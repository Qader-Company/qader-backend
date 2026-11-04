<?php

namespace App\Modules\V1\Talents\Application\Services;

use App\Modules\V1\Talents\Application\Contracts\ResumeParserGatewayInterface;
use App\Modules\V1\Talents\Domain\Exceptions\ResumeParsingFailedException;
use App\Modules\V1\Talents\Domain\Models\Talent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ParseTalentResumeService
{
    public function __construct(
        private readonly ResumeParserGatewayInterface $parser,
        private readonly ResumeAutocompleteMapper $mapper,
    ) {}

    /** @return array<string, mixed> */
    public function execute(Talent $talent, UploadedFile $resume): array
    {
        $temporaryPath = $this->temporaryPath($resume);

        try {
            $parsed = $this->parser->parse($temporaryPath);
            $mapped = $this->mapper->map($parsed, $talent);

            if (! $this->mapper->hasUsefulData($mapped)) {
                throw new ResumeParsingFailedException(__('talents.resume_parse_failed'));
            }

            return [
                'cv' => [
                    'file_name' => $resume->getClientOriginalName(),
                    'mime_type' => $resume->getMimeType(),
                    'size' => $resume->getSize(),
                ],
                ...$mapped,
            ];
        } finally {
            File::delete($temporaryPath);
        }
    }

    private function temporaryPath(UploadedFile $resume): string
    {
        $directory = storage_path('framework/resume-parser');
        File::ensureDirectoryExists($directory);
        $extension = strtolower($resume->getClientOriginalExtension());
        $path = $directory.'/'.Str::uuid().($extension === '' ? '' : '.'.$extension);

        File::copy($resume->getRealPath(), $path);

        return $path;
    }
}
