<?php

namespace App\Support\ApiDocumentation;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;

class EndpointNames
{
    public static function for(Route $route): string
    {
        $parts = array_slice(explode('/', $route->uri()), 2);
        $method = $route->methods()[0];

        if ($parts[0] === 'auth') {
            return match ($route->getActionMethod()) {
                'register' => 'Register',
                'login' => 'Login',
                'socialLogin' => 'Social Login',
                'logout' => 'Logout',
                'me' => 'Get Current User',
                'refresh' => 'Refresh Token',
                'sendOtp' => 'Send OTP',
                'verifyEmail' => 'Verify Email',
                'verifyPasswordReset' => 'Verify Password Reset OTP',
                'resetPassword' => 'Reset Password',
                default => Str::headline($route->getActionMethod()),
            };
        }

        array_shift($parts); // Portal.
        $module = $parts[0];

        if ($module === 'profile') {
            if (($parts[1] ?? '') === 'media') {
                return 'Download Profile Media';
            }

            return $method === 'GET' ? 'Get Profile' : 'Update Profile';
        }

        if ($module === 'onboarding') {
            return match ($parts[1] ?? '') {
                '' => 'Get Onboarding Progress',
                'form-fields' => 'Get Onboarding Form Fields',
                'professional-field' => 'Select Professional Field',
                'application' => 'Get Application',
                default => 'Save '.Str::headline($parts[1]),
            };
        }

        if ($module === 'fields' && ($parts[1] ?? '') === 'form-fields') {
            return 'Get Professional Field Form Configuration';
        }

        $resource = in_array($module, ['master-data', 'access-control'], true) ? $parts[1] : $module;
        $resource = $resource === 'fields' ? 'professional-fields' : $resource;
        $singular = Str::headline(Str::singular($resource));

        if (end($parts) === 'status') {
            return 'Update '.$singular.' Status';
        }

        return match ($method) {
            'GET' => str_contains($route->uri(), '{') ? 'Get '.$singular : 'List '.Str::headline($resource),
            'POST' => 'Create '.$singular,
            'PUT', 'PATCH' => 'Update '.$singular,
            'DELETE' => 'Delete '.$singular,
            default => Str::headline($route->getActionMethod()),
        };
    }
}
