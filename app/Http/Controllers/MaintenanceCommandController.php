<?php

namespace App\Http\Controllers;

use App\Facades\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MaintenanceCommandController extends Controller
{
    public function __invoke(string $command): JsonResponse
    {
        [$artisanCommand, $parameters] = match ($command) {
            'migrate' => ['migrate', ['--force' => true]],
            'seed' => ['db:seed', ['--force' => true]],
            'optimize-clear' => ['optimize:clear', []],
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
