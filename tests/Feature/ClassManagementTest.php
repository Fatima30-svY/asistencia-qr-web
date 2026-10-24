<?php

namespace Tests\Feature;

use App\Models\Clase;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_create_group_subject_and_class_together(): void
    {
        $director = User::factory()->create(['rol' => 'admin']);
        $teacher = User::factory()->create(['rol' => 'docente']);
        $data = [
            'docente_id' => $teacher->id,
            'materia_id' => '',
            'materia_nombre' => 'Programación',
            'grupo_id' => '',
            'grupo_nombre' => 'Grupo 704',
            'semestre' => 7,
            'fecha' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fin' => '09:00',
            'tolerancia_min' => 10,
        ];

        $this->actingAs($director)->post(route('docente.guardar'), array_replace($data, ['hora_fin' => '07:00']))
            ->assertSessionHasErrors('hora_fin');
        $this->assertDatabaseCount('grupos', 0);
        $this->assertDatabaseCount('materias', 0);
        $this->assertDatabaseCount('clases', 0);
        $this->post(route('docente.guardar'), array_replace($data, ['semestre' => '']))
            ->assertSessionHasErrors('semestre');
        $this->post(route('docente.guardar'), $data)->assertSessionHasNoErrors()->assertRedirect(route('docente.clases'));

        $this->assertDatabaseCount('clases', 1);
        $this->assertDatabaseHas('grupos', ['nombre' => 'Grupo 704', 'semestre' => 7]);
        $this->assertDatabaseHas('materias', ['nombre' => 'Programación']);
        $class = Clase::with(['materia', 'grupo'])->firstOrFail();
        $this->assertSame($teacher->id, $class->docente_id);
        $this->assertSame('Programación', $class->materia->nombre);
        $this->assertSame('Grupo 704', $class->grupo->nombre);
        $this->get(route('docente.clases'))->assertOk()->assertSee('Grupo 704')->assertSee('Programación');
        $this->actingAs($teacher)->get(route('docente.clases'))->assertOk()->assertSee('Grupo 704')->assertSee('Programación');
    }

    public function test_director_can_create_subject_for_an_existing_group(): void
    {
        $director = User::factory()->create(['rol' => 'admin']);
        $teacher = User::factory()->create(['rol' => 'docente']);
        $group = Grupo::create(['nombre' => 'Grupo 702', 'semestre' => 7]);

        $this->actingAs($director)->post(route('docente.guardar'), [
            'docente_id' => $teacher->id,
            'materia_nombre' => 'Matemáticas',
            'grupo_id' => $group->id,
            'grupo_nombre' => '',
            'semestre' => '',
            'fecha' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fin' => '09:00',
            'tolerancia_min' => 10,
        ])->assertSessionHasNoErrors()->assertRedirect(route('docente.clases'));

        $this->assertDatabaseCount('grupos', 1);
        $this->assertDatabaseHas('clases', ['grupo_id' => $group->id, 'docente_id' => $teacher->id]);
    }

    public function test_only_directors_can_schedule_classes(): void
    {
        $director = User::factory()->create(['rol' => 'admin']);
        $teacher = User::factory()->create(['rol' => 'docente']);
        $student = User::factory()->create(['rol' => 'alumno']);
        $group = Grupo::create(['nombre' => '3A', 'semestre' => 3]);
        $subject = Materia::create(['nombre' => 'Programación']);
        $data = [
            'docente_id' => $teacher->id,
            'grupo_id' => $group->id,
            'materia_id' => $subject->id,
            'fecha' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fin' => '09:00',
            'tolerancia_min' => 10,
        ];

        foreach ([$teacher, $student] as $user) {
            $this->actingAs($user)->get(route('docente.crear'))->assertForbidden();
            $this->post(route('docente.guardar'), $data)->assertForbidden();
        }

        $this->assertDatabaseCount('clases', 0);
        $this->actingAs($director)->get(route('docente.crear'))->assertOk()->assertSee($teacher->name);
        $this->post(route('docente.guardar'), $data)->assertRedirect(route('docente.clases'));
        $this->assertDatabaseHas('clases', ['docente_id' => $teacher->id, 'grupo_id' => $group->id]);

        $class = Clase::firstOrFail();
        $this->get(route('docente.clases'))->assertOk()->assertSee('Programar clase');
        $this->get(route('docente.qr', $class))->assertOk();
        $this->actingAs($teacher)->get(route('docente.clases'))->assertOk()->assertDontSee('Programar clase');
        $this->get(route('docente.qr', $class))->assertOk();

        $otherTeacher = User::factory()->create(['rol' => 'docente']);
        $this->actingAs($otherTeacher)->get(route('docente.qr', $class))->assertForbidden();
        $this->actingAs($student)->get(route('docente.clases'))->assertForbidden();

        $this->actingAs($director)->post(route('docente.guardar'), array_replace($data, ['docente_id' => $student->id]))
            ->assertSessionHasErrors('docente_id');
        $this->post(route('docente.guardar'), array_replace($data, ['docente_id' => $director->id]))
            ->assertSessionHasErrors('docente_id');
        $this->assertDatabaseCount('clases', 1);
    }
}
