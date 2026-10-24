<?php

namespace Tests\Feature;

use App\Models\Materia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sections_separate_teachers_students_subjects_and_groups(): void
    {
        $director = User::factory()->create(['rol' => 'admin']);
        $teacher = User::factory()->create(['rol' => 'docente', 'name' => 'Docente Separado']);
        $student = User::factory()->create(['rol' => 'alumno', 'name' => 'Alumno Separado']);
        Materia::create(['nombre' => 'Materia Separada']);
        $this->actingAs($director)->get(route('admin.index'))->assertOk()->assertSee('Control escolar')->assertDontSee($teacher->name)->assertDontSee($student->name);
        $this->get(route('admin.docentes.index'))->assertOk()->assertSee($teacher->name)->assertDontSee($student->name)->assertDontSee('Materia Separada');
        $this->get(route('admin.alumnos.index'))->assertOk()->assertSee($student->name)->assertDontSee($teacher->name)->assertDontSee('Materia Separada');
        $this->get(route('admin.docentes.index', ['rol' => 'alumno']))->assertOk()->assertSee($teacher->name)->assertDontSee($student->name);
        $this->get(route('admin.materias.index'))->assertOk()->assertSee('Materia Separada')->assertDontSee($teacher->name)->assertDontSee($student->name);
        $this->get(route('admin.grupos.index'))->assertOk()->assertDontSee($teacher->name)->assertDontSee('Materia Separada');
        $this->get(route('admin.clases.index'))->assertOk();
        $this->actingAs($teacher)->get(route('admin.materias.index'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.docentes.index'))->assertForbidden();
    }

    public function test_existing_subject_details_can_be_completed_and_searched_by_code(): void
    {
        $director = User::factory()->create(['rol' => 'admin']);
        $subject = Materia::create(['nombre' => 'Inteligencia']);
        $this->actingAs($director)->get(route('admin.materias.editar', $subject))->assertOk()->assertSee('Editar materia')->assertSee('ID del registro');
        $this->put(route('admin.editar', ['materias', $subject->id]), [
            'nombre' => 'Inteligencia artificial', 'semestre' => 7, 'clave' => 'IA-701', 'descripcion' => 'Sistemas basados en conocimiento.',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.materias.editar', $subject));
        $this->assertDatabaseHas('materias', ['id' => $subject->id, 'clave' => 'IA-701', 'semestre' => 7, 'descripcion' => 'Sistemas basados en conocimiento.']);
        $this->get(route('admin.materias.index', ['buscar_materia' => 'IA-701']))->assertOk()->assertSee('Inteligencia artificial');
        $this->get(route('admin.materias.crear'))->assertOk();
        $this->post(route('admin.crear', 'materias'), ['nombre' => 'Otra materia', 'clave' => 'IA-701'])->assertSessionHasErrors('clave');
        $this->assertDatabaseCount('materias', 1);
    }

    public function test_user_contact_edits_preserve_role_and_teacher_approval(): void
    {
        $director = User::factory()->create(['rol' => 'admin']);
        $teacher = User::factory()->create(['rol' => 'docente']);
        $approval = $teacher->aprobado_at;
        $this->actingAs($director)->patch(route('admin.usuario', $teacher), ['name' => 'Nombre actualizado', 'email' => 'docente@example.com', 'rol' => 'admin'])->assertSessionHasNoErrors();
        $teacher->refresh();
        $this->assertSame('docente', $teacher->rol);
        $this->assertTrue($approval->equalTo($teacher->aprobado_at));
        $this->assertNull($teacher->email_verified_at);
        $this->assertDatabaseHas('users', ['id' => $teacher->id, 'name' => 'Nombre actualizado', 'email' => 'docente@example.com']);
    }
}
