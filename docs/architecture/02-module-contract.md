# 02 — Module Contract

## Standard Structure

```text
app/Modules/V1/Talents/
├── Application/
│   ├── DTOs/
│   ├── Services/
│   └── UseCases/
├── Domain/
│   ├── Models/
│   ├── Repositories/
│   └── ValueObjects/
├── Infrastructure/
│   ├── Persistence/Repositories/
│   └── Providers/
└── Presentation/
    └── Http/
        ├── Controllers/
        ├── Requests/
        └── Resources/
```

Not every folder must exist on day one. Add only what the module needs, while keeping the same locations and naming conventions.

## Starting a New Module

Use the project command when appropriate:

```powershell
php artisan make:module Talents --model=Talent
```

The command creates the initial folders, repository contract, Eloquent implementation, and provider. It is a starting point, not a complete design.

## Repository Rule

Application depends on an interface:

```php
public function __construct(private TalentRepositoryInterface $talents) {}
```

The provider binds it to an implementation:

```php
$this->app->bind(TalentRepositoryInterface::class, EloquentTalentRepository::class);
```

Do not add a repository simply because a model exists. Add one when queries are reusable, need portal scoping, or may later have more than one data source.

## Use Case Rule

A use case name is a clear action with one business outcome:

- `RegisterTalentUseCase`
- `SaveTalentProfessionalProfileUseCase`
- `SubmitTalentApplicationUseCase`

Avoid broad classes such as `TalentService` that collect unrelated operations unless those operations have a clear shared responsibility.

## Requests and Resources

- A Request handles validation and request-level authorization only.
- A Resource defines response shape only.
- Never pass `$request->all()`; use `$request->validated()`.
- Never expose passwords, tokens, or internal-only fields in a Resource.

## Provider Registration

A module that has bindings or listeners must have a provider at:

```text
Infrastructure/Providers/<Module>ModuleServiceProvider.php
```

Register it once in `config/modules.php`. Do not register bindings in Controllers or route files.

## Module Completion Checklist

- [ ] The business scope is clear and not duplicated elsewhere.
- [ ] Routes are in the correct portal.
- [ ] Validation is in a Request.
- [ ] Business logic is in a use case/service, not a Controller.
- [ ] Eloquent relations are loaded explicitly before Resources use them.
- [ ] The Provider is registered when bindings exist.
- [ ] A dedicated migration exists when schema changes.
- [ ] The relevant flow document is updated for visible or sensitive behavior.
