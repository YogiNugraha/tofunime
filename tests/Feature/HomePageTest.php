<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_correctly_with_millishion_elements(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('TOFUNIME');
        $response->assertSee('Update Episode Terbaru');
        $response->assertSee('Studio & Direktori Pilihan', false);
        $response->assertSee('Studio Bind');
        $response->assertSee('Tonton Sekarang');
    }
}
