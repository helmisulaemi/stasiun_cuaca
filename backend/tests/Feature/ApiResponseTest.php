<?php

namespace Tests\Feature;

use App\Exceptions\Handler;
use App\Support\Api\ApiResponse;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_success_envelope_structure(): void
    {
        $response = ApiResponse::success(['foo' => 'bar']);

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertEquals(['foo' => 'bar'], $json['data']);
        $this->assertArrayHasKey('request_id', $json['meta']);
        $this->assertNotEmpty($json['meta']['request_id']);
        $this->assertEquals(200, $response->status());
    }

    public function test_created_envelope_structure(): void
    {
        $response = ApiResponse::success(['id' => '123'], 201);

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertEquals(201, $response->status());
    }

    public function test_error_envelope_structure(): void
    {
        $response = ApiResponse::error(422, 'VALIDATION_ERROR', 'Invalid.');

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertEquals('VALIDATION_ERROR', $json['error']['code']);
        $this->assertEquals('Invalid.', $json['error']['message']);
        $this->assertArrayHasKey('request_id', $json['meta']);
        $this->assertEquals(422, $response->status());
    }

    public function test_error_with_details(): void
    {
        $response = ApiResponse::error(422, 'VALIDATION_ERROR', 'Invalid.', [
            ['field' => 'email', 'code' => 'INVALID_VALUE', 'message' => 'Bad format.'],
        ]);

        $json = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('details', $json['error']);
        $this->assertCount(1, $json['error']['details']);
    }

    public function test_handler_unauthenticated(): void
    {
        $response = Handler::unauthenticated();

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertEquals('UNAUTHENTICATED', $json['error']['code']);
        $this->assertEquals(401, $response->status());
    }

    public function test_handler_forbidden(): void
    {
        $response = Handler::forbidden();

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertEquals('FORBIDDEN', $json['error']['code']);
        $this->assertEquals(403, $response->status());
    }

    public function test_handler_not_found(): void
    {
        $response = Handler::notFound();

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertEquals('NOT_FOUND', $json['error']['code']);
        $this->assertEquals(404, $response->status());
    }

    public function test_handler_conflict(): void
    {
        $response = Handler::conflict('Duplikat.');

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertEquals('CONFLICT', $json['error']['code']);
        $this->assertEquals(409, $response->status());
    }

    public function test_handler_validation_error(): void
    {
        $exception = \Illuminate\Validation\ValidationException::withMessages([
            'email' => ['The email field is required.'],
        ]);

        $response = Handler::validationError($exception);

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertEquals('VALIDATION_ERROR', $json['error']['code']);
        $this->assertArrayHasKey('details', $json['error']);
        $this->assertEquals(422, $response->status());
    }

    public function test_handler_rate_limited(): void
    {
        $response = Handler::rateLimited(30);

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertEquals('RATE_LIMITED', $json['error']['code']);
    }

    public function test_handler_internal_server_error(): void
    {
        $response = Handler::internalServerError();

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertEquals('INTERNAL_SERVER_ERROR', $json['error']['code']);
        $this->assertEquals(500, $response->status());
    }

    public function test_every_response_has_request_id(): void
    {
        $success = json_decode(ApiResponse::success(['x' => 1])->getContent(), true);
        $error = json_decode(ApiResponse::error(500, 'INTERNAL', 'Oops.')->getContent(), true);

        $this->assertArrayHasKey('request_id', $success['meta']);
        $this->assertNotEmpty($success['meta']['request_id']);
        $this->assertArrayHasKey('request_id', $error['meta']);
        $this->assertNotEmpty($error['meta']['request_id']);
    }
}
