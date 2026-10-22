<?php

namespace App\Providers;

use App\Modules\V1\Authentication\Presentation\Http\Requests\RegisterRequest;
use App\Modules\V1\Clients\Presentation\Http\Requests\RegisterClientRequest;
use App\Modules\V1\Talents\Presentation\Http\Requests\RegisterTalentRequest;
use App\Modules\V1\Talents\Presentation\Http\Requests\SaveTalentOnboardingStepRequest;
use App\Support\ApiDocumentation\PortalExports;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Knuckles\Scribe\Scribe;
use Knuckles\Scribe\ScribeServiceProvider;

class LocalApiDocumentationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(ScribeServiceProvider::class);

        Scribe::instantiateFormRequestUsing(function (string $class, RoutingRoute $route): FormRequest {
            // Resolve each portal's validation rules without executing its registration request.
            if ($class === RegisterRequest::class) {
                return str_contains($route->uri(), '/client/')
                    ? new RegisterClientRequest
                    : new RegisterTalentRequest;
            }

            if ($class === SaveTalentOnboardingStepRequest::class) {
                return $class::create('/'.$route->uri(), 'POST');
            }

            return new $class;
        });

        Scribe::afterGenerating([PortalExports::class, 'write']);
    }

    public function boot(): void
    {
        Route::get('/docs/{portal}.openapi', function (string $portal) {
            $file = Storage::disk('local')->path('scribe/'.$portal.'.openapi.yaml');
            abort_unless(is_file($file), 404, 'Generate the API documentation first.');

            $response = response()->file($file, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);

            $response->setPrivate();

            return $response;
        })->whereIn('portal', ['talent', 'client', 'admin', 'marketing'])->name('scribe.portal.openapi');
    }
}
