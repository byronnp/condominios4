<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class SwaggerDocumentationTest extends TestCase
{
    public function test_swagger_ui_route_is_available(): void
    {
        $response = $this->get('/api/documentation');

        $response->assertOk();
    }
}
