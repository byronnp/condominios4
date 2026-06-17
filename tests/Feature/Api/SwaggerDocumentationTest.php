<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SwaggerDocumentationTest extends TestCase
{
    public function test_swagger_ui_route_is_available(): void
    {
        $response = $this->get('/api/documentation');

        $response->assertOk();
    }

    public function test_openapi_documentation_covers_api_routes(): void
    {
        $documentation = json_decode(
            file_get_contents(storage_path('api-docs/api-docs.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $documentedOperations = [];

        foreach ($documentation['paths'] ?? [] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    $documentedOperations[strtoupper($method).' '.$path] = true;
                }
            }
        }

        $routeOperations = [];

        foreach (Route::getRoutes() as $route) {
            $uri = '/'.$route->uri();

            if (! str_starts_with($uri, '/api/') || in_array($uri, ['/api/documentation', '/api/oauth2-callback'], true)) {
                continue;
            }

            foreach ($route->methods() as $method) {
                if ($method !== 'HEAD') {
                    $routeOperations[$method.' '.$uri] = true;
                }
            }
        }

        $missingOperations = array_values(array_diff(array_keys($routeOperations), array_keys($documentedOperations)));
        $extraOperations = array_values(array_diff(array_keys($documentedOperations), array_keys($routeOperations)));

        $this->assertSame([], $missingOperations, 'API routes missing in OpenAPI documentation.');
        $this->assertSame([], $extraOperations, 'OpenAPI operations without a matching API route.');
    }

    public function test_openapi_operations_have_payload_and_response_examples(): void
    {
        $documentation = json_decode(
            file_get_contents(storage_path('api-docs/api-docs.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $responsesWithoutExamples = [];
        $requestBodiesWithoutExamples = [];

        foreach ($documentation['paths'] ?? [] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    continue;
                }

                $operationKey = strtoupper($method).' '.$path;

                foreach ($operation['responses'] ?? [] as $status => $response) {
                    if (! isset($response['content']['application/json']['example'])) {
                        $responsesWithoutExamples[] = "{$operationKey} response {$status}";
                    }
                }

                if (
                    isset($operation['requestBody'])
                    && ! isset($operation['requestBody']['content']['application/json']['example'])
                ) {
                    $requestBodiesWithoutExamples[] = $operationKey;
                }
            }
        }

        $this->assertSame([], $responsesWithoutExamples, 'OpenAPI responses missing examples.');
        $this->assertSame([], $requestBodiesWithoutExamples, 'OpenAPI request bodies missing examples.');
    }
}
