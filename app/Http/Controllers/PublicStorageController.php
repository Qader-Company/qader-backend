<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicStorageController extends Controller
{
    public function __invoke(string $path): StreamedResponse
    {
        abort_if(
            $path === ''
            || str_contains($path, '..')
            || str_contains($path, "\0")
            || str_contains($path, '\\'),
            404,
        );

        $disk = Storage::disk('public');

        abort_unless($disk->fileExists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
