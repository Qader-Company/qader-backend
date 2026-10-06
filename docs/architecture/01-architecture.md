# 01 — Architecture and Layers

## Project Structure

Qader is a versioned Laravel API organized into modules:

```text
app/Modules/V1/<Module>/
├── Application/
├── Domain/
├── Infrastructure/
└── Presentation/
```

A module represents a business area, such as `Authentication`, `AccessControl`, or `Talents`. It must not be named after a single UI screen.

## Model and Migration Ownership

An Eloquent model belongs in the Domain layer of the module that owns its business behavior. For example:

- `Talents/Domain/Models/Talent` owns the `talents` aggregate and its profile-detail models.
- `Clients/Domain/Models/Client` owns the `clients` profile.
- `Admins/Domain/Models/Admin` owns the `admins` profile.

Laravel migrations remain in the framework-standard `database/migrations` directory so they execute in chronological order. A migration's location does not determine business ownership; the module model and use cases do. Shared identity (`User`) remains shared, while reference-data models are owned by their dedicated modules.

## Layer Responsibilities

### Presentation

The HTTP boundary only: route files, controllers, form requests, and API resources.

Controllers coordinate the request: receive validated data, call a use case, and return an `ApiResponse` or Resource. They do not contain business rules or complex queries.

### Application

The feature workflow: use cases such as `RegisterTalentUseCase`, services coordinating multiple repositories or processes, DTOs, validators, and jobs when needed.

This layer owns transactions that affect multiple tables and the ordering of workflow steps, for example creating an account, creating a profile, then sending an OTP after the transaction succeeds.

### Domain

The business language and rules: models, repository contracts/interfaces, enums, value objects, and business invariants independent of HTTP.

In Qader's current pattern, an Eloquent model may live in Domain. However, Domain must never call a Controller, Request, Resource, or Infrastructure implementation.

### Infrastructure

Technical implementation details: Eloquent repository implementations, service providers and interface bindings, and integrations such as Google, mail, and storage.

Infrastructure implements contracts defined in Domain. It does not impose implementation details on Application.

## Dependency Direction

```text
Presentation → Application → Domain
Infrastructure ─────────────┘
```

- Presentation may call Application and Domain value objects only.
- Application depends on Domain contracts, not concrete Eloquent repositories.
- Infrastructure depends on Domain to implement its contracts.
- Domain does not depend on Presentation, Application, or Infrastructure.

## Shared Code

Only code genuinely reused by more than one module belongs in `app/Modules/Shared` or `app/Support`, for example response utilities, traits, rules, or caching abstractions. Do not move code to Shared merely because it may be reused later.

## One Authentication Endpoint, Portal-Specific Behavior

Authentication keeps a shared endpoint where the operation is genuinely shared, for example:

```text
POST /api/v1/auth/{type}/register
```

The controller must not branch on the portal. Instead, two factories resolve the portal implementation from `{type}`:

1. `PortalRegistrationRequestFactory` selects the portal Form Request, such as `RegisterTalentRequest`.
2. `PortalRegistrationFactory` selects a portal registration handler, which calls the owning module's account-creation use case.

Therefore, `RegisterUseCase` owns the shared workflow only: start the transaction, ask the selected handler to create the account and its initial profile, create the verification OTP/token, and return the result. It must not create `Talent` or `Client` models itself.

Use the same selector pattern whenever behavior differs by portal. The selector is the only place that maps a portal enum to its implementation; the controller and shared use case remain unchanged when a portal gains its own rules or workflow.

## Practical Rules

- Do not create a module for a tiny screen; create one for an independent business area.
- Do not put Talent-specific logic in Authentication unless it is directly part of registration or token lifecycle.
- Do not place queries in Resources or Form Requests.
- Every class must have one responsibility that can be described in one sentence.
- `Model::preventLazyLoading()` is enabled, so every relation exposed by a Resource must be loaded deliberately in a use case or repository.
