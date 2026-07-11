<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sga:demo {--force : Ejecutar sin pedir confirmación}')]
#[Description('Reinicia la base de datos y carga datos de demostración (DatosDemoSeeder).')]
class SgaDemo extends Command
{
    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('sga:demo está bloqueado en producción. Usa --force si estás totalmente seguro.');

            return self::FAILURE;
        }

        $this->warn('Esto BORRARÁ toda la base de datos y la reemplazará con datos de demostración.');

        if (! $this->option('force') && ! $this->confirm('¿Continuar?')) {
            $this->info('Operación cancelada.');

            return self::SUCCESS;
        }

        $this->call('migrate:fresh');
        $this->call('db:seed', ['--class' => 'DatosDemoSeeder']);

        $this->newLine();
        $this->info('✅ Datos de demostración cargados.');
        $this->line('   Admin:     admin@demo.test / password');
        $this->line('   Encargado: encargado@demo.test / password');

        return self::SUCCESS;
    }
}
