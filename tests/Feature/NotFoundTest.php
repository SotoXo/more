<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NotFoundTest extends TestCase
{
    public function test_missing_pages_keep_http_404_and_render_the_branded_page(): void
    {
        $this->get('/QA_TEST_missing_page')->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Errors/NotFound'));
        $this->withHeaders(['X-Inertia' => 'true'])->get('/QA_TEST_missing_page')
            ->assertNotFound()->assertJsonPath('component', 'Errors/NotFound');
    }

    public function test_json_clients_keep_a_json_404(): void
    {
        $this->getJson('/QA_TEST_missing_api')->assertNotFound()->assertJsonStructure(['message']);
    }
}
