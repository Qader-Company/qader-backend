<?php

namespace App\Http\Controllers;

use App\Facades\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MaintenanceCommandController extends Controller
{
    public function __invoke(Request $request, string $command): JsonResponse
    {
        $apiKeyName = null;

        if ($command === 'create-api-key') {
            $apiKeyName = $request->validate([
                'name' => ['required', 'string', 'regex:/^[a-z0-9-]{1,255}$/'],
            ])['name'];
        }

        [$artisanCommand, $parameters] = match ($command) {
            'migrate' => ['migrate', ['--force' => true]],
            'migrate-fresh' => ['migrate:fresh', ['--force' => true]],
            'rollback' => ['migrate:rollback', ['--force' => true]],
            'seed' => ['db:seed', ['--force' => true]],
            'optimize-clear' => ['optimize:clear', []],
            'create-api-key' => ['apikey:generate', ['name' => $apiKeyName]],
            'queue-work' => ['queue:work', ['--stop-when-empty' => true]],
            default => throw new HttpException(422, 'Unsupported maintenance command.'),
        };

        $exitCode = Artisan::call($artisanCommand, $parameters);

        if ($exitCode !== 0) {
            return ApiResponse::failed(
                errors: ['output' => trim(Artisan::output())],
                message: "The {$artisanCommand} command failed.",
                code: 500,
            );
        }

        return ApiResponse::success(
            data: [
                'command' => $artisanCommand,
                'output' => trim(Artisan::output()),
            ],
            message: "The {$artisanCommand} command completed successfully.",
        );
    }
}
