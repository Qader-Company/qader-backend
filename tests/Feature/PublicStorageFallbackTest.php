<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicStorageFallbackTest extends TestCase
{
    public function test_it_serves_a_file_from_the_public_disk_when_the_web_server_cannot_follow_the_storage_link(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('partners/logo.png', 'public-image-content');

        $response = $this->get('/storage/partners/logo.png');

        $response
            ->assertOk()
            ->assertHeader('cache-control', 'max-age=86400, public');

        $this->assertSame('public-image-content', $response->streamedContent());
    }

    public function test_it_returns_not_found_for_a_missing_public_file(): void
    {
        Storage::fake('public');

        $this->get('/storage/partners/missing.png')->assertNotFound();
    }
}
