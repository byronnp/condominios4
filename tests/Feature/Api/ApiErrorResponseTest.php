<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class ApiErrorResponseTest extends TestCase
{
    public function test_api_not_found_errors_use_standard_error_response(): void
    {
        $response = $this->getJson('/api/route-that-does-not-exist');

        $response
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Recurso no encontrado.')
            ->assertJsonPath('code', 'not_found')
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
                'meta',
                'code',
            ]);
    }
}
