<?php

namespace App\Modules\V1\Talents\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Talents\Application\Services\ParseTalentResumeService;
use App\Modules\V1\Talents\Application\UseCases\GetTalentProfileUseCase;
use App\Modules\V1\Talents\Domain\Exceptions\ResumeParserUnavailableException;
use App\Modules\V1\Talents\Domain\Exceptions\ResumeParsingFailedException;
use App\Modules\V1\Talents\Presentation\Http\Requests\ParseTalentResumeRequest;
use App\Modules\V1\Talents\Presentation\Http\Resources\ParsedResumeResource;
use Illuminate\Http\JsonResponse;

class TalentResumeParserController extends Controller
{
    public function __invoke(
        ParseTalentResumeRequest $request,
        GetTalentProfileUseCase $profiles,
        ParseTalentResumeService $parser,
    ): JsonResponse {
        try {
            $data = $parser->execute(
                $profiles->execute($request->user()->id),
                $request->file('cv'),
            );
        } catch (ResumeParsingFailedException $exception) {
            return ApiResponse::validationError([
                'cv' => [$exception->getMessage()],
            ], $exception->getMessage());
        } catch (ResumeParserUnavailableException $exception) {
            return ApiResponse::failed(
                message: $exception->getMessage(),
                code: 503,
            );
        }

        return ApiResponse::success(
            new ParsedResumeResource($data),
            __('talents.resume_parsed'),
        );
    }
}
