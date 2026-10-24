<?php

namespace Tests\Feature;

use App\Models\Grupo;
use App\Models\Materia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_add_and_remove_students_without_replacing_unselected_members(): void
    {
        $director = User::factory()->create(['rol' => 'admin']);
        $group = Grupo::create(['nombre' => '702', 'semestre' => 7]);
        $member = User::factory()->create(['rol' => 'alumno', 'grupo_id' => $group->id]);
        $students = User::factory()->count(3)->create(['rol' => 'alumno']);
        $url = route('admin.grupo.alumnos', $group);
        $this->actingAs($director)->get(route('admin.grupo.editar', $group))->assertOk()->assertSee('Lista de estudiantes');
        $this->patch($url, ['alumnos' => $students->pluck('id')->all(), 'accion' => 'agregar'])->assertSessionHasNoErrors();
        $this->assertSame(4, $group->alumnos()->count());
        $this->patch($url, ['alumnos' => [$students[0]->id], 'accion' => 'quitar']);
        $this->assertDatabaseHas('users', ['id' => $students[0]->id, 'grupo_id' => null]);
        $this->assertDatabaseHas('users', ['id' => $member->id, 'grupo_id' => $group->id]);
        $this->assertSame(3, $group->alumnos()->count());
    }

    public function test_transfer_requires_confirmation_and_only_students_can_be_assigned(): void
    {
        $director = User::factory()->create(['rol' => 'admin']);
        $group = Grupo::create(['nombre' => '702', 'semestre' => 7]);
        $other = Grupo::create(['nombre' => '703', 'semestre' => 7]);
        $student = User::factory()->create(['rol' => 'alumno', 'grupo_id' => $other->id]);
        $teacher = User::factory()->create(['rol' => 'docente']);
        $url = route('admin.grupo.alumnos', $group);
        $data = ['alumnos' => [$student->id], 'accion' => 'agregar'];
        $this->actingAs($director)->patch($url, $data)->assertSessionHasErrors('alumnos');
        $this->assertDatabaseHas('users', ['id' => $student->id, 'grupo_id' => $other->id]);
        $this->patch($url, $data + ['confirmar_traslado' => 1]);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'grupo_id' => $group->id]);
        $this->patch($url, ['alumnos' => [$teacher->id], 'accion' => 'agregar'])->assertSessionHasErrors('alumnos.0');
        $this->actingAs($teacher)->patch($url, $data)->assertForbidden();
        $this->actingAs($student)->get(route('admin.grupo.editar', $group))->assertForbidden();
    }

    public function test_student_and_subject_search_filters_and_pagination(): void
    {
        $director = User::factory()->create(['rol' => 'admin']);
        $group = Grupo::create(['nombre' => '702', 'semestre' => 7]);
        $student = User::factory()->create(['rol' => 'alumno', 'name' => 'Ana Estudiante', 'matricula' => 'A123', 'grupo_id' => $group->id]);
        User::factory()->count(24)->create(['rol' => 'alumno']);
        $subject = Materia::create(['nombre' => 'Programación', 'semestre' => 7]);
        Materia::create(['nombre' => 'Álgebra', 'semestre' => 1]);
        $this->actingAs($director)->get(route('admin.grupo.editar', $group))
            ->assertOk()->assertViewHas('alumnos', fn ($rows) => $rows->total() === 25 && $rows->count() === 20);
        $this->get(route('admin.grupo.editar', [$group, 'buscar_alumno' => 'A123', 'origen' => 'inscritos', 'semestre' => 7]))
            ->assertOk()->assertViewHas('alumnos', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $student->id)
            ->assertViewHas('materias', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $subject->id);
        $this->get(route('admin.index', ['buscar_usuario' => 'Ana Estudiante', 'rol' => 'alumno', 'buscar_materia' => 'Programación', 'semestre_materia' => 7]))
            ->assertOk()->assertViewHas('usuarios', fn ($rows) => $rows->total() === 1)
            ->assertViewHas('materias', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $subject->id)
            ->assertViewHas('todasMaterias', fn ($rows) => $rows->count() === 2);
    }

    public function test_subject_lists_are_additive_and_can_be_removed_without_deleting_catalog(): void
    {
        $director = User::factory()->create(['rol' => 'admin']);
        $group = Grupo::create(['nombre' => '702', 'semestre' => 7]);
        $first = Materia::create(['nombre' => 'Programación', 'semestre' => 7]);
        $second = Materia::create(['nombre' => 'Bases de datos', 'semestre' => 7]);
        $url = route('admin.grupo.materias', $group);
        $this->actingAs($director)->patch($url, ['materias' => [$first->id], 'accion' => 'agregar'])->assertSessionHasNoErrors();
        $this->patch($url, ['materias' => [$second->id], 'accion' => 'agregar']);
        $this->assertSame(2, $group->materias()->count());
        $this->patch($url, ['materias' => [$first->id], 'accion' => 'quitar']);
        $this->assertSame(1, $group->materias()->count());
        $this->assertDatabaseHas('materias', ['id' => $first->id]);
        $this->post(route('admin.grupo.materia.crear', $group), ['nombre' => 'Redes', 'semestre' => 7]);
        $this->assertSame(2, $group->materias()->count());
        $this->assertDatabaseHas('materias', ['nombre' => 'Redes', 'semestre' => 7]);
        $this->put(route('admin.editar', ['materias', $second->id]), ['nombre' => 'Bases de datos', 'semestre' => 5]);
        $this->assertDatabaseHas('materias', ['id' => $second->id, 'semestre' => 5]);
    }
}
