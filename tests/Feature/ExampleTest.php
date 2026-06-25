<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * La raíz muestra la home pública del cliente.
     */
    public function test_the_home_page_renders(): void
    {
        $this->get('/')->assertOk()->assertSee('RentCar');
    }
}
