<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Excepcion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function school(): array
    {
        $director = User::factory()->create(['rol' => 'admin']);
        $teacher = User::factory()->create(['rol' => 'docente', 'aprobado_at' => null]);
        $group = Grupo::create(['nombre' => '702', 'semestre' => 7]);
        $subject = Materia::create(['nombre' => 'Programación']);
        $student = User::factory()->create(['rol' => 'alumno', 'grupo_id' => $group->id]);
        $class = Clase::create([
            'grupo_id' => $group->id, 'materia_id' => $subject->id, 'docente_id' => $teacher->id,
            'fecha' => '2026-10-19', 'hora_inicio' => '08:00', 'hora_fin' => '09:00',
            'tolerancia_min' => 10, 'token_qr' => 'test-token', 'qr_expira_at' => '2026-10-19 09:00',
        ]);
        $this->travelTo(Carbon::parse('2026-10-19 08:05'));

        return [$director, $teacher, $student, $class, $group, $subject];
    }

    public function test_teacher_requires_approval_and_director_can_assign_student(): void
    {
        [$director, $teacher, $student, $class, $group] = $this->school();
        $this->actingAs($teacher)->get(route('docente.clases'))->assertForbidden();
        $this->get(route('riesgo.index'))->assertForbidden();
        $this->get(route('admin.index'))->assertForbidden();
        $this->actingAs($director)->get(route('admin.docentes.index'))->assertOk()->assertSee($teacher->name)->assertSee('702');
        $this->patch(route('admin.usuario', $teacher), ['aprobado' => 1])->assertSessionHasNoErrors();
        $this->assertNotNull($teacher->fresh()->aprobado_at);
        $this->actingAs($teacher->fresh())->get(route('docente.clases'))->assertOk();
        $this->get(route('docente.qr', $class))->assertOk();
        $this->actingAs($director)->patch(route('admin.usuario', $student), ['grupo_id' => $group->id, 'matricula' => 'A123'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $student->id, 'grupo_id' => $group->id, 'matricula' => 'A123']);
        $this->patch(route('admin.usuario', $teacher), ['aprobado' => 0]);
        $this->actingAs($teacher->fresh())->get(route('docente.qr', $class))->assertForbidden();
    }

    public function test_scanning_registers_only_current_students_and_prevents_duplicates(): void
    {
        [$director, $teacher, $student, $class] = $this->school();
        $payload = ['clase_id' => $class->id, 'token' => 'test-token'];
        $this->actingAs($student)->get(route('alumno.confirmar', ['clase' => $class, 'token' => 'test-token']))->assertOk();
        $this->assertDatabaseCount('asistencias', 0);
        $this->postJson(route('alumno.registrar'), $payload)->assertCreated()->assertJsonPath('estado', 'asistencia');
        $this->postJson(route('alumno.registrar'), $payload)->assertUnprocessable();
        $this->get(route('alumno.index'))->assertOk()->assertSee('Programación')->assertSee('Gráfica de asistencia');
        $this->assertDatabaseCount('asistencias', 1);
        $outsider = User::factory()->create(['rol' => 'alumno']);
        $this->actingAs($outsider)->postJson(route('alumno.registrar'), $payload)->assertUnprocessable();
        $this->actingAs($director)->postJson(route('alumno.registrar'), $payload)->assertForbidden();
        $this->actingAs($teacher)->postJson(route('alumno.registrar'), $payload)->assertForbidden();
    }

    public function test_time_token_and_closure_rules_are_enforced(): void
    {
        [$director, $teacher, $student, $class] = $this->school();
        $payload = ['clase_id' => $class->id, 'token' => 'test-token'];
        $this->actingAs($student)->postJson(route('alumno.registrar'), array_replace($payload, ['token' => 'wrong']))->assertUnprocessable();
        $this->travelTo(Carbon::parse('2026-10-19 07:59'));
        $this->postJson(route('alumno.registrar'), $payload)->assertUnprocessable();
        $this->travelTo(Carbon::parse('2026-10-19 08:20'));
        $this->postJson(route('alumno.registrar'), $payload)->assertCreated()->assertJsonPath('estado', 'retardo');
        $absent = User::factory()->create(['rol' => 'alumno', 'grupo_id' => $class->grupo_id]);
        $this->actingAs($director)->post(route('docente.cerrar', $class))->assertRedirect();
        $this->assertNotNull($class->fresh()->cerrada_at);
        $this->assertDatabaseHas('asistencias', ['alumno_id' => $absent->id, 'estado' => 'falta']);
        $late = User::factory()->create(['rol' => 'alumno', 'grupo_id' => $class->grupo_id]);
        $this->actingAs($late)->postJson(route('alumno.registrar'), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('asistencias', 2);
    }

    public function test_manual_code_registers_attendance_with_the_same_rules(): void
    {
        [$director, $teacher, $student, $class] = $this->school();
        $code = $class->codigoManual();
        $this->actingAs($director)->get(route('docente.qr', $class))->assertOk()->assertSee($code)->assertSee('Código para escribir sin cámara');
        $outsider = User::factory()->create(['rol' => 'alumno']);
        $this->actingAs($outsider)->postJson(route('alumno.registrar'), ['codigo' => $code])->assertUnprocessable();
        $this->actingAs($student)->postJson(route('alumno.registrar'), ['codigo' => strtolower($code)])->assertCreated()->assertJsonPath('estado', 'asistencia');
        $this->postJson(route('alumno.registrar'), ['codigo' => $code])->assertUnprocessable();
        $this->assertDatabaseCount('asistencias', 1);
        $this->assertDatabaseHas('asistencias', ['alumno_id' => $student->id, 'clase_id' => $class->id, 'estado' => 'asistencia']);
    }

    public function test_qr_and_manual_code_rotate_together_and_stop_after_class_end(): void
    {
        [$director, $teacher, $student, $class] = $this->school();
        $class->update(['token_qr' => null, 'qr_expira_at' => null]);
        $this->actingAs($director)->getJson(route('docente.qr', $class))->assertOk()->assertJsonPath('finalizada', false);
        $class->refresh();
        $firstToken = $class->token_qr;
        $firstCode = $class->codigoManual();
        $this->assertSame('08:20:00', $class->qr_expira_at->format('H:i:s'));
        $this->getJson(route('docente.qr', $class))->assertOk();
        $this->assertSame($firstToken, $class->fresh()->token_qr);
        $this->travelTo(Carbon::parse('2026-10-19 08:21'));
        $this->actingAs($student)->postJson(route('alumno.registrar'), ['codigo' => $firstCode])->assertUnprocessable();
        $this->actingAs($director)->getJson(route('docente.qr', $class))->assertOk();
        $class->refresh();
        $this->assertNotSame($firstToken, $class->token_qr);
        $this->assertNotSame($firstCode, $class->codigoManual());
        $this->actingAs($student)->postJson(route('alumno.registrar'), ['codigo' => $firstCode])->assertUnprocessable();
        $this->postJson(route('alumno.registrar'), ['codigo' => $class->codigoManual()])->assertCreated()->assertJsonPath('estado', 'retardo');
        $this->travelTo(Carbon::parse('2026-10-19 08:59'));
        $this->actingAs($director)->getJson(route('docente.qr', $class))->assertOk();
        $class->refresh();
        $this->assertSame('09:00:00', $class->qr_expira_at->format('H:i:s'));
        $lastToken = $class->token_qr;
        $this->travelTo(Carbon::parse('2026-10-19 09:01'));
        $this->getJson(route('docente.qr', $class))->assertOk()->assertJsonPath('finalizada', true);
        $this->assertSame($lastToken, $class->fresh()->token_qr);
        $another = User::factory()->create(['rol' => 'alumno', 'grupo_id' => $class->grupo_id]);
        $this->actingAs($another)->postJson(route('alumno.registrar'), ['codigo' => $class->codigoManual()])->assertUnprocessable();
    }

    public function test_expired_qr_and_finished_class_reject_scans(): void
    {
        [$director, $teacher, $student, $class] = $this->school();
        $payload = ['clase_id' => $class->id, 'token' => 'test-token'];
        $class->update(['qr_expira_at' => now()->subMinute()]);
        $this->actingAs($student)->postJson(route('alumno.registrar'), $payload)->assertUnprocessable()->assertJsonPath('message', 'El código QR ya venció.');
        $class->update(['qr_expira_at' => now()->addHours(2)]);
        $this->travelTo(Carbon::parse('2026-10-19 09:01'));
        $this->postJson(route('alumno.registrar'), $payload)->assertUnprocessable()->assertJsonPath('message', 'La clase ya terminó.');
        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_closing_applies_justifications_and_is_idempotent(): void
    {
        [$director, $teacher, $student, $class] = $this->school();
        Excepcion::create([
            'tipo' => 'justificante', 'alumno_id' => $student->id,
            'fecha_inicio' => '2026-10-19', 'fecha_fin' => '2026-10-19', 'motivo' => 'Cita médica',
        ]);
        $this->actingAs($director)->post(route('docente.cerrar', $class))->assertRedirect();
        $this->post(route('docente.cerrar', $class))->assertRedirect();
        $this->assertDatabaseCount('asistencias', 1);
        $this->assertDatabaseHas('asistencias', ['alumno_id' => $student->id, 'estado' => 'justificada']);
        $this->assertNotNull($class->fresh()->cerrada_at);
    }

    public function test_class_crud_reassigns_teacher_and_deletes_unused_classes(): void
    {
        [$director, $teacher, $student, $class] = $this->school();
        $approved = User::factory()->create(['rol' => 'docente']);
        $data = [
            'docente_id' => $approved->id, 'grupo_id' => $class->grupo_id, 'materia_id' => $class->materia_id,
            'fecha' => '2026-10-20', 'hora_inicio' => '10:00', 'hora_fin' => '11:00', 'tolerancia_min' => 5,
        ];
        $this->actingAs($director)->put(route('admin.clase.editar', $class), array_replace($data, ['docente_id' => $teacher->id]))->assertSessionHasErrors('docente_id');
        $this->put(route('admin.clase.editar', $class), $data);
        $this->assertDatabaseHas('clases', ['id' => $class->id, 'docente_id' => $approved->id, 'token_qr' => null]);
        $this->delete(route('admin.clase.eliminar', $class));
        $this->assertDatabaseMissing('clases', ['id' => $class->id]);
    }

    public function test_director_can_edit_catalogs_and_delete_only_unused_data(): void
    {
        [$director, $teacher, $student, $class, $group, $subject] = $this->school();
        $this->actingAs($director)->put(route('admin.editar', ['materias', $subject->id]), ['nombre' => 'Programación II'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('materias', ['id' => $subject->id, 'nombre' => 'Programación II']);
        $this->delete(route('admin.eliminar', ['grupos', $group->id]))->assertSessionHasErrors();
        $this->delete(route('admin.eliminar', ['materias', $subject->id]))->assertSessionHasErrors();
        $unused = Grupo::create(['nombre' => 'Incompleto', 'semestre' => 1]);
        $this->delete(route('admin.eliminar', ['grupos', $unused->id]));
        $this->assertDatabaseMissing('grupos', ['id' => $unused->id]);
        Asistencia::create(['clase_id' => $class->id, 'alumno_id' => $student->id, 'estado' => 'asistencia']);
        $this->delete(route('admin.clase.eliminar', $class))->assertSessionHasErrors();
        $this->assertDatabaseHas('clases', ['id' => $class->id]);
    }

    public function test_public_registration_cannot_bypass_director_approval(): void
    {
        User::factory()->create(['rol' => 'admin']);
        $this->post('/register', [
            'name' => 'Intruso', 'email' => 'intruso@example.com', 'password' => 'password',
            'password_confirmation' => 'password', 'rol' => 'admin',
        ])->assertSessionHasErrors('rol');
        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
    }
}
