<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Pest\Laravel\Commands\PestDatasetCommand;
use Pest\Laravel\Commands\PestInstallCommand;
use Pest\Laravel\Commands\PestTestCommand;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $hidden = config('commands.hidden');

        $devClasses = [
            PestInstallCommand::class,
            PestDatasetCommand::class,
            PestTestCommand::class,
        ];
        foreach ($devClasses as $class) {
            if (class_exists($class)) {
                $hidden[] = $class;
            }
        }

        config(['commands.hidden' => $hidden]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
