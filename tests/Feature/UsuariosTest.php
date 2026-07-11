<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    private function rolEncargado(): Rol
    {
        return Rol::firstOrCreate(
            ['codigo' => Rol::COD_ENCARGADO],
            ['nombre' => 'Encargado', 'descripcion' => 'Operación diaria.', 'activo' => true],
        );
    }

    private function rolAdmin(): Rol
    {
        return Rol::firstOrCreate(
            ['codigo' => Rol::COD_ADMIN],
            ['nombre' => 'Administrador', 'descripcion' => 'Acceso total al sistema.', 'activo' => true],
        );
    }

    public function test_admin_puede_ver_el_indice_de_usuarios(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('usuarios.index'))
            ->assertOk();
    }

    public function test_encargado_no_puede_acceder_a_usuarios(): void
    {
        $encargado = User::factory()->encargado()->create();

        $this->actingAs($encargado)
            ->get(route('usuarios.index'))
            ->assertForbidden();
    }

    public function test_admin_puede_crear_un_usuario(): void
    {
        $admin = User::factory()->admin()->create();
        $rol = $this->rolEncargado();
        $this->actingAs($admin);

        Livewire::test('usuarios.form')
            ->call('abrir')
            ->set('name', 'Nuevo Encargado')
            ->set('email', 'Nuevo@SGA.test')
            ->set('rol_id', $rol->id)
            ->set('password', 'secret123')
            ->set('activo', true)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertDispatched('usuario-guardado');

        // El correo se normaliza a minúsculas.
        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@sga.test',
            'name' => 'Nuevo Encargado',
            'activo' => true,
        ]);
    }

    public function test_editar_sin_password_conserva_la_contrasena(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $user = User::factory()->encargado()->create(['password' => Hash::make('original123')]);

        Livewire::test('usuarios.form')
            ->call('abrir', id: $user->id)
            ->set('name', 'Nombre Cambiado')
            ->set('password', '')
            ->call('guardar')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('Nombre Cambiado', $user->name);
        $this->assertTrue(Hash::check('original123', $user->password));
    }

    public function test_el_rol_no_cambia_al_editar(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $encargado = User::factory()->encargado()->create();
        $rolAdmin = $this->rolAdmin();

        // Aunque se intente forzar rol_id de administrador, debe ignorarse.
        Livewire::test('usuarios.form')
            ->call('abrir', id: $encargado->id)
            ->set('rol_id', $rolAdmin->id)
            ->set('name', 'Sigue Encargado')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame($this->rolEncargado()->id, $encargado->fresh()->rol_id);
    }

    public function test_admin_puede_desactivar_a_un_encargado(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $otro = User::factory()->encargado()->create(['activo' => true]);

        Livewire::test('usuarios.modal-toggle-activo')
            ->call('abrir', id: $otro->id)
            ->call('confirmar')
            ->assertDispatched('usuario-estado-cambiado');

        $this->assertFalse($otro->fresh()->activo);
    }

    public function test_no_se_puede_desactivar_a_un_administrador(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $otroAdmin = User::factory()->admin()->create(['activo' => true]);

        Livewire::test('usuarios.modal-toggle-activo')
            ->call('abrir', id: $otroAdmin->id)
            ->assertSet('usuarioId', null);

        $this->assertTrue($otroAdmin->fresh()->activo);
    }

    public function test_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        $user = User::factory()->encargado()->create([
            'activo' => false,
            'password' => Hash::make('password123'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_desactivado_en_sesion_es_expulsado(): void
    {
        $user = User::factory()->encargado()->create(['activo' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
