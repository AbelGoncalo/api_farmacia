<?php

namespace App\Providers;

use App\Enums\Auth\PapelUtilizador;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('gerir-catalogo', fn (User $user): bool => $user->temPapel(
            PapelUtilizador::UTILIZADOR_SISTEMA,
            PapelUtilizador::SUPER_ADMIN,
        ));

        Gate::define('gerir-utilizadores', fn (User $user): bool => $user->temPapel(
            PapelUtilizador::SUPER_ADMIN,
        ));

        Gate::define('processar-encomendas', fn (User $user): bool => $user->temPapel(
            PapelUtilizador::UTILIZADOR_SISTEMA,
            PapelUtilizador::SUPER_ADMIN,
        ));

        Gate::define('rever-prescricoes', fn (User $user): bool => $user->temPapel(
            PapelUtilizador::UTILIZADOR_SISTEMA,
            PapelUtilizador::SUPER_ADMIN,
        ));
    }
}
