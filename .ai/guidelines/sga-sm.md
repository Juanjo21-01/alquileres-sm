# SGA-SM · Guidelines del Proyecto

## Sobre el proyecto
Sistema web de gestión de alquileres de cuartos para estudiantes y personal de salud en San Marcos, Guatemala. Stack: Laravel 13 + Livewire 4 (SFC) + Tailwind + daisyUI + MySQL 8 + Fortify + dompdf + Laravel Boost.

## Documentación de referencia
- Especificación funcional: `docs/Documentacion_SGA-SM.docx`
- Plan de desarrollo fase por fase: `docs/plan_desarrollo.md` ← **seguir estrictamente**

## Convenciones inviolables

### Idioma
- **Tablas, columnas, modelos, métodos y rutas del dominio del negocio: español**.
- **Excepción intencional:** `users` y `App\Models\User` se mantienen en inglés (convención de Laravel/Fortify). Esa tabla representa al operador del sistema, no al dominio de alquileres. Los inquilinos viven en su propia tabla en español. La columna `name` en `users` también se mantiene en inglés.
- Tablas dominio: snake_case plural en español (`propiedades`, `cuartos`, `extras_estancia`, `categorias_gasto`).
- Modelos dominio: PascalCase singular en español (`Propiedad`, `Cuarto`, `ExtraEstancia`). Fijar `protected $table` cuando el plural en español difiera de la convención inglesa.
- Métodos: camelCase en español (`abrirEstancia`, `calcularFlujoCaja`).
- Rutas: kebab-case en español (`/propiedades`, `/extras-estancia`).

### Componentes Livewire — Single-File Components (SFC) v4
- **Formato exclusivo: SFC**. La clase PHP y la vista Blade van en el mismo archivo `.blade.php` con bloque `<?php new class extends Component { ... }; ?>` arriba del HTML.
- **NO usar `--mfc` (multi-file components)**.
- **NO usar el patrón class-based de v2/v3** (clase PHP separada en `app/Livewire/`).
- **Páginas** (rutas accesibles vía URL): namespace `pages::`. Generar con `php artisan make:livewire pages::modulo.componente`. Viven en `resources/views/pages/`.
- **Componentes reusables** (tablas, formularios, modales embebidos): namespace default. Generar con `php artisan make:livewire modulo.componente`. Viven en `resources/views/components/`.
- **Patrón por módulo: un componente de tabla, formulario y modal por cada módulo de dominio.** No abstraer en componentes genéricos parametrizados — cada módulo tiene sus propias validaciones, columnas y comportamientos. Ejemplo: `propiedades.tabla`, `cuartos.tabla`, `inquilinos.tabla`, `pagos.tabla` son todos componentes distintos.
- Nombrado dot-notation: `pages::propiedades.index`, `propiedades.tabla`, `propiedades.form`, `propiedades.modal-eliminar`.
- El símbolo `⚡` en el nombre del archivo lo agrega `make:livewire` automáticamente.

### Datos y dinero
- Dinero: `decimal(10,2)` siempre. Nunca `float`.
- Cast Eloquent retorna string en `decimal:2` — usar `(float)` explícito para operaciones aritméticas.
- Fechas: UTC en BD, presentación con timezone `America/Guatemala`.
- Soft deletes activos en: `inquilinos`, `estancias`, `pagos`, `gastos`.

### Arquitectura
- Estructura estándar de Laravel + carpetas `app/Services/` y `app/Observers/`.
- **NO** usar arquitectura tipo DDD (sin carpeta `Domain/`).
- Lógica de negocio transaccional: en `app/Services/`. Los componentes Livewire **solo orquestan** (UI, validación, llamada al service).
- Ejemplo: `EstanciaService::abrir()`, `PagoService::registrar()`, `ReciboPdfService::generar()`.
- Toda operación que afecte 2+ tablas dentro de `DB::transaction()`.
- Para evitar race conditions: `lockForUpdate()` en consultas críticas (siempre dentro de transacción).

### Autenticación y autorización
- Auth: Laravel Fortify (viene con starter kit Livewire). **Registro público deshabilitado** — solo admin crea usuarios.
- Tabla `roles` normalizada con FK `rol_id` desde `users`. Modelo `Rol` con constantes `COD_ADMIN`, `COD_ENCARGADO`, `COD_INQUILINO` (futuro).
- Autorización: Policies + Gates + middleware `rol` nativo. **NO usar `spatie/laravel-permission`**.
- Roles iniciales seed: `administrador`, `encargado`. Preparado para `inquilino` a futuro sin migración.

### Validación
- En FormRequests para controllers, o en `protected function rules()` en SFCs Livewire.
- Estados: enum en BD + constantes en el modelo (ej. `Cuarto::ESTADO_DISPONIBLE`).

### UX
- Modales daisyUI (`<dialog>`) controlados con eventos Livewire (`mostrar-modal`, `cerrar-modal`).
- Toasts globales con un único componente `<livewire:toast-global />` que escucha el evento `toast`.
- Loading states con `wire:loading` en todos los botones de acción.
- Empty states con CTA en tablas vacías.
- Confirmación con modales explícitos para acciones destructivas (no solo `wire:confirm`).

### Estrategia de archivos generados vs subidos
- **Recibos PDF (generados):** NO se persisten. Se generan on-demand con `ReciboPdfService::descargar($pago)` cada vez que el usuario los descarga. Los datos del pago son inmutables, así que el PDF reconstruido es idéntico al original. Esto evita llenar disco con miles de archivos derivados.
- **Comprobantes de gastos (subidos por el usuario):** SÍ se persisten en `storage/app/comprobantes/` (disco `local`). En producción se migran a S3 cambiando `FILESYSTEM_DISK` en `.env`.
- **Visor PDF:** se porta del proyecto previo `expedientes-codede`, no se re-implementa. Recibos van por descarga directa; comprobantes se ven inline en el visor con opción de descarga.

### Constraint crítico (Fase 2)
- Estancias: índice único parcial sobre `cuarto_id` donde `estado='activa' AND deleted_at IS NULL`.
- En MySQL 8: columna virtual generada + UNIQUE en una sola sentencia ALTER (ver `docs/plan_desarrollo.md` sección 2.3).

## Reglas para Claude Code

1. **Antes de modificar código existente, lee primero los archivos relevantes.**
2. **Antes de crear migraciones, modelos, services o componentes**, revisa `docs/plan_desarrollo.md` y usa el código exacto definido ahí. **No improvises sintaxis.**
3. **Trabaja por fase**, no todo a la vez. Sigue el orden estricto del plan.
4. **Dentro de cada fase, trabaja por bloques**: migraciones → modelos → seeders → services → policies → componentes Livewire (páginas y reusables) → rutas. No saltes etapas.
5. **Componentes Livewire siempre como SFC**, nunca class-based ni multi-file. Sigue los ejemplos de `docs/plan_desarrollo.md` (Fase 1 tiene los SFC más completos).
6. **Una tabla, un form y un modal de eliminación por módulo.** No reutilices un mismo componente tabla para módulos distintos.
7. **Lógica de negocio en services**, no en el SFC. El SFC solo valida, llama al service e maneja eventos/toasts.
8. **Al terminar cada bloque**: ejecuta migraciones/tests pertinentes y verifica que no haya errores antes de pasar al siguiente.
9. **NO hagas commits automáticos** — el desarrollador decide cuándo commitear y con qué mensaje.
10. **Si una decisión técnica no está en el plan**, pregúntale al desarrollador antes de improvisar.
11. **Convención de commits del desarrollador**: conventional commits (`feat:`, `fix:`, `chore:`, `refactor:`, `docs:`).
12. **Usa Laravel Boost MCP**: cuando dudes sobre sintaxis de Laravel/Livewire/Tailwind, consulta la documentation API de Boost (`Search Docs` MCP tool) en lugar de adivinar.

## Estado actual
Setup inicial recién completado con Laravel 13 + starter kit Livewire + Boost. Próximo: ajustes finales de Fase 0 (locale, daisyUI, deshabilitar registro, dompdf, estructura de carpetas) y luego Fase 1 (roles, propiedades y cuartos).