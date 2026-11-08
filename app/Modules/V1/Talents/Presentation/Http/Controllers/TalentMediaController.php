<?php

namespace App\Modules\V1\Talents\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\Talents\Application\UseCases\GetTalentProfileUseCase;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Talents\Domain\Models\TalentProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TalentMediaController extends Controller
{
    public function download(
        Request $request,
        Media $media,
        GetTalentProfileUseCase $profiles,
    ): StreamedResponse {
        $profile = $profiles->execute($request->user()->id);
        $owner = $media->load('model')->model;
        $owned = $owner instanceof Talent && $owner->is($profile)
            || $owner instanceof TalentProject && $owner->talent_id === $profile->id
            || $owner instanceof TalentApplication && $owner->talent_id === $profile->id;

        abort_unless($owned, 404);

        return Storage::disk($media->disk)->download(
            $media->getPathRelativeToRoot(),
            $media->file_name,
        );
    }
}
