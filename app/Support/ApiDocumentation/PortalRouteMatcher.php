<?php

namespace App\Support\ApiDocumentation;

use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use Knuckles\Scribe\Matching\MatchedRoute;
use Knuckles\Scribe\Matching\RouteMatcher;

class PortalRouteMatcher extends RouteMatcher
{
    public const PORTALS = ['talent', 'client', 'admin'];

    public function getRoutes(array $routeRules = []): array
    {
        $matches = [];

        foreach (parent::getRoutes($routeRules) as $match) {
            $route = $match->getRoute();

            if ($route->uri() === 'api/v1/talent/onboarding/{step}') {
                foreach (OnboardingStepEnum::cases() as $step) {
                    $copy = clone $route;
                    $copy->setUri(str_replace('{step}', $step->value, $route->uri()));
                    $copy->defaults('step', $step->value);
                    $matches[] = new MatchedRoute($copy, $match->getRules());
                }

                continue;
            }

            if (! str_contains($route->uri(), 'auth/{type}/')) {
                $matches[] = $match;

                continue;
            }

            // Expand documentation only; keep the application's registered routes intact.
            foreach (self::PORTALS as $portal) {
                if (! preg_match('~^(?:'.($route->wheres['type'] ?? '.*').')$~D', $portal)) {
                    continue;
                }

                $copy = clone $route;
                $copy->setUri(str_replace('{type}', $portal, $route->uri()));
                $matches[] = new MatchedRoute($copy, $match->getRules());
            }
        }

        return $matches;
    }
}
