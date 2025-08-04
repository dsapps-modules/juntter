<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ApiClientService;


class ApiClientServiceTest extends TestCase
{
    /** @test */
    public function it_can_fetch_token_and_make_get_request()
    {
        $this->withoutExceptionHandling();

        $client = new ApiClientService();
        $response = $client->get('marketplace/establishments');

        $this->assertIsArray($response);
        echo "\n\nResponse from API:\n";
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
}
