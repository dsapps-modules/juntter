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
        $response = $client->get('establishment');

        $this->assertIsArray($response);
    }
}
