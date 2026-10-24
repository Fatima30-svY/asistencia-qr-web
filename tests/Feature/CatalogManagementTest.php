<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_complete_missing_catalogs_and_schedule_a_class(): void
    {
        $director = User::factory()->create(['rol' => 'admin']);
        $teacher = User::factory()->create(['rol' => 'docente']);

        $this->actingAs($director)->get(route('docente.crear'))
            ->assertOk()
            ->assertSee($teacher->name)
            ->assertSee('name="materia_nombre"', false)
            ->assertSee('name="grupo_nombre"', false)
            ->assertSee('name="docente_id"', false);

        $this->post(route('catalogos.materias.guardar'), ['nombre' => 'Programación'])
            ->assertRedirect(route('docente.crear'));
        $this->post(route('catalogos.grupos.guardar'), ['nombre' => '3A', 'semestre' => 3])
            ->assertRedirect(route('docente.crear'));

        $this->assertDatabaseHas('materias', ['nombre' => 'Programación']);
        $this->assertDatabaseHas('grupos', ['nombre' => '3A', 'semestre' => 3]);
        $this->assertDatabaseCount('clases', 0);
        $this->get(route('docente.crear'))->assertOk()
            ->assertSee('name="docente_id"', false)
            ->assertDontSee('Falta registrar una materia.')
            ->assertDontSee('Falta registrar un grupo.');

        $this->post(route('docente.guardar'), [
            'docente_id' => $teacher->id,
            'materia_id' => \App\Models\Materia::firstOrFail()->id,
            'grupo_id' => \App\Models\Grupo::firstOrFail()->id,
            'fecha' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fin' => '09:00',
            'tolerancia_min' => 10,
        ])->assertRedirect(route('docente.clases'));

        $this->assertDatabaseCount('clases', 1);
        $this->get(route('docente.clases'))->assertOk()->assertSee('Programación')->assertSee('3A');
        $this->get(route('dashboard'))->assertOk()->assertSee('Clases de hoy');
        $this->actingAs($teacher)->get(route('docente.clases'))->assertOk()->assertSee('Programación')->assertSee('3A');
        $this->get(route('dashboard'))->assertOk()->assertSee('Clases de hoy');
    }

    public function test_only_directors_can_create_catalog_entries(): void
    {
        foreach (['docente', 'alumno'] as $role) {
            $user = User::factory()->create(['rol' => $role]);
            $this->actingAs($user)->post(route('catalogos.materias.guardar'), ['nombre' => 'Programación'])->assertForbidden();
            $this->post(route('catalogos.grupos.guardar'), ['nombre' => '3A', 'semestre' => 3])->assertForbidden();
        }

        $this->assertDatabaseCount('materias', 0);
        $this->assertDatabaseCount('grupos', 0);
    }

    public function test_catalog_fields_are_validated_in_separate_error_bags(): void
    {
        $director = User::factory()->create(['rol' => 'admin']);
        $this->actingAs($director)->post(route('catalogos.materias.guardar'), ['nombre' => ''])
            ->assertSessionHasErrors(['nombre'], null, 'materia');
        $this->post(route('catalogos.grupos.guardar'), ['nombre' => '3A', 'semestre' => 0])
            ->assertSessionHasErrors(['semestre'], null, 'grupo');

        $this->post(route('catalogos.materias.guardar'), ['nombre' => 'Programación']);
        $this->post(route('catalogos.materias.guardar'), ['nombre' => 'Programación'])
            ->assertSessionHasErrors(['nombre'], null, 'materia');
        $this->post(route('catalogos.grupos.guardar'), ['nombre' => '3A', 'semestre' => 3]);
        $this->post(route('catalogos.grupos.guardar'), ['nombre' => '3A', 'semestre' => 3])
            ->assertSessionHasErrors(['nombre'], null, 'grupo');

        $this->assertDatabaseCount('materias', 1);
        $this->assertDatabaseCount('grupos', 1);
    }
}
