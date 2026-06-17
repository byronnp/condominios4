<?php

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
