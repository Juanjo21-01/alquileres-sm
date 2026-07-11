# SGA-SM · Plan de Desarrollo

> Sistema de Gestión de Alquileres — San Marcos
> Documento operativo de desarrollo. Sirve como bitácora: marca cada tarea conforme avances.
> **Versión 3.0** — Livewire 4 SFC · Flux Free · Chart.js · recibos on-demand · comprobantes con visor PDF.

---

## Convenciones del proyecto

### Idioma
- Todas las **tablas, columnas, modelos, métodos y rutas del dominio del negocio** van en **español**.
- **Excepción intencional:** `users` y `App\Models\User` se mantienen en inglés (convención de Laravel/Fortify, paquetes de terceros, factories, notificaciones). Esa tabla representa al operador del sistema (admin/encargado), no al dominio de alquileres. Los inquilinos viven en su propia tabla en español.
- Tablas del dominio: snake_case, plural (`propiedades`, `cuartos`, `extras_estancia`).
- Modelos Eloquent del dominio: PascalCase singular en español (`Propiedad`, `Cuarto`, `ExtraEstancia`). Si Laravel no infiere bien el plural en español, fijar `protected $table`.
- Métodos: camelCase en español (`abrirEstancia`, `calcularFlujoCaja`).
- Rutas: kebab-case en español (`/propiedades`, `/extras-estancia`).

### Componentes Livewire v4 — Single-File Components (SFC)
- Formato por defecto: **SFC** (clase PHP y vista Blade en un solo archivo).
- **NO usar multi-file components** (`--mfc`) en este proyecto.
- **Páginas** (rutas accesibles vía URL): namespace `pages::`. Viven en `resources/views/pages/`.
- **Componentes reusables** (tablas, formularios, modales embebidos): namespace `components::` (default). Viven en `resources/views/components/`.
- Patrón de organización: un componente de tabla, formulario y modal por módulo. No abstraer en un componente genérico parametrizado.
- Nombrado dot-notation: `pages::propiedades.index`, `components::propiedades.tabla`, `components::propiedades.form`.

### Datos y dinero
- Dinero: `decimal(10,2)` siempre. Nunca `float`.
- Fechas: UTC en BD, presentación con timezone `America/Guatemala`.
- Soft deletes activos en: `inquilinos`, `estancias`, `pagos`, `gastos`.

### Arquitectura
- Estructura estándar de Laravel + carpetas `app/Services/` y `app/Observers/`.
- **NO** usar arquitectura tipo DDD (sin carpeta `Domain/`).
- Lógica de negocio transaccional: en `app/Services/`. Los componentes Livewire **solo orquestan** (UI, validación, llamada al service).
- Toda operación que afecte 2+ tablas dentro de `DB::transaction()`.
- Para evitar race conditions: `lockForUpdate()` en consultas críticas dentro de transacciones.

### Autorización
- Auth: Laravel Fortify (viene con starter kit Livewire). Registro público deshabilitado.
- Tabla `roles` normalizada con FK `rol_id` desde `users`.
- Autorización: Policies + Gates + middleware `rol` nativo. **NO usar Spatie**.
- Roles iniciales: `administrador`, `encargado`. Preparado para `inquilino` a futuro.

### Commits
- Conventional commits: `feat:`, `fix:`, `refactor:`, `chore:`, `docs:`.
- Commits manuales por el desarrollador, no automáticos por el agente.

### Stack confirmado

| Componente | Tecnología |
|---|---|
| Backend | Laravel 13 (PHP 8.3+) |
| Frontend | Blade + Livewire 4 (SFC) |
| Componentes UI | Flux Free (livewire/flux) |
| Estilos | Tailwind CSS (incluido con Flux) |
| Cliente JS | Alpine.js (incluido con Livewire, no requiere instalación) |
| Gráficos | Chart.js (vía npm) |
| BD | MySQL 8 |
| PDF | barryvdh/laravel-dompdf |
| Auth | Laravel Fortify (vía starter kit) |
| Authorization | Policies + Gates + Middleware nativos |
| Dev assistant | Laravel Boost |
| VCS | Git + GitHub |

---

## Tabla de fases

| Fase | Nombre | Duración estimada |
|---|---|---|
| 0 | Ajustes post-scaffold | 1-2 días |
| 1 | Roles, propiedades y cuartos | 1 semana |
| 2 | Inquilinos y estancias | 1.5 semanas |
| 3 | Ingresos (pagos y parqueo externo) | 1.5 semanas |
| 4 | Egresos (gastos) | 4-5 días |
| 5 | PDF y reportes | 1-1.5 semanas |
| 6 | Pulido final y despliegue | 4-5 días |

---

# Fase 0 — Ajustes post-scaffold

**Objetivo:** dejar el proyecto Laravel (creado por el playbook oficial con `--livewire --boost`) configurado con las decisiones del proyecto: locale español, timezone GT, Chart.js instalado, registro público deshabilitado en Fortify, dompdf instalado, estructura de carpetas adicional creada.

**Duración:** 1-2 días.

> **Nota previa:** el starter kit Livewire ya resolvió: instalación de Laravel 13, Livewire 4, Tailwind 4, Vite, Fortify, layout base, vistas de auth (login, recuperación, perfil), navegación, MySQL configurable. La Fase 0 solo ajusta lo que **no** viene por defecto.

## Tareas

### Configuración del entorno

- [ ] Verificar `.env`:
  ```dotenv
  APP_NAME="Alquileres SM"
  APP_TIMEZONE=America/Guatemala
  APP_LOCALE=es
  APP_FALLBACK_LOCALE=es
  APP_FAKER_LOCALE=es_GT
  DB_CONNECTION=mysql
  DB_DATABASE=alquileres_sm
  ```
- [ ] Editar `config/app.php`:
  - `'timezone' => 'America/Guatemala'`
  - `'locale' => 'es'`
  - `'faker_locale' => 'es_GT'`
- [ ] Crear BD: `CREATE DATABASE alquileres_sm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
- [ ] Verificar que el archivo de traducciones `lang/es.json` exista (si no, crearlo vacío para empezar).

### Instalación de Chart.js (para reportes en Fase 5)

> Chart.js se instala como dependencia npm para no depender de un CDN externo y aprovechar el tree-shaking de Vite.

- [ ] `npm install chart.js`
- [ ] Verificar en `package.json` que aparece en `dependencies`:
  ```json
  "dependencies": {
      "chart.js": "^4.5.1"
  }
  ```
- [ ] Importar en `resources/js/app.js` solo cuando lleguemos a Fase 5 (no es necesario hacerlo ahora). Cuando llegue ese momento, se importarán únicamente los componentes que se usen, así Vite hace tree-shaking:
  ```js
  // En Fase 5, agregar al app.js:
  // import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend } from 'chart.js';
  // Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend);
  // window.Chart = Chart;
  ```

> **Nota sobre estilos:** no instalamos daisyUI ni otra librería de componentes UI. **Flux Free** (incluido en `composer.json` como `livewire/flux`) ya provee todos los componentes que SGA-SM necesita: botones, modales, inputs, selects, tablas, badges, toasts, dropdowns, switches, etc. Para componentes que Flux no incluye (date pickers avanzados, charts, etc.), usamos las alternativas nativas o Chart.js como en este caso.

### Configuración de Fortify (deshabilitar registro)

- [ ] Editar `config/fortify.php` para dejar **solo**:
  ```php
  'features' => [
      Features::resetPasswords(),
      Features::updateProfileInformation(),
      Features::updatePasswords(),
      // Features::registration() ← deshabilitada
      // Features::emailVerification() ← opcional, puede dejarse
  ],
  ```
- [ ] Eliminar (o esconder) las rutas/vistas de registro generadas por el starter kit.
  - Buscar en `resources/views/pages/` o `resources/views/components/auth/` el componente `register` y eliminarlo.
  - Quitar el link "Registrarse" del layout de auth si existe.
- [ ] Verificar que `/login` y `/forgot-password` siguen funcionando, pero `/register` da 404.

### Instalación de dompdf

- [ ] `composer require barryvdh/laravel-dompdf`
- [ ] `php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"`
- [ ] Editar `config/dompdf.php`: `'default_font' => 'DejaVu Sans'`.

### Estructura de carpetas adicional

- [ ] Crear:
  ```bash
  mkdir -p app/Services app/Observers
  mkdir -p resources/views/pages
  mkdir -p resources/views/components/auth
  mkdir -p storage/app/comprobantes
  touch storage/app/comprobantes/.gitkeep
  ```

> **Nota:** no creamos `storage/app/recibos/` porque los recibos PDF se generan **on-demand** y se descargan directamente al usuario, sin persistir en disco. Solo los comprobantes de gastos (que sube el usuario) se almacenan.

> **Nota sobre el namespace `pages::`:** Livewire v4 lo registra automáticamente al detectar la carpeta `resources/views/pages/`. No requiere configuración adicional.

### Verificación

- [ ] `npm run dev` compila sin errores.
- [ ] `composer run dev` (o `php artisan serve` + `npm run dev`) levanta la app.
- [ ] El admin (creado por seeder en Fase 1) podrá iniciar sesión.
- [ ] El layout base se ve con Tailwind y Flux funcionando.

## Comandos útiles

```bash
# Servir la app
composer run dev   # incluye queue + logs + vite si está configurado en composer.json
# o por separado:
php artisan serve
npm run dev

# Refrescar BD
php artisan migrate:fresh --seed

# Limpiar caches en dev
php artisan optimize:clear
```

## Criterios de aceptación

- ✅ El proyecto corre con assets compilados.
- ✅ El locale es español, el timezone es Guatemala.
- ✅ Flux está disponible (probarlo con `<flux:button variant="primary">Test</flux:button>`).
- ✅ Chart.js está en `package.json` como dependencia.
- ✅ La ruta `/register` no existe.
- ✅ dompdf está listo para usarse.
- ✅ La estructura de carpetas extras está creada.

---

# Fase 1 — Roles, propiedades y cuartos

**Objetivo:** introducir la tabla `roles` y la FK en `users`, e implementar CRUD completo de propiedades y cuartos con vista de tablero por casa, autorización por rol y SFC reutilizables (tabla, formulario, modal de eliminación) por módulo.

**Duración:** 1 semana.

## Migraciones

> **Orden:** primero `roles`, luego modificar la migración default de `users` (agregar `rol_id`), luego `propiedades`, luego `cuartos`. Si aún no has corrido `migrate` por primera vez, **edita directamente la migración default de `users`** en vez de crear una nueva — queda más limpio.

### 1.1 Crear `roles`

```bash
php artisan make:migration create_roles_table
```

```php
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('nombre', 50)->unique();
    $table->string('codigo', 30)->unique();
    $table->string('descripcion', 255)->nullable();
    $table->boolean('activo')->default(true);
    $table->timestamps();
});
```

> **Importante:** la migración de `roles` debe ejecutarse **antes** que la de `users` (o que tenga la FK `rol_id`). Si ya tiene timestamp posterior, renómbrala para ponerla primero.

### 1.2 Modificar la migración default de `users`

Edita el archivo `database/migrations/0001_01_01_000000_create_users_table.php` (o el equivalente que generó el starter kit). Agrega la columna `rol_id`:

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rol_id')->constrained('roles')->restrictOnDelete(); // ← AGREGADO
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->boolean('activo')->default(true); // ← AGREGADO
    $table->rememberToken();
    $table->timestamps();
});
```

> Si ya corriste `migrate` antes de este paso, crea una migración aparte que agregue las columnas con `Schema::table('users', ...)`.

### 1.3 Crear `propiedades`

```bash
php artisan make:migration create_propiedades_table
```

```php
Schema::create('propiedades', function (Blueprint $table) {
    $table->id();
    $table->string('nombre', 150);
    $table->string('direccion', 255);
    $table->string('zona', 50)->nullable();
    $table->string('referencia', 255)->nullable();
    $table->text('notas')->nullable();
    $table->boolean('activo')->default(true);
    $table->timestamps();

    $table->index('activo');
});
```

### 1.4 Crear `cuartos`

```bash
php artisan make:migration create_cuartos_table
```

```php
Schema::create('cuartos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('propiedad_id')->constrained('propiedades')->restrictOnDelete();
    $table->string('codigo', 20);
    $table->unsignedTinyInteger('nivel')->default(1);
    $table->string('tamano', 50)->nullable();
    $table->decimal('precio_base', 10, 2);
    $table->enum('estado', ['disponible', 'ocupado', 'reservado', 'mantenimiento'])
          ->default('disponible');
    $table->text('descripcion')->nullable();
    $table->boolean('activo')->default(true);
    $table->timestamps();

    $table->unique(['propiedad_id', 'codigo']);
    $table->index('estado');
});
```

## Modelos

### `app/Models/Rol.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'roles';
    protected $fillable = ['nombre', 'codigo', 'descripcion', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public const COD_ADMIN     = 'administrador';
    public const COD_ENCARGADO = 'encargado';
    public const COD_INQUILINO = 'inquilino'; // futuro

    public function users()
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}
```

### `app/Models/User.php` (modificar el existente)

> El starter kit ya creó `User.php`. Solo agrega:

```php
protected $fillable = [
    'rol_id', 'name', 'email', 'password', 'activo', // ← agrega rol_id y activo
];

protected $casts = [
    'email_verified_at' => 'datetime',
    'password'          => 'hashed',
    'activo'            => 'boolean',
];

protected $with = ['rol']; // eager-load del rol siempre

public function rol()
{
    return $this->belongsTo(Rol::class, 'rol_id');
}

public function tieneRol(string $codigo): bool
{
    return $this->rol?->codigo === $codigo;
}

public function esAdministrador(): bool
{
    return $this->tieneRol(Rol::COD_ADMIN);
}

public function esEncargado(): bool
{
    return $this->tieneRol(Rol::COD_ENCARGADO);
}
```

### `app/Models/Propiedad.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Propiedad extends Model
{
    protected $table = 'propiedades';
    protected $fillable = ['nombre', 'direccion', 'zona', 'referencia', 'notas', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function cuartos()
    {
        return $this->hasMany(Cuarto::class);
    }
}
```

### `app/Models/Cuarto.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuarto extends Model
{
    protected $table = 'cuartos';
    protected $fillable = [
        'propiedad_id', 'codigo', 'nivel', 'tamano',
        'precio_base', 'estado', 'descripcion', 'activo',
    ];
    protected $casts = [
        'precio_base' => 'decimal:2',
        'activo'      => 'boolean',
    ];

    public const ESTADO_DISPONIBLE    = 'disponible';
    public const ESTADO_OCUPADO       = 'ocupado';
    public const ESTADO_RESERVADO     = 'reservado';
    public const ESTADO_MANTENIMIENTO = 'mantenimiento';

    public static function estados(): array
    {
        return [
            self::ESTADO_DISPONIBLE    => 'Disponible',
            self::ESTADO_OCUPADO       => 'Ocupado',
            self::ESTADO_RESERVADO     => 'Reservado',
            self::ESTADO_MANTENIMIENTO => 'Mantenimiento',
        ];
    }

    public function propiedad() { return $this->belongsTo(Propiedad::class); }

    public function estaDisponible(): bool
    {
        return $this->estado === self::ESTADO_DISPONIBLE;
    }
}
```

## Seeders

### `database/seeders/RolSeeder.php`

```php
<?php
namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['nombre' => 'Administrador', 'codigo' => Rol::COD_ADMIN,     'descripcion' => 'Acceso total al sistema.'],
            ['nombre' => 'Encargado',     'codigo' => Rol::COD_ENCARGADO, 'descripcion' => 'Operación diaria.'],
        ];
        foreach ($roles as $r) {
            Rol::updateOrCreate(['codigo' => $r['codigo']], $r);
        }
    }
}
```

### `database/seeders/UserSeeder.php`

```php
<?php
namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sga.test'],
            [
                'rol_id'            => Rol::where('codigo', Rol::COD_ADMIN)->value('id'),
                'name'              => 'Administrador',
                'password'          => 'admin1234', // se hashea por el cast
                'activo'            => true,
                'email_verified_at' => now(),
            ]
        );
        User::updateOrCreate(
            ['email' => 'encargado@sga.test'],
            [
                'rol_id'            => Rol::where('codigo', Rol::COD_ENCARGADO)->value('id'),
                'name'              => 'Encargado',
                'password'          => 'encargado1234',
                'activo'            => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
```

Registrar en `DatabaseSeeder`:
```php
$this->call([RolSeeder::class, UserSeeder::class]);
```

## Middleware de rol

```bash
php artisan make:middleware RolMiddleware
```

### `app/Http/Middleware/RolMiddleware.php`

```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolMiddleware
{
    public function handle(Request $request, Closure $next, string ...$rolesPermitidos): Response
    {
        $user = $request->user();
        if (!$user || !$user->rol) {
            abort(403, 'Acceso denegado.');
        }
        if (!in_array($user->rol->codigo, $rolesPermitidos, true)) {
            abort(403, 'No tiene permisos para acceder a esta sección.');
        }
        return $next($request);
    }
}
```

Registrar en `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'rol' => \App\Http\Middleware\RolMiddleware::class,
    ]);
})
```

## Policies

```bash
php artisan make:policy PropiedadPolicy --model=Propiedad
php artisan make:policy CuartoPolicy --model=Cuarto
```

### `app/Policies/PropiedadPolicy.php`

```php
<?php
namespace App\Policies;

use App\Models\Propiedad;
use App\Models\User;

class PropiedadPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Propiedad $propiedad): bool { return true; }
    public function create(User $user): bool { return $user->esAdministrador(); }
    public function update(User $user, Propiedad $propiedad): bool { return $user->esAdministrador(); }
    public function delete(User $user, Propiedad $propiedad): bool { return $user->esAdministrador(); }
}
```

(Análoga `CuartoPolicy` — encargado solo `view`/`viewAny`; admin todo).

## Componentes Livewire (SFC)

### Estrategia por módulo

Para cada módulo (propiedades, cuartos, etc.) tendremos:

| Tipo | Namespace | Ruta archivo | Función |
|---|---|---|---|
| Página índice | `pages::propiedades.index` | `resources/views/pages/propiedades/⚡index.blade.php` | URL `/propiedades` |
| Tabla reusable | `propiedades.tabla` | `resources/views/components/propiedades/⚡tabla.blade.php` | Lista paginada, búsqueda, acciones |
| Formulario | `propiedades.form` | `resources/views/components/propiedades/⚡form.blade.php` | Crear / editar dentro de modal |
| Modal eliminar | `propiedades.modal-eliminar` | `resources/views/components/propiedades/⚡modal-eliminar.blade.php` | Confirmación y borrado |

> **El símbolo `⚡` es parte del nombre del archivo en Livewire v4** — lo agrega `make:livewire` automáticamente. No lo escribas a mano.

### Comandos de creación

```bash
# Páginas
php artisan make:livewire pages::propiedades.index
php artisan make:livewire pages::cuartos.tablero

# Componentes reusables
php artisan make:livewire propiedades.tabla
php artisan make:livewire propiedades.form
php artisan make:livewire propiedades.modal-eliminar

php artisan make:livewire cuartos.tabla
php artisan make:livewire cuartos.form
php artisan make:livewire cuartos.modal-eliminar
```

### Ejemplo SFC: `pages::propiedades.index`

`resources/views/pages/propiedades/⚡index.blade.php`:

```blade
<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Propiedades')] class extends Component {
    //
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Propiedades</flux:heading>

        @can('create', App\Models\Propiedad::class)
            <flux:modal.trigger name="form-propiedad">
                <flux:button variant="primary" icon="plus">Nueva propiedad</flux:button>
            </flux:modal.trigger>
        @endcan
    </div>

    {{-- Tabla reusable --}}
    <livewire:propiedades.tabla />

    {{-- Formulario en modal --}}
    <livewire:propiedades.form />

    {{-- Modal de eliminación --}}
    <livewire:propiedades.modal-eliminar />
</div>
```

> **Nota sobre modales en Flux:** los modales se identifican por un `name` único. Los abres con `<flux:modal.trigger name="...">` (botón) o programáticamente desde el componente con `Flux::modal('...')->show()`. Se cierran con `Flux::modal('...')->close()` o desde el botón "Cancelar" interno.

### Ejemplo SFC: `propiedades.tabla`

`resources/views/components/propiedades/⚡tabla.blade.php`:

```blade
<?php

use App\Models\Propiedad;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

new class extends Component {
    use WithPagination;

    public string $busqueda = '';
    public bool $soloActivos = true;

    public function updatingBusqueda(): void { $this->resetPage(); }
    public function updatingSoloActivos(): void { $this->resetPage(); }

    #[On('propiedad-guardada')]
    #[On('propiedad-eliminada')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    public function editar(int $id): void
    {
        $this->dispatch('abrir-form-propiedad', id: $id);
    }

    public function confirmarEliminar(int $id): void
    {
        $this->dispatch('confirmar-eliminar-propiedad', id: $id);
    }

    public function with(): array
    {
        $query = Propiedad::query()
            ->withCount('cuartos')
            ->when($this->busqueda, fn ($q) => $q->where(function ($q) {
                $q->where('nombre', 'like', "%{$this->busqueda}%")
                  ->orWhere('direccion', 'like', "%{$this->busqueda}%");
            }))
            ->when($this->soloActivos, fn ($q) => $q->where('activo', true))
            ->orderBy('nombre');

        return [
            'propiedades' => $query->paginate(15),
        ];
    }
}; ?>

<div>
    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <flux:input
            wire:model.live.debounce.300ms="busqueda"
            icon="magnifying-glass"
            placeholder="Buscar por nombre o dirección..."
            class="flex-1" />

        <flux:switch wire:model.live="soloActivos" label="Solo activos" />
    </div>

    <flux:table :paginate="$propiedades">
        <flux:table.columns>
            <flux:table.column>Nombre</flux:table.column>
            <flux:table.column>Dirección</flux:table.column>
            <flux:table.column>Zona</flux:table.column>
            <flux:table.column align="center">Cuartos</flux:table.column>
            <flux:table.column align="center">Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($propiedades as $propiedad)
                <flux:table.row :key="$propiedad->id">
                    <flux:table.cell class="font-medium">{{ $propiedad->nombre }}</flux:table.cell>
                    <flux:table.cell>{{ $propiedad->direccion }}</flux:table.cell>
                    <flux:table.cell>{{ $propiedad->zona ?? '—' }}</flux:table.cell>
                    <flux:table.cell align="center">{{ $propiedad->cuartos_count }}</flux:table.cell>
                    <flux:table.cell align="center">
                        @if ($propiedad->activo)
                            <flux:badge color="green" size="sm">Activa</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Inactiva</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button
                            href="{{ route('cuartos.tablero', $propiedad) }}"
                            size="xs"
                            variant="ghost">
                            Ver cuartos
                        </flux:button>

                        @can('update', $propiedad)
                            <flux:button wire:click="editar({{ $propiedad->id }})" size="xs" icon="pencil-square" />
                        @endcan
                        @can('delete', $propiedad)
                            <flux:button
                                wire:click="confirmarEliminar({{ $propiedad->id }})"
                                size="xs"
                                variant="danger"
                                icon="trash" />
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500 py-8">
                        No hay propiedades registradas.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
```

### Ejemplo SFC: `propiedades.form`

`resources/views/components/propiedades/⚡form.blade.php`:

```blade
<?php

use App\Models\Propiedad;
use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component {
    public ?int $propiedadId = null;
    public string $nombre = '';
    public string $direccion = '';
    public string $zona = '';
    public string $referencia = '';
    public string $notas = '';
    public bool $activo = true;

    protected function rules(): array
    {
        return [
            'nombre'     => 'required|string|max:150',
            'direccion'  => 'required|string|max:255',
            'zona'       => 'nullable|string|max:50',
            'referencia' => 'nullable|string|max:255',
            'notas'      => 'nullable|string',
            'activo'     => 'boolean',
        ];
    }

    #[On('abrir-form-propiedad')]
    public function abrir(?int $id = null): void
    {
        $this->reset();
        $this->resetValidation();

        if ($id) {
            $p = Propiedad::findOrFail($id);
            $this->authorize('update', $p);
            $this->propiedadId = $p->id;
            $this->nombre      = $p->nombre;
            $this->direccion   = $p->direccion;
            $this->zona        = $p->zona ?? '';
            $this->referencia  = $p->referencia ?? '';
            $this->notas       = $p->notas ?? '';
            $this->activo      = $p->activo;
        } else {
            $this->authorize('create', Propiedad::class);
        }

        Flux::modal('form-propiedad')->show();
    }

    public function guardar(): void
    {
        $datos = $this->validate();

        if ($this->propiedadId) {
            $p = Propiedad::findOrFail($this->propiedadId);
            $this->authorize('update', $p);
            $p->update($datos);
            $mensaje = 'Propiedad actualizada correctamente.';
        } else {
            $this->authorize('create', Propiedad::class);
            Propiedad::create($datos);
            $mensaje = 'Propiedad creada correctamente.';
        }

        Flux::modal('form-propiedad')->close();
        Flux::toast(text: $mensaje, variant: 'success');
        $this->dispatch('propiedad-guardada');
    }

    public function cancelar(): void
    {
        Flux::modal('form-propiedad')->close();
    }
}; ?>

<flux:modal name="form-propiedad" class="md:w-150">
    <form wire:submit="guardar" class="space-y-4">
        <div>
            <flux:heading size="lg">
                {{ $propiedadId ? 'Editar propiedad' : 'Nueva propiedad' }}
            </flux:heading>
            <flux:subheading>
                Datos principales de la casa de alquiler.
            </flux:subheading>
        </div>

        <flux:input
            wire:model="nombre"
            label="Nombre"
            placeholder="Casa central, Casa norte..."
            required />

        <flux:input
            wire:model="direccion"
            label="Dirección"
            required />

        <div class="grid grid-cols-2 gap-3">
            <flux:input wire:model="zona" label="Zona" />
            <flux:input wire:model="referencia" label="Referencia" />
        </div>

        <flux:textarea wire:model="notas" label="Notas" rows="3" />

        <flux:switch wire:model="activo" label="Propiedad activa" />

        <div class="flex gap-2 justify-end">
            <flux:button type="button" wire:click="cancelar" variant="ghost">Cancelar</flux:button>
            <flux:button type="submit" variant="primary">
                <span wire:loading.remove wire:target="guardar">Guardar</span>
                <span wire:loading wire:target="guardar">Guardando...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
```

### Ejemplo SFC: `propiedades.modal-eliminar`

`resources/views/components/propiedades/⚡modal-eliminar.blade.php`:

```blade
<?php

use App\Models\Propiedad;
use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component {
    public ?int $propiedadId = null;
    public ?string $propiedadNombre = null;
    public ?string $error = null;

    #[On('confirmar-eliminar-propiedad')]
    public function abrir(int $id): void
    {
        $p = Propiedad::findOrFail($id);
        $this->authorize('delete', $p);
        $this->propiedadId = $p->id;
        $this->propiedadNombre = $p->nombre;
        $this->error = null;

        Flux::modal('eliminar-propiedad')->show();
    }

    public function eliminar(): void
    {
        $p = Propiedad::findOrFail($this->propiedadId);
        $this->authorize('delete', $p);

        if ($p->cuartos()->exists()) {
            $this->error = 'No se puede eliminar: la propiedad tiene cuartos asociados.';
            return;
        }

        $nombre = $p->nombre;
        $p->delete();
        $this->reset(['propiedadId', 'propiedadNombre', 'error']);

        Flux::modal('eliminar-propiedad')->close();
        Flux::toast(text: "Propiedad '{$nombre}' eliminada.", variant: 'success');
        $this->dispatch('propiedad-eliminada');
    }
}; ?>

<flux:modal name="eliminar-propiedad" class="md:w-125">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Confirmar eliminación</flux:heading>
            <flux:subheading>
                ¿Seguro que deseas eliminar la propiedad <strong>{{ $propiedadNombre }}</strong>?
                Esta acción no se puede deshacer.
            </flux:subheading>
        </div>

        @if ($error)
            <flux:callout color="red" icon="exclamation-triangle">
                {{ $error }}
            </flux:callout>
        @endif

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancelar</flux:button>
            </flux:modal.close>

            @if (!$error)
                <flux:button wire:click="eliminar" variant="danger">
                    <span wire:loading.remove wire:target="eliminar">Eliminar</span>
                    <span wire:loading wire:target="eliminar">Eliminando...</span>
                </flux:button>
            @endif
        </div>
    </div>
</flux:modal>
```

### Sistema de modales y toasts (Flux nativo)

> **Importante:** con Flux **no** necesitas scripts globales para abrir/cerrar modales ni un componente custom de toasts. Flux maneja ambos internamente.

**Modales:**
- Abrir desde un trigger Blade: `<flux:modal.trigger name="form-propiedad"><flux:button>Abrir</flux:button></flux:modal.trigger>`.
- Abrir desde el componente PHP: `Flux::modal('form-propiedad')->show();`.
- Cerrar desde el componente PHP: `Flux::modal('form-propiedad')->close();`.
- Cerrar desde Blade: `<flux:modal.close><flux:button>Cancelar</flux:button></flux:modal.close>`.

**Toasts:**
- Disparar desde el componente PHP: `Flux::toast(text: 'Operación exitosa.', variant: 'success');`.
- Variantes disponibles: `success`, `warning`, `danger`, `info` (default).
- El componente `<flux:toast>` ya está incluido en el layout principal del starter kit. Si no lo encuentras, agrégalo una vez en `resources/views/components/layouts/app.blade.php` antes de `</body>`:
  ```blade
  <flux:toast />
  ```

> **Esto reemplaza al componente custom `toast-global` y al script de modales daisyUI mencionados en versiones previas del plan.**

## Componentes para cuartos

Aplicar el mismo patrón:

```bash
php artisan make:livewire pages::cuartos.tablero
php artisan make:livewire cuartos.tabla
php artisan make:livewire cuartos.form
php artisan make:livewire cuartos.modal-eliminar
php artisan make:livewire cuartos.modal-cambiar-estado
```

### Notas para `pages::cuartos.tablero`
- Recibe `$propiedad` por route binding.
- Layout en grid de tarjetas (`<flux:card>`), no tabla. Cada tarjeta muestra código, precio, estado coloreado.
- Colores de badge según estado (con `<flux:badge>`):
  - `disponible` → `color="green"`
  - `ocupado` → `color="blue"`
  - `reservado` → `color="amber"`
  - `mantenimiento` → `color="red"`
- Filtros por estado y nivel.
- Acciones por tarjeta: editar (admin), cambiar estado a/desde mantenimiento (admin), eliminar (admin si no tiene estancias).

### Notas para `cuartos.form`
- Validación con `Rule::unique('cuartos')->where('propiedad_id', $this->propiedadId)->ignore($this->cuartoId)` para `codigo`.
- Recibe `$propiedadId` al abrir.

### Notas para `cuartos.modal-cambiar-estado`
- Solo permite transiciones manuales: `disponible` ↔ `mantenimiento`. El paso a `ocupado` es automático vía estancia (Fase 2).
- Pide motivo si el destino es `mantenimiento`.

## Rutas

`routes/web.php`:

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', \App\Livewire\Pages\Dashboard::class)->name('dashboard');
        // ↑ esa clase la genera `make:livewire pages::dashboard` cuando llegues a Fase 5

    // Propiedades
    Route::get('/propiedades', App\Livewire\Pages\Propiedades\Index::class)
        ->name('propiedades.index');

    // Cuartos por propiedad
    Route::get('/propiedades/{propiedad}/cuartos', App\Livewire\Pages\Cuartos\Tablero::class)
        ->name('cuartos.tablero');
});
```

> **Importante:** los componentes página de Livewire v4 generan automáticamente clases en el namespace `App\Livewire\Pages\...`. Usa esa clase como handler de la ruta.

## Criterios de aceptación

- ✅ La tabla `roles` existe con sus dos roles seed.
- ✅ La tabla `users` tiene `rol_id` y `activo`. La columna `name` se mantiene como en Fortify.
- ✅ El admin y el encargado pueden iniciar sesión.
- ✅ El admin puede crear/editar/eliminar propiedades. El encargado solo las ve.
- ✅ El admin puede crear/editar/eliminar cuartos. El encargado solo los ve.
- ✅ El tablero muestra los cuartos con colores por estado.
- ✅ No se puede crear un cuarto con `codigo` duplicado dentro de la misma propiedad.
- ✅ No se puede eliminar una propiedad con cuartos.
- ✅ Los modales abren/cierran correctamente con eventos Livewire.
- ✅ Los toasts aparecen al guardar/eliminar.

---

# Fase 2 — Inquilinos y estancias

**Objetivo:** CRUD de inquilinos, apertura y cierre de estancias dentro de transacciones, con cambio automático del estado del cuarto, gestión de extras y validación robusta de disponibilidad.

**Duración:** 1.5 semanas.

## Migraciones

### 2.1 `inquilinos`

```bash
php artisan make:migration create_inquilinos_table
```

```php
Schema::create('inquilinos', function (Blueprint $table) {
    $table->id();
    $table->string('nombres', 100);
    $table->string('apellidos', 100);
    $table->string('dpi', 13)->nullable()->unique();
    $table->string('telefono', 8)->nullable();
    $table->string('email', 150)->nullable();
    $table->enum('ocupacion', ['estudiante', 'salud', 'otro'])->default('otro');
    $table->string('institucion', 150)->nullable();
    $table->string('contacto_emergencia_nombre', 150)->nullable();
    $table->string('contacto_emergencia_telefono', 8)->nullable();
    $table->enum('vehiculo_tipo', ['carro', 'moto'])->nullable();
    $table->string('vehiculo_placa', 20)->nullable();
    $table->text('notas')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['nombres', 'apellidos']);
    $table->index('telefono');
    $table->index('vehiculo_placa');
});
```

### 2.2 `estancias`

```bash
php artisan make:migration create_estancias_table
```

```php
Schema::create('estancias', function (Blueprint $table) {
    $table->id();
    $table->foreignId('inquilino_id')->constrained('inquilinos')->restrictOnDelete();
    $table->foreignId('cuarto_id')->constrained('cuartos')->restrictOnDelete();
    $table->date('fecha_inicio');
    $table->date('fecha_fin')->nullable();
    $table->date('fecha_fin_estimada')->nullable();
    $table->decimal('precio_acordado', 10, 2);
    $table->decimal('deposito', 10, 2)->default(0);
    $table->enum('estado', ['activa', 'finalizada', 'cancelada'])->default('activa');
    $table->string('motivo_cierre', 255)->nullable();
    $table->text('notas')->nullable();
    $table->foreignId('user_registro_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index('estado');
    $table->index(['cuarto_id', 'estado']);
    $table->index(['inquilino_id', 'estado']);
});
```

### 2.3 Constraint de unicidad para estancia activa (MySQL 8)

```bash
php artisan make:migration add_unique_constraint_to_estancias_activas
```

```php
public function up(): void
{
    DB::statement("
        ALTER TABLE estancias
        ADD COLUMN cuarto_activo_id BIGINT UNSIGNED
        GENERATED ALWAYS AS (
            CASE WHEN estado = 'activa' AND deleted_at IS NULL THEN cuarto_id END
        ) VIRTUAL,
        ADD UNIQUE KEY uk_cuarto_activa (cuarto_activo_id)
    ");
}

public function down(): void
{
    DB::statement("ALTER TABLE estancias DROP INDEX uk_cuarto_activa");
    DB::statement("ALTER TABLE estancias DROP COLUMN cuarto_activo_id");
}
```

### 2.4 `extras_estancia`

```bash
php artisan make:migration create_extras_estancia_table
```

```php
Schema::create('extras_estancia', function (Blueprint $table) {
    $table->id();
    $table->foreignId('estancia_id')->constrained('estancias')->cascadeOnDelete();
    $table->string('descripcion', 150);
    $table->decimal('monto', 10, 2);
    $table->enum('periodicidad', ['unico', 'mensual'])->default('mensual');
    $table->timestamps();
});
```

## Modelos

### `app/Models/Inquilino.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inquilino extends Model
{
    use SoftDeletes;

    protected $table = 'inquilinos';
    protected $fillable = [
        'nombres','apellidos','dpi','telefono','email','ocupacion',
        'institucion','contacto_emergencia_nombre','contacto_emergencia_telefono',
        'vehiculo_tipo','vehiculo_placa','notas',
    ];

    public const VEHICULO_CARRO = 'carro';
    public const VEHICULO_MOTO  = 'moto';

    public function estancias() { return $this->hasMany(Estancia::class); }
    public function estanciaActiva() { return $this->hasOne(Estancia::class)->where('estado', 'activa'); }

    public function tieneVehiculo(): bool { return !is_null($this->vehiculo_tipo); }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }
}
```

### `app/Models/Estancia.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estancia extends Model
{
    use SoftDeletes;

    protected $table = 'estancias';
    protected $fillable = [
        'inquilino_id','cuarto_id','fecha_inicio','fecha_fin','fecha_fin_estimada',
        'precio_acordado','deposito','estado','motivo_cierre','notas','user_registro_id',
    ];
    protected $casts = [
        'fecha_inicio'       => 'date',
        'fecha_fin'          => 'date',
        'fecha_fin_estimada' => 'date',
        'precio_acordado'    => 'decimal:2',
        'deposito'           => 'decimal:2',
    ];

    public const ESTADO_ACTIVA     = 'activa';
    public const ESTADO_FINALIZADA = 'finalizada';
    public const ESTADO_CANCELADA  = 'cancelada';

    public function inquilino()    { return $this->belongsTo(Inquilino::class); }
    public function cuarto()       { return $this->belongsTo(Cuarto::class); }
    public function userRegistro() { return $this->belongsTo(User::class, 'user_registro_id'); }
    public function extras()       { return $this->hasMany(ExtraEstancia::class); }
    public function pagos()        { return $this->hasMany(Pago::class); }

    public function estaActiva(): bool { return $this->estado === self::ESTADO_ACTIVA; }
}
```

### `app/Models/ExtraEstancia.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtraEstancia extends Model
{
    protected $table = 'extras_estancia';
    protected $fillable = ['estancia_id','descripcion','monto','periodicidad'];
    protected $casts = ['monto' => 'decimal:2'];

    public const PERIODICIDAD_UNICO   = 'unico';
    public const PERIODICIDAD_MENSUAL = 'mensual';

    public function estancia() { return $this->belongsTo(Estancia::class); }
}
```

## Service: `EstanciaService`

### `app/Services/EstanciaService.php`

```php
<?php
namespace App\Services;

use App\Models\Cuarto;
use App\Models\Estancia;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EstanciaService
{
    public function abrir(array $datos, array $extras = [], ?int $userId = null): Estancia
    {
        return DB::transaction(function () use ($datos, $extras, $userId) {
            $cuarto = Cuarto::lockForUpdate()->findOrFail($datos['cuarto_id']);

            if (!$cuarto->estaDisponible()) {
                throw new RuntimeException("El cuarto no está disponible (estado actual: {$cuarto->estado}).");
            }

            $estancia = Estancia::create([
                'inquilino_id'        => $datos['inquilino_id'],
                'cuarto_id'           => $cuarto->id,
                'fecha_inicio'        => $datos['fecha_inicio'],
                'fecha_fin_estimada'  => $datos['fecha_fin_estimada'] ?? null,
                'precio_acordado'     => $datos['precio_acordado'],
                'deposito'            => $datos['deposito'] ?? 0,
                'estado'              => Estancia::ESTADO_ACTIVA,
                'notas'               => $datos['notas'] ?? null,
                'user_registro_id'    => $userId,
            ]);

            foreach ($extras as $extra) {
                $estancia->extras()->create($extra);
            }

            $cuarto->update(['estado' => Cuarto::ESTADO_OCUPADO]);

            return $estancia->load(['inquilino','cuarto','extras']);
        });
    }

    public function cerrar(Estancia $estancia, string $fechaFin, ?string $motivo = null): Estancia
    {
        return DB::transaction(function () use ($estancia, $fechaFin, $motivo) {
            if (!$estancia->estaActiva()) {
                throw new RuntimeException('Solo se pueden cerrar estancias activas.');
            }

            $estancia->update([
                'fecha_fin'     => $fechaFin,
                'estado'        => Estancia::ESTADO_FINALIZADA,
                'motivo_cierre' => $motivo,
            ]);

            $estancia->cuarto->update(['estado' => Cuarto::ESTADO_DISPONIBLE]);

            return $estancia->fresh(['cuarto','inquilino']);
        });
    }

    public function cancelar(Estancia $estancia, string $motivo): Estancia
    {
        return DB::transaction(function () use ($estancia, $motivo) {
            if (!$estancia->estaActiva()) {
                throw new RuntimeException('Solo se pueden cancelar estancias activas.');
            }

            $estancia->update([
                'estado'        => Estancia::ESTADO_CANCELADA,
                'motivo_cierre' => $motivo,
            ]);

            $estancia->cuarto->update(['estado' => Cuarto::ESTADO_DISPONIBLE]);

            return $estancia->fresh(['cuarto','inquilino']);
        });
    }
}
```

## Componentes Livewire (SFC) para esta fase

```bash
# Páginas
php artisan make:livewire pages::inquilinos.index
php artisan make:livewire pages::inquilinos.detalle
php artisan make:livewire pages::estancias.index
php artisan make:livewire pages::estancias.detalle

# Componentes reusables - inquilinos
php artisan make:livewire inquilinos.tabla
php artisan make:livewire inquilinos.form
php artisan make:livewire inquilinos.modal-eliminar

# Componentes reusables - estancias
php artisan make:livewire estancias.tabla
php artisan make:livewire estancias.form-abrir
php artisan make:livewire estancias.form-cerrar
php artisan make:livewire estancias.repeater-extras
php artisan make:livewire estancias.modal-cancelar
```

### Notas clave

- **`inquilinos.form`**: incluye campo de búsqueda `select2-like` para encontrar inquilinos existentes (Livewire `wire:model.live.debounce` + lista filtrada). En la página de "abrir estancia" se reusa este form en modo modal embebido para "+ Nuevo inquilino".
- **`estancias.form-abrir`**: orquesta el inicio de estancia. Llama al `EstanciaService` y captura `RuntimeException` para mostrar errores amigables (ej. "El cuarto ya no está disponible").
- **`estancias.repeater-extras`**: componente hijo que gestiona la lista de extras. Emite eventos al padre cuando se agrega/elimina un extra. Internamente mantiene un array `public $items = []` con métodos `agregar()` y `quitar($i)`.
- **Selector de cuartos disponibles**: en `estancias.form-abrir`, listar solo `Cuarto::where('estado', Cuarto::ESTADO_DISPONIBLE)->where('activo', true)`, agrupados por `propiedad`.

### Patrón de validación en `inquilinos.form`

```php
use Illuminate\Validation\Rule;

protected function rules(): array
{
    return [
        'nombres'   => 'required|string|max:100',
        'apellidos' => 'required|string|max:100',
        'dpi'       => [
            'nullable', 'string', 'max:13',
            Rule::unique('inquilinos','dpi')
                ->ignore($this->inquilinoId)
                ->whereNull('deleted_at'),
        ],
        'telefono'  => 'nullable|string|max:8',
        'email'     => 'nullable|email|max:150',
        'ocupacion' => 'required|in:estudiante,salud,otro',
        'institucion' => 'nullable|string|max:150',
    ];
}
```

### Llamada al service desde el componente

En `estancias.form-abrir`:

```php
public function guardar(EstanciaService $service): void
{
    $datos = $this->validate();

    try {
        $estancia = $service->abrir(
            datos: $datos,
            extras: $this->extras,
            userId: auth()->id()
        );

        Flux::modal('abrir-estancia')->close();
        Flux::toast(text: "Estancia #{$estancia->id} abierta correctamente.", variant: 'success');
        $this->dispatch('estancia-abierta', id: $estancia->id);
        $this->redirect(route('estancias.detalle', $estancia), navigate: true);
    } catch (\RuntimeException $e) {
        $this->addError('cuarto_id', $e->getMessage());
    }
}
```

## Policies

```bash
php artisan make:policy InquilinoPolicy --model=Inquilino
php artisan make:policy EstanciaPolicy --model=Estancia
```

- **Inquilino:** todos los autenticados pueden `viewAny`, `view`, `create`, `update`. Solo admin: `delete`.
- **Estancia:** todos los autenticados pueden `viewAny`, `view`, `create` (abrir), `update` (cerrar). Solo admin: `delete` y `cancelar`.

## Rutas

```php
Route::middleware('auth')->group(function () {
    // Inquilinos
    Route::get('/inquilinos', App\Livewire\Pages\Inquilinos\Index::class)
        ->name('inquilinos.index');
    Route::get('/inquilinos/{inquilino}', App\Livewire\Pages\Inquilinos\Detalle::class)
        ->name('inquilinos.detalle');

    // Estancias
    Route::get('/estancias', App\Livewire\Pages\Estancias\Index::class)
        ->name('estancias.index');
    Route::get('/estancias/{estancia}', App\Livewire\Pages\Estancias\Detalle::class)
        ->name('estancias.detalle');
});
```

## Criterios de aceptación

- ✅ Se puede abrir una estancia y el cuarto pasa a `ocupado`.
- ✅ No se pueden abrir dos estancias activas para el mismo cuarto (BD lo rechaza).
- ✅ Si dos usuarios intentan abrir simultáneamente, solo uno tiene éxito.
- ✅ Se puede cerrar una estancia y el cuarto vuelve a `disponible`.
- ✅ Los extras se guardan asociados y se muestran en el detalle.
- ✅ El historial de estancias del inquilino muestra todas las estancias.
- ✅ Validación de DPI único respeta soft deletes.
- ✅ El selector de cuartos solo muestra los disponibles.

---

# Fase 3 — Ingresos (pagos y parqueo externo)

**Objetivo:** registrar pagos asociados a estancias activas con tipos diferenciados (anticipo / mensualidad / extra / depósito) desde catálogo `tipos_pago`, descuentos manuales con motivo, mes aplicado y método de pago. Además, gestionar el alquiler de parqueo a personas externas (que no rentan cuartos) con su propio flujo independiente. Generación de recibo PDF se prepara aquí (plantilla completa en Fase 5).

**Duración:** 1.5 semanas.

**Trabajo por bloques (orden estricto):**
1. Migraciones (`tipos_pago`, `pagos`, `arrendatarios_parqueo`, `alquileres_parqueo`).
2. Modelos (`TipoPago`, `Pago`, `ArrendatarioParqueo`, `AlquilerParqueo`).
3. Seeders (`TipoPagoSeeder`).
4. Services (`PagoService`, `AlquilerParqueoService`).
5. Policies.
6. Componentes Livewire SFC (módulo pagos + módulo parqueo externo).
7. Rutas.

> **Sobre parqueo externo:** los inquilinos que rentan cuartos ya incluyen parqueo, pero también se alquila el parqueo por mes a personas externas (típicamente doctores). Ese flujo es independiente del de cuartos: tabla propia, sin recibo correlativo, sin afectar el flujo de caja principal. Se reporta aparte en Fase 5.

---

## Bloque 1 — Migraciones

### 3.1 `tipos_pago` (catálogo)

```bash
php artisan make:migration create_tipos_pago_table
```

```php
Schema::create('tipos_pago', function (Blueprint $table) {
    $table->id();
    $table->string('nombre', 80)->unique();           // "Mensualidad", "Anticipo"
    $table->string('codigo', 30)->unique();           // 'mensualidad', 'anticipo'
    $table->boolean('requiere_mes')->default(false);  // solo 'mensualidad' = true
    $table->boolean('activo')->default(true);
    $table->timestamps();
});
```

### 3.2 `pagos`

```bash
php artisan make:migration create_pagos_table
```

```php
Schema::create('pagos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('estancia_id')->constrained('estancias')->restrictOnDelete();
    $table->foreignId('tipo_pago_id')->constrained('tipos_pago')->restrictOnDelete();
    $table->date('fecha_pago');
    $table->date('mes_aplicado')->nullable();
    $table->decimal('monto_bruto', 10, 2);
    $table->decimal('descuento', 10, 2)->default(0);
    $table->string('motivo_descuento', 255)->nullable();
    $table->decimal('monto_neto', 10, 2);
    $table->enum('metodo_pago', ['efectivo', 'cuenta'])->default('efectivo');
    $table->string('referencia', 100)->nullable();
    $table->string('recibo_numero', 50)->nullable()->unique();
    $table->text('notas')->nullable();
    $table->foreignId('user_registro_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['estancia_id', 'fecha_pago']);
    $table->index('mes_aplicado');
    $table->index(['tipo_pago_id', 'fecha_pago']);
});
```

> **Nota sobre PDFs:** no almacenamos `recibo_pdf_path` porque los recibos se generan **on-demand** desde `ReciboPdfService` cuando el usuario los descarga. Los datos del pago son inmutables (`monto_bruto`, `descuento`, `monto_neto`, `recibo_numero`), así que regenerar el PDF mañana o dentro de 5 años produce un archivo idéntico al que se entregó. Esto evita llenar disco con miles de PDFs y simplifica los backups.

> **Nota sobre parqueo:** la tabla `pagos` **no** lleva FK a `alquileres_parqueo`. El alquiler de parqueo a externos tiene su propia tabla independiente — se considera ingreso extra y se reporta por separado.

### 3.3 `arrendatarios_parqueo`

```bash
php artisan make:migration create_arrendatarios_parqueo_table
```

```php
Schema::create('arrendatarios_parqueo', function (Blueprint $table) {
    $table->id();
    $table->string('nombre_completo', 150);
    $table->string('telefono', 8)->nullable();
    $table->enum('ocupacion', ['estudiante', 'salud', 'otro'])->default('otro');
    $table->string('placa', 20)->nullable();
    $table->boolean('activo')->default(true);
    $table->text('notas')->nullable();
    $table->timestamps();

    $table->index('nombre_completo');
    $table->index('placa');
    $table->index('activo');
});
```

> Sin soft deletes — el flag `activo` cubre el caso "ya no renta parqueo pero queda en sistema".

### 3.4 `alquileres_parqueo`

```bash
php artisan make:migration create_alquileres_parqueo_table
```

```php
Schema::create('alquileres_parqueo', function (Blueprint $table) {
    $table->id();
    $table->foreignId('arrendatario_parqueo_id')
          ->constrained('arrendatarios_parqueo')
          ->restrictOnDelete();
    $table->date('mes');                       // siempre día 1 del mes
    $table->decimal('monto', 10, 2);           // manual cada mes (típicamente Q50-Q200)
    $table->boolean('pagado')->default(false);
    $table->date('fecha_pago')->nullable();    // cuándo se marcó pagado
    $table->enum('metodo_pago', ['efectivo', 'cuenta'])->default('efectivo');
    $table->text('notas')->nullable();         // detalles del carro/moto si hace falta
    $table->foreignId('user_registro_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();

    $table->index(['arrendatario_parqueo_id', 'mes']);
    $table->index('mes');
    $table->index('pagado');
});
```

> Un arrendatario puede tener múltiples registros del mismo mes (caso raro: renta 2 espacios). No se aplica unique constraint sobre `(arrendatario_parqueo_id, mes)`.

---

## Bloque 2 — Modelos

### `app/Models/TipoPago.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPago extends Model
{
    protected $table = 'tipos_pago';
    protected $fillable = ['nombre', 'codigo', 'requiere_mes', 'activo'];
    protected $casts = [
        'requiere_mes' => 'boolean',
        'activo'       => 'boolean',
    ];

    public const COD_ANTICIPO    = 'anticipo';
    public const COD_MENSUALIDAD = 'mensualidad';
    public const COD_EXTRA       = 'extra';
    public const COD_DEPOSITO    = 'deposito';

    public function pagos() { return $this->hasMany(Pago::class); }

    public function scopeActivos($query) { return $query->where('activo', true); }
}
```

### `app/Models/Pago.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pago extends Model
{
    use SoftDeletes;

    protected $table = 'pagos';
    protected $fillable = [
        'estancia_id','tipo_pago_id','fecha_pago','mes_aplicado',
        'monto_bruto','descuento','motivo_descuento','monto_neto',
        'metodo_pago','referencia','recibo_numero',
        'notas','user_registro_id',
    ];
    protected $casts = [
        'fecha_pago'   => 'date',
        'mes_aplicado' => 'date',
        'monto_bruto'  => 'decimal:2',
        'descuento'    => 'decimal:2',
        'monto_neto'   => 'decimal:2',
    ];

    public const METODO_EFECTIVO = 'efectivo';
    public const METODO_CUENTA   = 'cuenta';

    public function estancia()    { return $this->belongsTo(Estancia::class); }
    public function tipoPago()    { return $this->belongsTo(TipoPago::class); }
    public function userRegistro(){ return $this->belongsTo(User::class, 'user_registro_id'); }
}
```

### `app/Models/ArrendatarioParqueo.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArrendatarioParqueo extends Model
{
    protected $table = 'arrendatarios_parqueo';
    protected $fillable = [
        'nombre_completo','telefono','ocupacion','placa','activo','notas',
    ];
    protected $casts = ['activo' => 'boolean'];

    public const OCUPACION_ESTUDIANTE = 'estudiante';
    public const OCUPACION_SALUD      = 'salud';
    public const OCUPACION_OTRO       = 'otro';

    public function alquileres() { return $this->hasMany(AlquilerParqueo::class); }

    public function scopeActivos($query) { return $query->where('activo', true); }
}
```

### `app/Models/AlquilerParqueo.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlquilerParqueo extends Model
{
    protected $table = 'alquileres_parqueo';
    protected $fillable = [
        'arrendatario_parqueo_id','mes','monto','pagado','fecha_pago',
        'metodo_pago','notas','user_registro_id',
    ];
    protected $casts = [
        'mes'        => 'date',
        'fecha_pago' => 'date',
        'monto'      => 'decimal:2',
        'pagado'     => 'boolean',
    ];

    public const METODO_EFECTIVO = 'efectivo';
    public const METODO_CUENTA   = 'cuenta';

    public function arrendatario()
    {
        return $this->belongsTo(ArrendatarioParqueo::class, 'arrendatario_parqueo_id');
    }

    public function userRegistro() { return $this->belongsTo(User::class, 'user_registro_id'); }
}
```

---

## Bloque 3 — Seeder

### `database/seeders/TipoPagoSeeder.php`

```bash
php artisan make:seeder TipoPagoSeeder
```

```php
<?php
namespace Database\Seeders;

use App\Models\TipoPago;
use Illuminate\Database\Seeder;

class TipoPagoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['codigo' => 'anticipo',    'nombre' => 'Anticipo',    'requiere_mes' => false],
            ['codigo' => 'mensualidad', 'nombre' => 'Mensualidad', 'requiere_mes' => true],
            ['codigo' => 'extra',       'nombre' => 'Extra',       'requiere_mes' => false],
            ['codigo' => 'deposito',    'nombre' => 'Depósito',    'requiere_mes' => false],
        ];

        foreach ($tipos as $t) {
            TipoPago::updateOrCreate(['codigo' => $t['codigo']], $t + ['activo' => true]);
        }
    }
}
```

Registrar en `DatabaseSeeder::run()`:

```php
$this->call([
    RolSeeder::class,
    TipoPagoSeeder::class,
    // ...
]);
```

---

## Bloque 4 — Services

### `app/Services/PagoService.php`

```php
<?php
namespace App\Services;

use App\Models\Estancia;
use App\Models\Pago;
use App\Models\TipoPago;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PagoService
{
    public function registrar(array $datos, ?int $userId = null): Pago
    {
        return DB::transaction(function () use ($datos, $userId) {
            $estancia = Estancia::lockForUpdate()->findOrFail($datos['estancia_id']);
            $tipoPago = TipoPago::findOrFail($datos['tipo_pago_id']);

            if ($tipoPago->requiere_mes && empty($datos['mes_aplicado'])) {
                throw new RuntimeException("El tipo de pago '{$tipoPago->nombre}' requiere mes aplicado.");
            }

            $bruto     = round((float) $datos['monto_bruto'], 2);
            $descuento = round((float) ($datos['descuento'] ?? 0), 2);

            if ($descuento > 0 && empty($datos['motivo_descuento'])) {
                throw new RuntimeException('El descuento requiere un motivo.');
            }
            if ($descuento > $bruto) {
                throw new RuntimeException('El descuento no puede exceder el monto bruto.');
            }

            $neto = round($bruto - $descuento, 2);

            $pago = Pago::create([
                'estancia_id'      => $estancia->id,
                'tipo_pago_id'     => $tipoPago->id,
                'fecha_pago'       => $datos['fecha_pago'],
                'mes_aplicado'     => $tipoPago->requiere_mes ? $datos['mes_aplicado'] : null,
                'monto_bruto'      => $bruto,
                'descuento'        => $descuento,
                'motivo_descuento' => $descuento > 0 ? $datos['motivo_descuento'] : null,
                'monto_neto'       => $neto,
                'metodo_pago'      => $datos['metodo_pago'],
                'referencia'       => $datos['referencia'] ?? null,
                'recibo_numero'    => $this->generarReciboNumero(),
                'notas'            => $datos['notas'] ?? null,
                'user_registro_id' => $userId,
            ]);

            // El PDF NO se genera ni se persiste aquí.
            // Se genera on-demand desde ReciboPdfService cuando el usuario hace clic en "Descargar".

            return $pago;
        });
    }

    public function eliminar(Pago $pago): void
    {
        // Soft delete simple. No hay archivo físico que limpiar (los PDFs no se persisten).
        $pago->delete();
    }

    protected function generarReciboNumero(): string
    {
        $year = now()->year;
        $ultimo = Pago::where('recibo_numero', 'like', "REC-{$year}-%")
                      ->orderByDesc('id')
                      ->value('recibo_numero');
        $secuencial = $ultimo ? ((int) substr($ultimo, -6)) + 1 : 1;
        return sprintf('REC-%d-%06d', $year, $secuencial);
    }
}
```

### `app/Services/AlquilerParqueoService.php`

```php
<?php
namespace App\Services;

use App\Models\AlquilerParqueo;
use App\Models\ArrendatarioParqueo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AlquilerParqueoService
{
    public function registrarMes(array $datos, ?int $userId = null): AlquilerParqueo
    {
        return DB::transaction(function () use ($datos, $userId) {
            $arrendatario = ArrendatarioParqueo::findOrFail($datos['arrendatario_parqueo_id']);

            if (!$arrendatario->activo) {
                throw new RuntimeException('No se puede registrar un mes a un arrendatario inactivo.');
            }

            $monto = round((float) $datos['monto'], 2);
            if ($monto <= 0) {
                throw new RuntimeException('El monto debe ser mayor a cero.');
            }

            $mes = Carbon::parse($datos['mes'])->startOfMonth()->format('Y-m-d');

            return AlquilerParqueo::create([
                'arrendatario_parqueo_id' => $arrendatario->id,
                'mes'                     => $mes,
                'monto'                   => $monto,
                'pagado'                  => $datos['pagado'] ?? false,
                'fecha_pago'              => ($datos['pagado'] ?? false) ? ($datos['fecha_pago'] ?? now()) : null,
                'metodo_pago'             => $datos['metodo_pago'] ?? AlquilerParqueo::METODO_EFECTIVO,
                'notas'                   => $datos['notas'] ?? null,
                'user_registro_id'        => $userId,
            ]);
        });
    }

    public function marcarPagado(AlquilerParqueo $alquiler, string $metodoPago, ?string $fechaPago = null): void
    {
        $alquiler->update([
            'pagado'      => true,
            'fecha_pago'  => $fechaPago ?? now()->toDateString(),
            'metodo_pago' => $metodoPago,
        ]);
    }

    public function marcarPendiente(AlquilerParqueo $alquiler): void
    {
        $alquiler->update([
            'pagado'     => false,
            'fecha_pago' => null,
        ]);
    }

    public function desactivarArrendatario(ArrendatarioParqueo $arrendatario): void
    {
        $arrendatario->update(['activo' => false]);
    }

    public function activarArrendatario(ArrendatarioParqueo $arrendatario): void
    {
        $arrendatario->update(['activo' => true]);
    }
}
```

---

## Bloque 5 — Policies

```bash
php artisan make:policy PagoPolicy --model=Pago
php artisan make:policy ArrendatarioParqueoPolicy --model=ArrendatarioParqueo
php artisan make:policy AlquilerParqueoPolicy --model=AlquilerParqueo
```

- **PagoPolicy:**
  - `viewAny`, `view`, `create`: ambos roles.
  - `update`: solo admin (no se editan; se anulan + crean nuevo).
  - `delete`: solo admin.
- **ArrendatarioParqueoPolicy:**
  - `viewAny`, `view`, `create`, `update`: ambos roles.
  - `delete`: solo admin (en la práctica se desactiva, no se elimina).
- **AlquilerParqueoPolicy:**
  - `viewAny`, `view`, `create`, `update`: ambos roles.
  - `delete`: solo admin.

---

## Bloque 6 — Componentes Livewire (SFC)

### Módulo pagos

```bash
# Páginas
php artisan make:livewire pages::pagos.index
php artisan make:livewire pages::pagos.registrar

# Componentes reusables
php artisan make:livewire pagos.tabla
php artisan make:livewire pagos.form
php artisan make:livewire pagos.historial-estancia
php artisan make:livewire pagos.modal-eliminar
```

#### Notas clave para `pagos.form`

- Campos:
  - Selector de estancia activa (con info contextual: inquilino + cuarto + casa).
  - `fecha_pago` (default: hoy).
  - `tipo_pago_id` — radios o select cargados desde `TipoPago::activos()->get()`.
  - `mes_aplicado` visible solo si el tipo seleccionado tiene `requiere_mes = true` (lookup reactivo).
  - `monto_bruto`, `descuento`, `motivo_descuento` (condicional).
  - `monto_neto` calculado reactivamente:
    ```php
    use Livewire\Attributes\Computed;

    #[Computed]
    public function montoNeto(): float
    {
        return max(0, round((float)$this->monto_bruto - (float)$this->descuento, 2));
    }

    #[Computed]
    public function tipoRequiereMes(): bool
    {
        return $this->tipo_pago_id
            ? (bool) \App\Models\TipoPago::find($this->tipo_pago_id)?->requiere_mes
            : false;
    }
    ```
  - `metodo_pago` (radio).
  - `referencia` visible solo si método = `cuenta`.
- Validación:
  ```php
  protected function rules(): array
  {
      return [
          'estancia_id'      => 'required|exists:estancias,id',
          'tipo_pago_id'     => 'required|exists:tipos_pago,id',
          'fecha_pago'       => 'required|date',
          'mes_aplicado'     => 'nullable|date',
          'monto_bruto'      => 'required|numeric|min:0.01',
          'descuento'        => 'nullable|numeric|min:0|lte:monto_bruto',
          'motivo_descuento' => 'required_if:descuento,>,0|nullable|string|max:255',
          'metodo_pago'      => 'required|in:efectivo,cuenta',
          'referencia'       => 'nullable|string|max:100',
      ];
  }
  ```
  La validación de `mes_aplicado` cuando el tipo lo requiere se hace en `PagoService` (fuente única de verdad).
- Al hacer set de `mes_aplicado`, normalizar al día 1: `Carbon::parse($value)->startOfMonth()->format('Y-m-d')`.

#### `pagos.historial-estancia`

Componente embebible:
```blade
<livewire:pagos.historial-estancia :estancia-id="$estancia->id" />
```
Muestra los pagos agrupados por `mes_aplicado`, con totales por mes y total general.

### Módulo parqueo externo

```bash
# Páginas
php artisan make:livewire pages::parqueo.index
php artisan make:livewire pages::parqueo.detalle

# Componentes reusables
php artisan make:livewire parqueo.tabla-arrendatarios
php artisan make:livewire parqueo.form-arrendatario
php artisan make:livewire parqueo.tabla-meses
php artisan make:livewire parqueo.form-mes
php artisan make:livewire parqueo.modal-marcar-pagado
php artisan make:livewire parqueo.modal-eliminar-mes
```

#### `pages::parqueo.index`

- Tabla de arrendatarios (activos + inactivos con filtro).
- Columnas: nombre, teléfono, ocupación, placa, estado (activo/inactivo), último mes registrado, acciones.
- Botón "+ Nuevo arrendatario" abre modal con `parqueo.form-arrendatario`.
- Click en fila → `pages::parqueo.detalle` con historial mensual.

#### `pages::parqueo.detalle`

- Datos del arrendatario en header.
- Tabla mensual (`parqueo.tabla-meses`): mes, monto, pagado/pendiente, método, fecha pago, acciones.
- Botón "+ Registrar mes" abre modal con `parqueo.form-mes`.
- Acción rápida "Marcar pagado" desde la fila pendiente.

#### `parqueo.form-arrendatario`

Validación:
```php
protected function rules(): array
{
    return [
        'nombre_completo' => 'required|string|max:150',
        'telefono'        => 'nullable|string|max:8',
        'ocupacion'       => 'required|in:estudiante,salud,otro',
        'placa'           => 'nullable|string|max:20',
        'activo'          => 'boolean',
        'notas'           => 'nullable|string',
    ];
}
```

#### `parqueo.form-mes`

Validación:
```php
protected function rules(): array
{
    return [
        'arrendatario_parqueo_id' => 'required|exists:arrendatarios_parqueo,id',
        'mes'                     => 'required|date',
        'monto'                   => 'required|numeric|min:0.01',
        'pagado'                  => 'boolean',
        'fecha_pago'              => 'nullable|date|required_if:pagado,true',
        'metodo_pago'             => 'required|in:efectivo,cuenta',
        'notas'                   => 'nullable|string',
    ];
}
```

---

## Bloque 7 — Rutas

```php
Route::middleware('auth')->group(function () {
    // Pagos
    Route::get('/pagos', App\Livewire\Pages\Pagos\Index::class)->name('pagos.index');
    Route::get('/pagos/registrar', App\Livewire\Pages\Pagos\Registrar::class)
        ->name('pagos.registrar');

    // Parqueo externo
    Route::get('/parqueo', App\Livewire\Pages\Parqueo\Index::class)->name('parqueo.index');
    Route::get('/parqueo/{arrendatario}', App\Livewire\Pages\Parqueo\Detalle::class)
        ->name('parqueo.detalle');
});
```

## Criterios de aceptación

**Pagos (cuartos):**
- ✅ Se registra un pago asociado a una estancia activa.
- ✅ El tipo de pago se selecciona desde el catálogo `tipos_pago`.
- ✅ Si el tipo seleccionado tiene `requiere_mes = true` y no se manda mes, falla.
- ✅ El monto neto se calcula reactivamente en la UI.
- ✅ Sin motivo de descuento (cuando hay descuento), falla la validación.
- ✅ Si descuento > bruto, falla.
- ✅ Correlativo de recibo único y secuencial por año.
- ✅ Historial por estancia muestra agrupación por mes.
- ✅ Solo admin puede eliminar pagos.

**Parqueo externo:**
- ✅ Se crea un arrendatario con datos mínimos (nombre, teléfono, placa opcional).
- ✅ Se registra un mes con monto manual (típicamente Q50-Q200).
- ✅ Un arrendatario puede tener múltiples meses registrados; también múltiples registros del mismo mes (caso 2 espacios).
- ✅ "Marcar pagado" actualiza `pagado=true` y graba `fecha_pago`.
- ✅ Desactivar arrendatario impide registrar nuevos meses pero conserva su historial.
- ✅ Los pagos de parqueo **no** aparecen en el flujo de caja principal (Fase 5) — solo en su reporte propio.

---

# Fase 4 — Egresos (gastos) ✅ COMPLETADA

> **Estado:** Completada (2026-07-08). Bloques 1-7 implementados y verificados; todos los criterios de aceptación se cumplen. **Bloque 8 (tests) pendiente** — omitido por decisión del desarrollador y además bloqueado por un bug pre-existente de Fase 2 (ver nota al final de esta sección).
>
> **Extensiones sobre el plan original (aprobadas):**
> - `categorias_gasto` incluye `requiere_propiedad` y `requiere_cuarto` (bool). El form de gastos aplica validación dinámica: si la categoría los requiere, propiedad/cuarto pasan a obligatorios. `requiere_cuarto` fuerza `requiere_propiedad`.
> - Se agregó `categorias-gasto.modal-eliminar` (regla 6 CLAUDE.md) con guard "no eliminable si tiene gastos".
> - Se agregó `gastos.modal-detalle` (ver todos los datos + notas + comprobante). La columna "Comprobante" se quitó de la tabla; el comprobante se ve desde el detalle.
> - Filtros de fecha en `gastos.tabla` son diferidos, con botones **Buscar** y **Limpiar**.
> - El visor PDF (`components::visor-pdf`) se construyó genérico con Flux (no se portó de expedientes-codede). Montado 1 vez en `layouts/app/sidebar.blade.php`. Escucha `abrir-visor-pdf` con `url`/`titulo`/`descargaUrl`/`esImagen`.
> - Sin `GastoService` — el registro es insert de una sola tabla; el form orquesta upload + create.
>
> **Nota de storage (Laravel 11+):** el disco `local` apunta a `storage/app/private`. Los comprobantes se guardan en `storage/app/private/comprobantes/` (no `storage/app/comprobantes/`). Carpeta versionada vía `.gitignore` interno; los archivos subidos quedan ignorados por git.
>
> **BLOQUEO para tests (pre-existente, Fase 2):** la migración `2026_05_09_182931_add_unique_constraint_to_estancias_activas` usa SQL solo-MySQL (`GENERATED ALWAYS AS ... VIRTUAL, ADD UNIQUE KEY`). La suite corre en SQLite `:memory:` → `migrate:fresh` falla → 32/33 tests existentes fallan. Resolver antes de escribir/correr tests de Fase 4 (envolver el `DB::statement` en `if (DB::getDriverName() === 'mysql')`, o usar MySQL de test).

**Objetivo:** CRUD de categorías de gastos y registro de gastos operativos con asociación opcional a propiedad y/o cuarto.

**Duración:** 4-5 días.

## Migraciones

### 4.1 `categorias_gasto`

```bash
php artisan make:migration create_categorias_gasto_table
```

```php
Schema::create('categorias_gasto', function (Blueprint $table) {
    $table->id();
    $table->string('nombre', 80)->unique();
    $table->string('codigo', 20)->nullable()->unique();
    $table->string('descripcion', 255)->nullable();
    $table->boolean('activo')->default(true);
    $table->timestamps();
});
```

### 4.2 `gastos`

```bash
php artisan make:migration create_gastos_table
```

```php
Schema::create('gastos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('categoria_gasto_id')->constrained('categorias_gasto')->restrictOnDelete();
    $table->foreignId('propiedad_id')->nullable()->constrained('propiedades')->nullOnDelete();
    $table->foreignId('cuarto_id')->nullable()->constrained('cuartos')->nullOnDelete();
    $table->date('fecha');
    $table->decimal('monto', 10, 2);
    $table->string('descripcion', 255);
    $table->string('proveedor', 150)->nullable();
    $table->enum('metodo_pago', ['efectivo', 'cuenta'])->default('efectivo');
    $table->string('comprobante_path', 255)->nullable();
    $table->text('notas')->nullable();
    $table->foreignId('user_registro_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['fecha', 'categoria_gasto_id']);
    $table->index('propiedad_id');
});
```

## Modelos

### `app/Models/CategoriaGasto.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaGasto extends Model
{
    protected $table = 'categorias_gasto';
    protected $fillable = ['nombre','codigo','descripcion','activo'];
    protected $casts = ['activo' => 'boolean'];

    public function gastos() { return $this->hasMany(Gasto::class); }
}
```

### `app/Models/Gasto.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gasto extends Model
{
    use SoftDeletes;

    protected $table = 'gastos';
    protected $fillable = [
        'categoria_gasto_id','propiedad_id','cuarto_id','fecha','monto',
        'descripcion','proveedor','metodo_pago','comprobante_path','notas','user_registro_id',
    ];
    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    public function categoria()    { return $this->belongsTo(CategoriaGasto::class, 'categoria_gasto_id'); }
    public function propiedad()    { return $this->belongsTo(Propiedad::class); }
    public function cuarto()       { return $this->belongsTo(Cuarto::class); }
    public function userRegistro() { return $this->belongsTo(User::class, 'user_registro_id'); }
}
```

## Seeder

### `database/seeders/CategoriaGastoSeeder.php`

```php
$cats = [
    ['nombre' => 'Luz',           'codigo' => 'luz'],
    ['nombre' => 'Agua',          'codigo' => 'agua'],
    ['nombre' => 'Internet',      'codigo' => 'internet'],
    ['nombre' => 'Basura',        'codigo' => 'basura'],
    ['nombre' => 'Mantenimiento', 'codigo' => 'mantenimiento'],
    ['nombre' => 'Gas',           'codigo' => 'gas'],
    ['nombre' => 'Salarios',      'codigo' => 'salarios'],
    ['nombre' => 'Otros',         'codigo' => 'otros'],
];
foreach ($cats as $c) CategoriaGasto::updateOrCreate(['codigo' => $c['codigo']], $c);
```

## Componentes Livewire (SFC)

```bash
# Páginas
php artisan make:livewire pages::gastos.index
php artisan make:livewire pages::categorias-gasto.index

# Componentes reusables
php artisan make:livewire gastos.tabla
php artisan make:livewire gastos.form
php artisan make:livewire gastos.modal-eliminar
php artisan make:livewire categorias-gasto.tabla
php artisan make:livewire categorias-gasto.form
```

### Validación en `gastos.form`

```php
protected function rules(): array
{
    return [
        'categoria_gasto_id' => 'required|exists:categorias_gasto,id',
        'propiedad_id'       => 'nullable|exists:propiedades,id',
        'cuarto_id'          => 'nullable|exists:cuartos,id',
        'fecha'              => 'required|date',
        'monto'              => 'required|numeric|min:0.01',
        'descripcion'        => 'required|string|max:255',
        'proveedor'          => 'nullable|string|max:150',
        'metodo_pago'        => 'required|in:efectivo,cuenta',
        'comprobante'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        'notas'              => 'nullable|string',
    ];
}
```

Validación cruzada (cuarto debe pertenecer a la propiedad):

```php
protected function prepareForValidation(): void
{
    if ($this->cuarto_id && $this->propiedad_id) {
        $cuarto = \App\Models\Cuarto::find($this->cuarto_id);
        if (!$cuarto || $cuarto->propiedad_id != $this->propiedad_id) {
            $this->cuarto_id = null;
        }
    }
}
```

Carga del comprobante en `guardar()`:

```php
if ($this->comprobante) {
    $datos['comprobante_path'] = $this->comprobante->store('comprobantes', 'local');
}
```

> **Nota sobre el disco:** se usa el disco `local` (privado) — los comprobantes contienen información financiera privada del negocio y no deben ser accesibles públicamente. En producción, cambiar `FILESYSTEM_DISK=s3` en `.env` migra automáticamente a S3 sin tocar código (los archivos antiguos se pueden migrar con un comando artisan).

## Visor de comprobantes

Los comprobantes (facturas, boletas, fotos de tickets) se consultan con **frecuencia** durante auditoría/conciliación mensual. Por eso usamos un visor inline en modal en lugar de descarga directa.

> **Nota importante:** el desarrollador ya tiene un componente `visor-pdf` funcionando en su proyecto previo (`expedientes-codede`). Ese componente se **porta** al proyecto SGA-SM en lugar de construirlo desde cero. Claude Code no debe implementar el visor — el desarrollador lo trae directo de su proyecto anterior y lo adapta a Livewire 4 SFC si es necesario.

### API esperada del componente `components::visor-pdf`

Mientras se porta el componente existente, dejamos documentada la interfaz que se va a consumir desde el módulo de gastos:

**Eventos que recibe (vía `dispatch`):**

```php
// Abrir el visor con un archivo
$this->dispatch('abrir-visor-pdf',
    url: route('gastos.comprobante.preview', $gasto),
    titulo: "Comprobante - {$gasto->descripcion}",
    descargaUrl: route('gastos.comprobante.descargar', $gasto)
);
```

**Eventos que emite:** ninguno crítico (cierre interno).

**Comportamiento esperado:**
- Abre un modal con el PDF embebido (vía `<iframe>` o `<embed>`).
- Si el archivo es imagen (jpg/png), lo muestra con `<img>`.
- Botón "Descargar" en el header del modal que apunta a `descargaUrl`.
- Botón "Cerrar".

### Controller para servir comprobantes

```bash
php artisan make:controller GastoController
```

`app/Http/Controllers/GastoController.php`:

```php
<?php
namespace App\Http\Controllers;

use App\Models\Gasto;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class GastoController extends Controller
{
    /**
     * Sirve el comprobante inline (para mostrar en visor).
     */
    public function previewComprobante(Gasto $gasto): Response
    {
        $this->authorize('view', $gasto);
        return $this->servir($gasto, inline: true);
    }

    /**
     * Sirve el comprobante como descarga.
     */
    public function descargarComprobante(Gasto $gasto): Response
    {
        $this->authorize('view', $gasto);
        return $this->servir($gasto, inline: false);
    }

    private function servir(Gasto $gasto, bool $inline): Response
    {
        if (!$gasto->comprobante_path || !Storage::exists($gasto->comprobante_path)) {
            abort(404, 'Comprobante no encontrado.');
        }

        $contenido = Storage::get($gasto->comprobante_path);
        $mime = Storage::mimeType($gasto->comprobante_path);
        $extension = pathinfo($gasto->comprobante_path, PATHINFO_EXTENSION);
        $nombreDescarga = sprintf('comprobante-%d-%s.%s', $gasto->id, $gasto->fecha->format('Y-m-d'), $extension);

        $disposition = $inline ? 'inline' : 'attachment';

        return response($contenido, 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => "{$disposition}; filename=\"{$nombreDescarga}\"",
        ]);
    }
}
```

### Acción desde la tabla de gastos

En `gastos.tabla`, el botón de "Ver comprobante":

```blade
@if ($gasto->comprobante_path)
    <flux:button
        wire:click="verComprobante({{ $gasto->id }})"
        size="xs"
        variant="ghost"
        icon="eye"
        title="Ver comprobante">
        Ver
    </flux:button>
@else
    <span class="text-zinc-400 text-sm">Sin comprobante</span>
@endif
```

Y en la clase del SFC `gastos.tabla`:

```php
public function verComprobante(int $gastoId): void
{
    $gasto = \App\Models\Gasto::findOrFail($gastoId);
    $this->authorize('view', $gasto);

    $this->dispatch('abrir-visor-pdf',
        url: route('gastos.comprobante.preview', $gasto),
        titulo: "Comprobante #{$gasto->id} - {$gasto->descripcion}",
        descargaUrl: route('gastos.comprobante.descargar', $gasto),
    );
}
```

> El componente `<livewire:visor-pdf />` se incluye una sola vez en el layout principal (igual que `<flux:toast />`), escucha el evento `abrir-visor-pdf` y se encarga de mostrarse.

## Policies

- **CategoriaGasto:** `viewAny`/`view` ambos. CUD solo admin. No eliminable si tiene gastos.
- **Gasto:** `viewAny`/`view`/`create` ambos. `update`/`delete` solo admin.

## Rutas

```php
Route::middleware('auth')->group(function () {
    Route::get('/gastos', App\Livewire\Pages\Gastos\Index::class)->name('gastos.index');

    Route::middleware('rol:administrador')->group(function () {
        Route::get('/categorias-gasto', App\Livewire\Pages\CategoriasGasto\Index::class)
            ->name('categorias.index');
    });

    // Servir comprobante autenticado — preview (inline) y descarga
    Route::get('/comprobante/{gasto}/preview', [App\Http\Controllers\GastoController::class, 'previewComprobante'])
        ->name('gastos.comprobante.preview');
    Route::get('/comprobante/{gasto}/descargar', [App\Http\Controllers\GastoController::class, 'descargarComprobante'])
        ->name('gastos.comprobante.descargar');
});
```

## Criterios de aceptación

- ✅ 8 categorías base sembradas.
- ✅ Gasto registrable sin asociación a propiedad/cuarto.
- ✅ Si selecciona cuarto, debe ser de la propiedad seleccionada.
- ✅ Comprobante en `storage/app/comprobantes/` (disco `local`, no público).
- ✅ Comprobante visible inline en visor PDF al hacer clic en "Ver".
- ✅ Comprobante descargable como respaldo desde el visor.
- ✅ Acceso a comprobantes solo para usuarios autenticados con permiso.
- ✅ Total filtrado coincide con la suma visible.
- ✅ Categoría con gastos no se puede eliminar.

---

# Fase 5 — PDF y reportes

**Objetivo:** generar recibos de pago en PDF, dashboard con flujo de caja (devengado y de caja), reporte de ocupación e historial financiero.

**Duración:** 1-1.5 semanas.

## Plantilla del recibo

### `resources/views/pdfs/recibo.blade.php`

> CSS inline básico. dompdf no soporta Tailwind ni Flexbox moderno. Layout con tablas.

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recibo {{ $pago->recibo_numero }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #222; margin: 0; padding: 30px; }
        .header { border-bottom: 2px solid #1F4E79; padding-bottom: 8px; margin-bottom: 16px; }
        .header h1 { color: #1F4E79; margin: 0; font-size: 18pt; }
        .header .sub { color: #666; font-size: 10pt; }
        .meta { width: 100%; margin-bottom: 16px; }
        .meta td { padding: 4px 0; }
        .meta .label { color: #666; width: 35%; }
        table.detalle { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.detalle th { background: #1F4E79; color: #fff; padding: 6px; text-align: left; }
        table.detalle td { padding: 6px; border-bottom: 1px solid #ddd; }
        .total { font-size: 14pt; font-weight: bold; color: #1F4E79; }
        .footer { margin-top: 40px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 9pt; color: #999; text-align: center; }
        .firma { margin-top: 50px; }
        .firma .linea { border-top: 1px solid #333; width: 60%; margin: 0 auto; padding-top: 4px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Recibo de Pago</h1>
        <div class="sub">Sistema de Gestión de Alquileres — San Marcos</div>
    </div>

    <table class="meta">
        <tr><td class="label">No. de recibo:</td><td><strong>{{ $pago->recibo_numero }}</strong></td></tr>
        <tr><td class="label">Fecha:</td><td>{{ $pago->fecha_pago->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Inquilino:</td><td>{{ $pago->estancia->inquilino->nombre_completo }}</td></tr>
        <tr><td class="label">Cuarto:</td><td>{{ $pago->estancia->cuarto->propiedad->nombre }} — {{ $pago->estancia->cuarto->codigo }}</td></tr>
        <tr><td class="label">Método:</td><td>{{ ucfirst($pago->metodo_pago) }} {{ $pago->referencia ? '('.$pago->referencia.')' : '' }}</td></tr>
        @if($pago->mes_aplicado)
            <tr><td class="label">Mes aplicado:</td><td>{{ $pago->mes_aplicado->translatedFormat('F Y') }}</td></tr>
        @endif
    </table>

    <table class="detalle">
        <thead>
            <tr><th>Concepto</th><th style="text-align:right;width:30%">Monto</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ ucfirst($pago->tipo) }}</td>
                <td style="text-align:right">Q {{ number_format($pago->monto_bruto, 2) }}</td>
            </tr>
            @if($pago->descuento > 0)
                <tr>
                    <td>Descuento ({{ $pago->motivo_descuento }})</td>
                    <td style="text-align:right">- Q {{ number_format($pago->descuento, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td class="total">Total</td>
                <td class="total" style="text-align:right">Q {{ number_format($pago->monto_neto, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="firma">
        <div class="linea">Recibido por</div>
    </div>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} por {{ $pago->userRegistro?->name ?? 'Sistema' }}
    </div>
</body>
</html>
```

## Service: `ReciboPdfService`

> Genera el PDF al vuelo y lo retorna como respuesta. **No persiste el archivo en disco.** Cada vez que el usuario haga clic en "Descargar recibo", se reconstruye desde los datos del pago (que son inmutables).

```php
<?php
namespace App\Services;

use App\Models\Pago;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ReciboPdfService
{
    /**
     * Genera el PDF al vuelo y lo retorna como respuesta de descarga.
     */
    public function descargar(Pago $pago): Response
    {
        $pago->load(['estancia.inquilino', 'estancia.cuarto.propiedad', 'userRegistro']);

        $pdf = Pdf::loadView('pdfs.recibo', ['pago' => $pago])
                  ->setPaper('letter', 'portrait');

        return $pdf->download("{$pago->recibo_numero}.pdf");
    }

    /**
     * Genera el PDF y lo sirve inline (útil para preview en navegador).
     * En SGA-SM no se usa por defecto — los recibos se descargan directamente.
     * Se mantiene disponible por si en el futuro se quiere ofrecer preview.
     */
    public function stream(Pago $pago): Response
    {
        $pago->load(['estancia.inquilino', 'estancia.cuarto.propiedad', 'userRegistro']);

        $pdf = Pdf::loadView('pdfs.recibo', ['pago' => $pago])
                  ->setPaper('letter', 'portrait');

        return $pdf->stream("{$pago->recibo_numero}.pdf");
    }
}
```

> **Importante:** `PagoService::registrar()` **NO** llama a `ReciboPdfService` al guardar el pago. La generación es completamente on-demand.

### Controller para servir el recibo

```bash
php artisan make:controller PagoController
```

`app/Http/Controllers/PagoController.php`:

```php
<?php
namespace App\Http\Controllers;

use App\Models\Pago;
use App\Services\ReciboPdfService;
use Symfony\Component\HttpFoundation\Response;

class PagoController extends Controller
{
    public function descargarRecibo(Pago $pago, ReciboPdfService $service): Response
    {
        $this->authorize('view', $pago);
        return $service->descargar($pago);
    }
}
```

### UX en la tabla de pagos

El botón es una ruta GET directa, sin Livewire ni eventos:

```blade
<flux:button
    href="{{ route('recibos.pdf', $pago) }}"
    size="xs"
    variant="ghost"
    icon="arrow-down-tray"
    title="Descargar recibo">
    Recibo
</flux:button>
```

El navegador descarga automáticamente `REC-2026-000001.pdf`.

## Service: `ReporteService`

```php
<?php
namespace App\Services;

use App\Models\Cuarto;
use App\Models\Gasto;
use App\Models\Pago;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReporteService
{
    public function flujoDevengado(Carbon $desde, Carbon $hasta): Collection
    {
        $ingresos = Pago::whereNotNull('mes_aplicado')
            ->whereBetween('mes_aplicado', [$desde->copy()->startOfMonth(), $hasta->copy()->endOfMonth()])
            ->selectRaw("DATE_FORMAT(mes_aplicado, '%Y-%m') as periodo, SUM(monto_neto) as total")
            ->groupBy('periodo')
            ->pluck('total', 'periodo');

        $egresos = Gasto::whereBetween('fecha', [$desde, $hasta])
            ->selectRaw("DATE_FORMAT(fecha, '%Y-%m') as periodo, SUM(monto) as total")
            ->groupBy('periodo')
            ->pluck('total', 'periodo');

        return $this->consolidar($ingresos, $egresos, $desde, $hasta);
    }

    public function flujoCaja(Carbon $desde, Carbon $hasta): Collection
    {
        $ingresos = Pago::whereBetween('fecha_pago', [$desde, $hasta])
            ->selectRaw("DATE_FORMAT(fecha_pago, '%Y-%m') as periodo, SUM(monto_neto) as total")
            ->groupBy('periodo')
            ->pluck('total', 'periodo');

        $egresos = Gasto::whereBetween('fecha', [$desde, $hasta])
            ->selectRaw("DATE_FORMAT(fecha, '%Y-%m') as periodo, SUM(monto) as total")
            ->groupBy('periodo')
            ->pluck('total', 'periodo');

        return $this->consolidar($ingresos, $egresos, $desde, $hasta);
    }

    protected function consolidar(Collection $ingresos, Collection $egresos, Carbon $desde, Carbon $hasta): Collection
    {
        $resultado = collect();
        $cursor = $desde->copy()->startOfMonth();
        while ($cursor->lte($hasta->endOfMonth())) {
            $key = $cursor->format('Y-m');
            $ing = (float) ($ingresos[$key] ?? 0);
            $egr = (float) ($egresos[$key] ?? 0);
            $resultado->put($key, [
                'periodo'  => $key,
                'ingresos' => $ing,
                'egresos'  => $egr,
                'ganancia' => round($ing - $egr, 2),
            ]);
            $cursor->addMonth();
        }
        return $resultado;
    }

    public function ocupacionActual(): array
    {
        $total    = Cuarto::where('activo', true)->count();
        $ocupados = Cuarto::where('activo', true)->where('estado', Cuarto::ESTADO_OCUPADO)->count();
        $porPropiedad = Cuarto::where('activo', true)
            ->selectRaw('propiedad_id, COUNT(*) as total, SUM(estado = "ocupado") as ocupados')
            ->groupBy('propiedad_id')
            ->with('propiedad:id,nombre')
            ->get();

        return [
            'total'        => $total,
            'ocupados'     => $ocupados,
            'porcentaje'   => $total > 0 ? round(($ocupados / $total) * 100, 1) : 0,
            'porPropiedad' => $porPropiedad,
        ];
    }
}
```

## Componentes Livewire (SFC)

```bash
# Páginas
php artisan make:livewire pages::dashboard
php artisan make:livewire pages::reportes.flujo-caja
php artisan make:livewire pages::reportes.ocupacion
php artisan make:livewire pages::reportes.parqueo

# Componentes reusables
php artisan make:livewire reportes.cards-resumen
php artisan make:livewire reportes.tabla-flujo
php artisan make:livewire reportes.grafico-flujo
php artisan make:livewire reportes.barra-ocupacion
php artisan make:livewire reportes.tabla-parqueo
```

### `pages::dashboard`
- Cards: ocupación actual, ingresos del mes (devengado), gastos del mes, ganancia neta. Usar `<flux:card>` y `<flux:heading>`.
- Gráfico de últimos 6 meses con Chart.js (instalado vía npm en Fase 0).
- Últimos 10 pagos y últimos 10 gastos (tablas Flux compactas).

### Implementación de Chart.js

Antes de usarlo, importar y registrar los componentes que se necesiten en `resources/js/app.js`:

```js
import {
    Chart,
    LineController, LineElement, PointElement,
    BarController, BarElement,
    LinearScale, CategoryScale,
    Tooltip, Legend, Filler,
} from 'chart.js';

Chart.register(
    LineController, LineElement, PointElement,
    BarController, BarElement,
    LinearScale, CategoryScale,
    Tooltip, Legend, Filler,
);

// Exportar globalmente para que esté disponible en componentes Livewire
window.Chart = Chart;
```

Después en el componente Livewire:

```blade
<div wire:ignore x-data="grafico({{ Js::from($datos) }})" x-init="render()">
    <canvas x-ref="canvas" class="w-full h-64"></canvas>
</div>

<script>
    function grafico(datos) {
        return {
            chart: null,
            render() {
                this.chart = new Chart(this.$refs.canvas, {
                    type: 'line',
                    data: {
                        labels: datos.labels,
                        datasets: [
                            { label: 'Ingresos', data: datos.ingresos, borderColor: '#22c55e', tension: 0.3 },
                            { label: 'Egresos',  data: datos.egresos,  borderColor: '#ef4444', tension: 0.3 },
                        ],
                    },
                    options: { responsive: true, maintainAspectRatio: false },
                });
            }
        };
    }
</script>
```

> `wire:ignore` evita que Livewire re-renderice el `<canvas>` al actualizar y rompa el gráfico. Para refrescar datos, usar `chart.data = nuevoData; chart.update();`.

### `pages::reportes.flujo-caja`
- Selectores: rango de fechas (default año actual), vista (devengado/caja).
- Tabla mensual con totales generales.
- Botón "Exportar CSV" (opcional).
- Solo admin: `middleware: rol:administrador`.
- **No incluye ingresos de parqueo externo** (esos van en su reporte propio).

### `pages::reportes.parqueo`

Reporte simple e independiente del flujo de caja principal. Es un ingreso "extra".

- Selectores: rango de meses (default año actual), filtro por estado (pagado/pendiente/todos).
- Tabla agrupada por mes:
  - Mes
  - Cantidad de espacios rentados
  - Total cobrado (suma de `monto` donde `pagado=true`)
  - Total pendiente (suma de `monto` donde `pagado=false`)
- Conteo de vehículos esperados en parqueo (combinando inquilinos con vehículo y arrendatarios externos activos):
  ```php
  $inquilinosConVehiculo = Inquilino::whereNotNull('vehiculo_tipo')
      ->whereHas('estanciaActiva')
      ->count();
  $arrendatariosActivos = ArrendatarioParqueo::activos()->count();
  $totalVehiculos = $inquilinosConVehiculo + $arrendatariosActivos;
  ```
- Botón "Exportar CSV" (opcional).
- Solo admin: `middleware: rol:administrador`.

## Rutas

```php
Route::middleware('auth')->group(function () {
    Route::get('/recibos/{pago}/pdf', [App\Http\Controllers\PagoController::class, 'descargarRecibo'])
        ->name('recibos.pdf');

    Route::middleware('rol:administrador')->group(function () {
        Route::get('/reportes/flujo-caja', App\Livewire\Pages\Reportes\FlujoCaja::class)
            ->name('reportes.flujo');
        Route::get('/reportes/ocupacion', App\Livewire\Pages\Reportes\Ocupacion::class)
            ->name('reportes.ocupacion');
        Route::get('/reportes/parqueo', App\Livewire\Pages\Reportes\Parqueo::class)
            ->name('reportes.parqueo');
    });
});
```

## Criterios de aceptación

- ✅ El recibo PDF se genera **on-demand** al hacer clic en "Descargar" (no se persiste en disco).
- ✅ Cualquier descarga repetida del mismo recibo produce un PDF idéntico (datos del pago son inmutables).
- ✅ Recibo con datos correctos (número, fecha, inquilino, cuarto, casa, montos).
- ✅ Dashboard muestra ocupación, ingresos y gastos del mes.
- ✅ Devengado vs caja coinciden cuando no hay anticipos; difieren cuando los hay.
- ✅ Reportes financieros restringidos a admin.
- ✅ La carpeta `storage/app/recibos/` no existe (no es necesaria).
- ✅ Reporte de parqueo muestra ingresos por mes separado del flujo de caja principal.
- ✅ Conteo de vehículos suma inquilinos con vehículo + arrendatarios externos activos.

## Notas técnicas Fase 5

- `Carbon::setLocale('es')` y `translatedFormat()` para meses en español.
- Fuente `DejaVu Sans` viene con dompdf y soporta acentos.
- Locale de Carbon configurable globalmente en `AppServiceProvider::boot()`: `Carbon::setLocale(config('app.locale'));`.

---

# Fase 6 — Pulido final y despliegue

**Objetivo:** módulo de gestión de usuarios, ajustes UX, validaciones complementarias, seeders demo, README, despliegue.

**Duración:** 4-5 días.

## Tareas

### Gestión de usuarios

```bash
php artisan make:livewire pages::usuarios.index
php artisan make:livewire usuarios.tabla
php artisan make:livewire usuarios.form
php artisan make:livewire usuarios.modal-toggle-activo
```

- Solo admin. Puede crear/editar/activar/desactivar (no eliminar).
- Form: name, email, rol, password (al crear), checkbox activo.
- Reseteo de password por admin (genera password temporal y la muestra una vez).

### UX y responsive
- [ ] Revisar todas las vistas en mobile (320-414px).
- [ ] Confirmaciones: usar `wire:confirm` para acciones destructivas como respaldo a los modales.
- [ ] Loading states: `wire:loading` en todos los botones de acción.
- [ ] Skeleton screens en tablas grandes (`wire:loading.delay`).
- [ ] Empty states con CTA.

### Validaciones complementarias
- [ ] `fecha_fin` >= `fecha_inicio` en estancias.
- [ ] `mes_aplicado` >= `fecha_inicio` de la estancia.
- [ ] Rate limiting en login (Fortify lo trae).

### Seeders de demo
- [ ] `DatosDemoSeeder`: 3 propiedades, 12 cuartos, 8 inquilinos, 5 estancias activas, 2 finalizadas, 30 pagos, 20 gastos.
- [ ] Comando `php artisan sga:demo` que ejecute `migrate:fresh --seed --class=DatosDemoSeeder`.

### Documentación
- [ ] README completo (instalación, comandos, credenciales, despliegue).
- [ ] CHANGELOG v1.0.

### Despliegue
- [ ] `.env.production` template.
- [ ] `php artisan optimize` (cache de config, rutas, vistas, eventos).
- [ ] `npm run build`.
- [ ] Backup diario de BD (cron + `mysqldump`).
- [ ] HTTPS configurado.

### Checklist seguridad
- [ ] `APP_DEBUG=false` en producción.
- [ ] Credenciales demo eliminadas o cambiadas.
- [ ] `.env` fuera del repo.
- [ ] Permisos correctos en `storage/` y `bootstrap/cache/`.
- [ ] CSRF activo en formularios (default).
- [ ] Rutas `/comprobante/{id}/preview`, `/comprobante/{id}/descargar` y `/recibos/{id}/pdf` autenticadas y autorizadas vía Policy.

## Criterios de aceptación finales
- ✅ Sistema en producción.
- ✅ Admin gestiona usuarios completos.
- ✅ Todas las vistas responsive.
- ✅ Flujo end-to-end funciona sin errores.
- ✅ Backups configurados.
- ✅ README permite levantar el proyecto en otra máquina.

---

# Apéndices

## A. Comandos útiles

```bash
# Migraciones
php artisan make:migration nombre_descriptivo
php artisan migrate
php artisan migrate:rollback --step=1
php artisan migrate:fresh --seed

# Modelos / componentes
php artisan make:model NombreModelo
php artisan make:livewire pages::modulo.componente
php artisan make:livewire modulo.componente
php artisan make:policy NombrePolicy --model=NombreModelo
php artisan make:observer NombreObserver --model=NombreModelo
php artisan make:request Carpeta/NombreRequest

# Tinker
php artisan tinker

# Caches
php artisan optimize:clear   # dev
php artisan optimize         # prod

# Boost (mantener guidelines actualizadas)
php artisan boost:update
```

## B. Snippets recurrentes

### Autorizar dentro de un SFC
```php
public function mount(int $id): void
{
    $this->modelo = Modelo::findOrFail($id);
    $this->authorize('update', $this->modelo);
}
```

### Verificar permiso en Blade
```blade
@can('create', App\Models\Propiedad::class)
    <flux:button variant="primary">Nueva</flux:button>
@endcan
```

### Confirmación rápida
```blade
<flux:button
    wire:click="eliminar({{ $item->id }})"
    wire:confirm="¿Seguro que deseas eliminar este registro?"
    variant="danger" size="xs">Eliminar</flux:button>
```

### Toast desde un componente
```php
use Flux\Flux;

Flux::toast(text: 'Operación exitosa.', variant: 'success');
// Variantes: success, warning, danger, info (default)
```

### Modal desde un componente
```php
use Flux\Flux;

Flux::modal('nombre-del-modal')->show();
Flux::modal('nombre-del-modal')->close();
```

### Ruta protegida por rol
```php
Route::middleware(['auth','rol:administrador'])->group(function () {
    // ...
});
```

### Eager loading (anti N+1)
```php
$propiedades = Propiedad::withCount('cuartos')->paginate(15);
```

## C. Gotchas conocidos

- **MySQL 8 columna virtual generada**: la sintaxis del UNIQUE va junto a la columna en una sola sentencia ALTER. Si las separas en migraciones distintas, MySQL puede rechazarla.
- **Cast `decimal:2`**: Eloquent retorna **string**, no float. Para sumas en PHP, usa `(float)` explícito o BCMath.
- **Livewire v4 SFC**: el archivo lleva `⚡` (U+26A1) en el nombre — lo agrega `make:livewire`. No lo escribas a mano. El nombre lógico del componente es `pages::propiedades.index`, no `pages::propiedades.⚡index`.
- **Layout en página**: usa el atributo `#[Layout('components.layouts.app')]` en la clase del SFC página. Si no lo defines, Livewire usa el layout por defecto del starter kit.
- **Modales en Flux**: cada modal tiene un `name` único. Se abre con `<flux:modal.trigger>` o `Flux::modal('name')->show()`, y se cierra con `<flux:modal.close>` o `Flux::modal('name')->close()`. NO uses `<dialog>` nativo ni `document.getElementById(...).showModal()` — eso era el patrón daisyUI.
- **Toasts en Flux**: `Flux::toast(text: '...', variant: 'success')` desde el componente PHP. El componente `<flux:toast />` ya viene en el layout del starter kit; si no, agregarlo una vez antes de `</body>`.
- **Date picker en Free**: Flux Pro tiene date picker, pero Free no. Para SGA-SM se usa `<flux:input type="date" />` (input nativo HTML5 envuelto en estilo Flux). Suficiente para todos los casos del sistema.
- **Livewire 4 + paginación**: Tailwind por defecto. `WithPagination` trait sigue funcionando igual que en v3.
- **Soft deletes y unique**: `Rule::unique(...)->whereNull('deleted_at')` cuando aplique.
- **Locks y transacciones**: `lockForUpdate()` solo dentro de `DB::transaction()`. Sin transacción, MySQL lo ignora.
- **Boost guidelines**: si Boost reescribe `CLAUDE.md`, las reglas custom de SGA-SM están en `.ai/guidelines/sga-sm.md` y se incluyen automáticamente al regenerar.
- **Recibos vs comprobantes (estrategia de archivos)**:
  - **Recibos**: NO se persisten. Se generan on-demand cada vez con `ReciboPdfService::descargar($pago)`. Datos del pago son inmutables, el PDF reconstruido es idéntico al original.
  - **Comprobantes de gastos**: SÍ se persisten en `storage/app/comprobantes/` (disco `local`). Son archivos del usuario (uploads), no derivados. Migrar a S3 en producción cambiando `FILESYSTEM_DISK=s3`.
- **Componente `visor-pdf`**: se porta del proyecto previo `expedientes-codede`. No re-implementar desde cero. La interfaz esperada (`abrir-visor-pdf` con url/título/descargaUrl) está documentada en Fase 4.

## D. Recursos

- Laravel 13: https://laravel.com/docs/13.x
- Livewire 4: https://livewire.laravel.com/docs/4.x
- Livewire v4 Components (SFC): https://livewire.laravel.com/docs/4.x/components
- Fortify: https://laravel.com/docs/13.x/fortify
- Boost: https://laravel.com/docs/13.x/boost
- Flux UI: https://fluxui.dev/docs
- Chart.js: https://www.chartjs.org/docs/latest/
- dompdf Laravel: https://github.com/barryvdh/laravel-dompdf

---

*Plan operativo de desarrollo · Versión 3.0 · Mayo 2026*
*Stack: Laravel 13 + Livewire 4 SFC + Flux Free + Chart.js + MySQL 8 + Fortify + Boost · Recibos on-demand · Comprobantes con visor PDF*
