<?php

namespace App\Http\Controllers;

use App\Facades\ApiResponse;
use Illuminate\Database\Seeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MaintenanceCommandController extends Controller
{
    public function __invoke(Request $request, string $command): JsonResponse
    {
        $apiKeyName = null;
        $seederClass = null;

        if ($command === 'create-api-key') {
            $apiKeyName = $request->validate([
                'name' => ['required', 'string', 'regex:/^[a-z0-9-]{1,255}$/'],
            ])['name'];
        }

        if ($command === 'seed') {
            $seederClass = $this->resolveSeederClass(
                $request->validate([
                    'seeder' => [
                        'nullable',
                        'string',
                        'regex:/^[A-Za-z_][A-Za-z0-9_]*$/',
                    ],
                ])['seeder'] ?? null
            );
        }

        [$artisanCommand, $parameters] = match ($command) {
            'migrate' => ['migrate', ['--force' => true]],
            'migrate-fresh' => ['migrate:fresh', ['--force' => true]],
            'rollback' => ['migrate:rollback', ['--force' => true]],
            'seed' => ['db:seed', array_filter([
                '--class' => $seederClass,
                '--force' => true,
            ], static fn (mixed $value): bool => $value !== null)],
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
                'seeder' => $seederClass,
                'output' => trim(Artisan::output()),
            ],
            message: "The {$artisanCommand} command completed successfully.",
        );
    }

    /** @return class-string<Seeder>|null */
    private function resolveSeederClass(?string $seeder): ?string
    {
        if ($seeder === null) {
            return null;
        }

        $class = "Database\\Seeders\\{$seeder}";

        if (! class_exists($class) || ! is_subclass_of($class, Seeder::class)) {
            throw ValidationException::withMessages([
                'seeder' => ['The selected seeder does not exist.'],
            ]);
        }

        return $class;
    }
}
