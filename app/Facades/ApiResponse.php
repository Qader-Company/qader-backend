<?php

namespace App\Facades;

use App\Facades\FacadesLogic\ApiResponseLogic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Facade;

/**
 * @method static JsonResponse apiFormat(?array $info = null, ?string $message = null, int $code = Response::HTTP_OK)
 * @method static JsonResponse notFound(?string $message = null)
 * @method static JsonResponse serverError(?string $message = null)
 * @method static JsonResponse validationError(mixed $errors, ?string $message = null)
 * @method static JsonResponse unauthorized(?string $message = null, int $code = Response::HTTP_UNAUTHORIZED)
 * @method static JsonResponse forbidden(?string $message = null, int $code = Response::HTTP_FORBIDDEN)
 * @method static JsonResponse failed(mixed $errors = null, ?string $message = null, int $code = Response::HTTP_BAD_REQUEST)
 * @method static JsonResponse success(mixed $data = null, ?string $message = null, int $code = Response::HTTP_OK)
 * @method static JsonResponse message(?string $message = null, int $code = Response::HTTP_OK)
 * @method static JsonResponse created(mixed $data = null, ?string $message = null)
 * @method static JsonResponse deleted(?string $message = null)
 * @method static JsonResponse updated(mixed $data = null, ?string $message = null)
 * @method static JsonResponse tooManyRequests(int $retryAfterSeconds)
 *
 * @see ApiResponseLogic
 */
class ApiResponse extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ApiResponseLogic::class;
    }
}
