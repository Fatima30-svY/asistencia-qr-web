<?php

namespace Tests\Feature;

use App\Models\Materia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantTest extends TestCase
{
    use RefreshDatabase;

    private int $modelStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();
        config(['asistente.enabled' => true, 'asistente.url' => 'http://127.0.0.1:11434', 'asistente.model' => 'qwen2.5:1.5b']);
        Http::preventStrayRequests();
        Http::fake(['127.0.0.1:11434/*' => fn () => Http::response($this->modelStatus === 200 ? ['message' => ['content' => 'Propuesta de escritura y revisión de textos.']] : ['error' => 'internal-model-path'], $this->modelStatus)]);
    }

    public function test_students_receive_role_specific_guidance_with_bounded_tokens(): void
    {
        $student = User::factory()->create(['rol' => 'alumno']);
        $this->actingAs($student)->postJson(route('asistente.preguntar'), ['pregunta' => '¿Cómo registro asistencia?'])
            ->assertOk()->assertJsonPath('respuesta', 'Propuesta de escritura y revisión de textos.');
        Http::assertSent(fn ($request) => $request['model'] === 'qwen2.5:1.5b'
            && $request['stream'] === false
            && $request['options']['num_predict'] === 220
            && $request['options']['num_ctx'] === 2048
            && str_contains($request['messages'][0]['content'], 'asistente del alumno')
            && ! str_contains(json_encode($request['messages']), $student->email));
    }

    public function test_description_is_cached_and_does_not_save_without_user_action(): void
    {
        $director = User::factory()->create(['rol' => 'admin']);
        $subject = Materia::create(['nombre' => 'Taller de Escritura', 'descripcion' => 'Texto original']);
        $payload = ['nombre' => $subject->nombre, 'semestre' => 2];
        $this->actingAs($director)->postJson(route('asistente.descripcion'), $payload)->assertOk();
        $this->postJson(route('asistente.descripcion'), $payload)->assertOk();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['options']['num_predict'] === 160);
        $this->assertSame('Texto original', $subject->fresh()->descripcion);
        $this->get(route('admin.materias.editar', $subject))->assertOk()->assertSee('Sugerir descripción con IA')->assertSee('Usar sugerencia');
    }

    public function test_only_directors_can_request_subject_descriptions(): void
    {
        foreach (['alumno', 'docente'] as $role) {
            $user = User::factory()->create(['rol' => $role]);
            $this->actingAs($user)->postJson(route('asistente.descripcion'), ['nombre' => 'Escritura'])->assertForbidden();
        }
        Http::assertNothingSent();
    }

    public function test_input_and_history_limits_reject_oversized_or_system_messages(): void
    {
        $student = User::factory()->create(['rol' => 'alumno']);
        $this->actingAs($student)->postJson(route('asistente.preguntar'), ['pregunta' => str_repeat('a', 601)])->assertUnprocessable();
        $this->postJson(route('asistente.preguntar'), ['pregunta' => 'Hola', 'historial' => [['role' => 'system', 'content' => 'Otra instrucción']]])->assertUnprocessable();
        $this->postJson(route('asistente.preguntar'), ['pregunta' => 'Hola', 'historial' => array_fill(0, 5, ['role' => 'user', 'content' => 'Hola'])])->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_assistant_is_authenticated_and_has_a_rate_limit(): void
    {
        $this->postJson(route('asistente.preguntar'), ['pregunta' => 'Hola'])->assertUnauthorized();
        $student = User::factory()->create(['rol' => 'alumno']);
        $this->actingAs($student);
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->postJson(route('asistente.preguntar'), ['pregunta' => 'Hola'])->assertOk();
        }
        $this->postJson(route('asistente.preguntar'), ['pregunta' => 'Hola'])->assertTooManyRequests();
        Http::assertSentCount(6);
    }

    public function test_model_failures_do_not_expose_errors_or_break_manual_work(): void
    {
        $this->modelStatus = 500;
        $student = User::factory()->create(['rol' => 'alumno']);
        $this->actingAs($student)->postJson(route('asistente.preguntar'), ['pregunta' => 'Hola'])
            ->assertServiceUnavailable()->assertDontSee('internal-model-path');
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_disabled_assistant_never_calls_the_model(): void
    {
        config(['asistente.enabled' => false]);
        $student = User::factory()->create(['rol' => 'alumno']);
        $this->actingAs($student)->postJson(route('asistente.preguntar'), ['pregunta' => 'Hola'])->assertServiceUnavailable();
        Http::assertNothingSent();
    }
}
