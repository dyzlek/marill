<?php

namespace App\Providers;

use App\Models\Reglage;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configurerLimitesDeDebit();
    }

    /**
     * A10 : limites de débit nommées, utilisées dans routes/api.php (throttle:api-publique…).
     *
     * Le quota se lit dans les réglages (l'administrateur le change dans le back-office sans redéployer).
     * On compte par membre s'il est connecté, sinon par adresse IP : une autre bibliothèque qui relaie ses
     * visiteurs n'a qu'une IP, elle partage un compteur, comme le dit le CDC (« le reste est protégé par la
     * limite de débit »). Un nom de limite = un compteur : toutes les routes de lecture le partagent.
     */
    protected function configurerLimitesDeDebit(): void
    {
        $client = fn (Request $request): string => ($id = auth('sanctum')->id()) !== null
            ? 'membre:'.$id
            : 'ip:'.$request->ip();

        RateLimiter::for('api-publique', fn (Request $request) => Limit::perMinute(Reglage::quotaApi())->by($client($request)));

        // Les actions sans compte qui écrivent en base (liens courts, signalements) : même principe,
        // un quota plus serré, un compteur à part pour que lire beaucoup n'empêche pas de signaler.
        RateLimiter::for('publications', fn (Request $request) => Limit::perMinute(Reglage::quotaPublications())->by($client($request)));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // N+1 : hors production, lire une relation qui n'a pas été chargée par with() / load() lève
        // une exception au lieu de lancer en silence une requête par ligne. En production, on ne casse
        // pas une page pour ça.
        Model::preventLazyLoading(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
