<?php

use App\Models\AuthSession;
use App\Models\RefreshToken;
use App\Models\RevokedToken;
use App\OpenApi\OpenApiExampleEnricher;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('openapi:generate', function (OpenApiExampleEnricher $enricher) {
    $this->call('l5-swagger:generate');

    $path = storage_path('api-docs/api-docs.json');
    $documentation = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    file_put_contents(
        $path,
        json_encode($enricher->enrich($documentation), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
    );

    $this->info('OpenAPI documentation generated with request and response examples.');
})->purpose('Generate OpenAPI documentation and enrich it with examples');

Artisan::command('auth:prune {--inactive-days=30 : Días de inactividad para sesiones cerradas}', function () {
    $now = now();
    $inactiveDays = max(1, (int) $this->option('inactive-days'));
    $inactiveThreshold = $now->copy()->subDays($inactiveDays);

    $refreshTokensDeleted = RefreshToken::query()
        ->where('expires_at', '<=', $now)
        ->forceDelete();

    $revokedTokensDeleted = RevokedToken::query()
        ->where('expires_at', '<=', $now)
        ->delete();

    $sessionsDeleted = AuthSession::query()
        ->where('is_active', false)
        ->where(function ($query) use ($inactiveThreshold): void {
            $query->where(function ($query) use ($inactiveThreshold): void {
                $query->whereNotNull('ended_at')
                    ->where('ended_at', '<=', $inactiveThreshold);
            })->orWhere(function ($query) use ($inactiveThreshold): void {
                $query->whereNull('ended_at')
                    ->whereNotNull('last_activity_at')
                    ->where('last_activity_at', '<=', $inactiveThreshold);
            });
        })
        ->forceDelete();

    $this->info(sprintf(
        'Limpieza completada. Refresh tokens: %d, revoked tokens: %d, sesiones: %d.',
        $refreshTokensDeleted,
        $revokedTokensDeleted,
        $sessionsDeleted,
    ));
})->purpose('Prune expired refresh tokens, expired revoked tokens and inactive auth sessions');
