<?php

namespace App\Modules\V1\ProfessionalFields\Infrastructure\Providers;

use App\Modules\V1\ProfessionalFields\Application\Factories\ProfessionalFieldFormValidationFactory;
use App\Modules\V1\ProfessionalFields\Application\Services\ProfessionalFieldFormPolicyProvider;
use App\Modules\V1\ProfessionalFields\Domain\Repositories\ProfessionalFieldRepositoryInterface;
use App\Modules\V1\ProfessionalFields\Domain\Repositories\ProfessionalFormFieldRepositoryInterface;
use App\Modules\V1\ProfessionalFields\Infrastructure\Persistence\EloquentProfessionalFieldRepository;
use App\Modules\V1\ProfessionalFields\Infrastructure\Persistence\QueryBuilderProfessionalFormFieldRepository;
use App\Modules\V1\TalentApplications\Application\Contracts\ApplicationFormFieldPolicyProviderInterface;
use App\Modules\V1\TalentApplications\Application\Contracts\ApplicationFormValidationFactoryInterface;
use Illuminate\Support\ServiceProvider;

class ProfessionalFieldModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProfessionalFieldRepositoryInterface::class,
            EloquentProfessionalFieldRepository::class,
        );
        $this->app->bind(
            ProfessionalFormFieldRepositoryInterface::class,
            QueryBuilderProfessionalFormFieldRepository::class,
        );
        $this->app->bind(
            ApplicationFormFieldPolicyProviderInterface::class,
            ProfessionalFieldFormPolicyProvider::class,
        );
        $this->app->bind(
            ApplicationFormValidationFactoryInterface::class,
            ProfessionalFieldFormValidationFactory::class,
        );
    }
}
