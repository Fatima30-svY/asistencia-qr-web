<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Excepcion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForcedGroupDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_deletion_requires_explicit_confirmation_and_preserves_other_groups(): void
    {
        $director = User::factory()->create(['rol' => 'admin']);
        $teacher = User::factory()->create(['rol' => 'docente']);
        $group = Grupo::create(['nombre' => 'Grupo 702', 'semestre' => 7]);
        $other = Grupo::create(['nombre' => 'Grupo 703', 'semestre' => 7]);
        $student = User::factory()->create(['rol' => 'alumno', 'grupo_id' => $group->id, 'matricula' => '12345']);
        $otherStudent = User::factory()->create(['rol' => 'alumno', 'grupo_id' => $other->id]);
        $subject = Materia::create(['nombre' => 'Programación']);
        $group->materias()->attach($subject->id);
        $other->materias()->attach($subject->id);
        $data = [
            'docente_id' => $teacher->id, 'materia_id' => $subject->id,
            'fecha' => '2026-10-19', 'hora_inicio' => '08:00', 'hora_fin' => '09:00',
        ];
        $class = Clase::create($data + ['grupo_id' => $group->id]);
        $otherClass = Clase::create($data + ['grupo_id' => $other->id]);
        Asistencia::create(['clase_id' => $class->id, 'alumno_id' => $student->id, 'estado' => 'asistencia']);
        Asistencia::create(['clase_id' => $otherClass->id, 'alumno_id' => $otherStudent->id, 'estado' => 'retardo']);
        $exception = Excepcion::create(['tipo' => 'festivo', 'grupo_id' => $group->id, 'fecha_inicio' => '2026-10-19', 'fecha_fin' => '2026-10-19']);
        $individual = Excepcion::create(['tipo' => 'permiso', 'alumno_id' => $student->id, 'fecha_inicio' => '2026-10-19', 'fecha_fin' => '2026-10-19']);
        $url = route('admin.eliminar', ['grupos', $group->id]);

        $this->actingAs($director)->get(route('admin.grupo.confirmar-eliminacion', $group))->assertOk()->assertSee('Grupo 702')->assertSee('Esta acción es permanente');
        $this->assertDatabaseCount('grupos', 2);
        $this->delete($url)->assertSessionHasErrors('administracion');
        $this->delete($url, ['forzar' => 1])->assertSessionHasErrors(['confirmacion', 'acepto_eliminacion']);
        $this->delete($url, ['forzar' => 1, 'confirmacion' => 'incorrecto', 'acepto_eliminacion' => 1])->assertSessionHasErrors('confirmacion');
        $this->assertDatabaseCount('clases', 2);
        $this->assertDatabaseCount('asistencias', 2);

        $this->delete($url, ['forzar' => 1, 'confirmacion' => 'Grupo 702', 'acepto_eliminacion' => 1])->assertRedirect(route('admin.index'));
        $this->assertDatabaseMissing('grupos', ['id' => $group->id]);
        $this->assertDatabaseMissing('clases', ['id' => $class->id]);
        $this->assertDatabaseMissing('asistencias', ['clase_id' => $class->id]);
        $this->assertDatabaseMissing('excepciones', ['id' => $exception->id]);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'grupo_id' => null, 'matricula' => '12345']);
        $this->assertDatabaseHas('users', ['id' => $teacher->id]);
        $this->assertDatabaseHas('materias', ['id' => $subject->id]);
        $this->assertDatabaseHas('excepciones', ['id' => $individual->id]);
        $this->assertDatabaseHas('grupos', ['id' => $other->id]);
        $this->assertDatabaseMissing('grupo_materia', ['grupo_id' => $group->id]);
        $this->assertDatabaseHas('grupo_materia', ['grupo_id' => $other->id, 'materia_id' => $subject->id]);
        $this->assertDatabaseHas('asistencias', ['clase_id' => $otherClass->id]);
        $this->get(route('admin.index'))->assertOk();
    }

    public function test_non_administrators_cannot_force_delete_or_open_confirmation(): void
    {
        $group = Grupo::create(['nombre' => '702', 'semestre' => 7]);
        foreach (['docente', 'alumno'] as $role) {
            $user = User::factory()->create(['rol' => $role]);
            $this->actingAs($user)->get(route('admin.grupo.confirmar-eliminacion', $group))->assertForbidden();
            $this->delete(route('admin.eliminar', ['grupos', $group->id]), ['forzar' => 1, 'confirmacion' => '702', 'acepto_eliminacion' => 1])->assertForbidden();
        }
        $this->assertDatabaseHas('grupos', ['id' => $group->id]);
    }
}
