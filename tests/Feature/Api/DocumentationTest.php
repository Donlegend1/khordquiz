<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class DocumentationTest extends TestCase
{
    public function test_openapi_spec_lists_the_authentication_routes(): void
    {
        $this->artisan('l5-swagger:generate')->assertSuccessful();

        $spec = json_decode((string) file_get_contents(storage_path('api-docs/api-docs.json')), true);

        $this->assertSame('KhordQuiz API', $spec['info']['title']);
        $this->assertArrayHasKey('/api/v1/register', $spec['paths']);
        $this->assertArrayHasKey('/api/v1/login', $spec['paths']);
        $this->assertArrayHasKey('/api/v1/user', $spec['paths']);
        $this->assertArrayHasKey('/api/v1/logout', $spec['paths']);
        $this->assertArrayHasKey('/api/v1/profile', $spec['paths']);
        $this->assertArrayHasKey('/api/v1/forgot-password', $spec['paths']);
        $this->assertArrayHasKey('/api/v1/verify-otp', $spec['paths']);
        $this->assertArrayHasKey('/api/v1/reset-password', $spec['paths']);
        $this->assertArrayHasKey('bearerAuth', $spec['components']['securitySchemes']);
    }

    public function test_swagger_ui_is_available(): void
    {
        $this->get('/api/documentation')->assertOk();
    }
}
