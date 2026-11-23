<?php

namespace Modules\CCM_Events\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Namespace des contrôleurs admin.
     */
    protected string $adminNamespace = 'Modules\CCM_Events\Http\Controllers\Admin';

    /**
     * Namespace des contrôleurs publics.
     */
    protected string $publicNamespace = 'Modules\CCM_Events\Http\Controllers';

    public function map(): void
    {
        $this->mapAdminRoutes();
        $this->mapPublicRoutes();
        $this->mapShowcaseRoutes();
    }

    /**
     * Page vitrine publique des évènements (/evenements).
     * Accessible SANS connexion (visiteurs, VATSIM, etc.).
     */
    protected function mapShowcaseRoutes(): void
    {
        Route::group([
            'namespace'  => $this->publicNamespace,
            'middleware' => ['web'],
        ], function () {
            Route::get('/evenements', 'PublicController@showcase')->name('ccm_events.showcase');
        });
    }

    /**
     * Routes admin du module.
     *
     * IMPORTANT: préfixe volontairement court ("ccm-events"), PAS
     * "/modules/ccm-events" ni "/admin/ccm-events" — même convention
     * que CCM_OpsTools. La restriction d'accès vient uniquement du
     * middleware "ability:admin,flights", pas de l'URL.
     */
    protected function mapAdminRoutes(): void
    {
        Route::group([
            'namespace'  => $this->adminNamespace,
            'prefix'     => 'ccm-events',
            'as'         => 'ccm_events.',
            'middleware' => ['web', 'auth', 'ability:admin,flights'],
        ], function () {
            require __DIR__.'/../Http/Routes/admin.php';
        });
    }

    /**
     * Routes publiques du module (page évènements, réservations).
     * Accessible à tout pilote connecté.
     */
    protected function mapPublicRoutes(): void
    {
        Route::group([
            'namespace'  => $this->publicNamespace,
            'prefix'     => 'events',
            'as'         => 'ccm_events.public.',
            'middleware' => ['web', 'auth'],
        ], function () {
            require __DIR__.'/../Http/Routes/web.php';
        });
    }
}