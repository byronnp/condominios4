<?php

namespace App\Providers;

use App\Models\Condominium;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\CondominiumPolicy;
use App\Policies\MenuPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\PlatformAdministratorPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        Gate::policy(Condominium::class, CondominiumPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Menu::class, MenuPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);

        Gate::define('platform-administrators.viewAny', [PlatformAdministratorPolicy::class, 'viewAny']);
        Gate::define('platform-administrators.view', [PlatformAdministratorPolicy::class, 'view']);
        Gate::define('platform-administrators.create', [PlatformAdministratorPolicy::class, 'create']);
        Gate::define('platform-administrators.update', [PlatformAdministratorPolicy::class, 'update']);
        Gate::define('platform-administrators.updateStatus', [PlatformAdministratorPolicy::class, 'updateStatus']);
        Gate::define('platform-administrators.delete', [PlatformAdministratorPolicy::class, 'delete']);

        RateLimiter::for('auth-login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(sprintf('%s|%s', $request->ip(), (string) $request->input('email')));
        });

        RateLimiter::for('auth-refresh', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('auth-activate-access', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
