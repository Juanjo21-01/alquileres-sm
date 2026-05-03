# Sistema de Alquileres San Marcos

Aplicación web para la gestión de alquileres (inmuebles), contratos, pagos y clientes.

## Requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL/MariaDB

## Instalación

1. Clonar el repositorio.
2. Instalar dependencias:

```bash
composer install
npm install
```

3. Copiar y configurar el entorno:

```bash
cp .env.example .env
php artisan key:generate
```

4. Configurar la base de datos en `.env`.
5. Ejecutar migraciones y seeders:

```bash
php artisan migrate --seed
```

6. Compilar assets:

```bash
npm run build
```

7. Iniciar el servidor:

```bash
php artisan serve
```

## Scripts útiles

```bash
npm run dev
php artisan test
```

## Estructura básica

- `app/` Lógica de la aplicación
- `routes/` Rutas
- `resources/` Vistas y assets
- `database/` Migraciones y seeders

## Licencia

Proyecto privado.
