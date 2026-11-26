<?php

namespace App\Providers;

use App\Facades\ApiResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('auth-login', function (Request $request) {
            $config = config('rate-limiting.login');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['max_attempts']
            )->by($this->loginKey($request));
        });

        RateLimiter::for('auth-register', function (Request $request) {
            $config = config('rate-limiting.register');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['max_attempts']
            )
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    $retryAfter = (int) ($headers['Retry-After'] ?? 60);

                    return ApiResponse::tooManyRequests(
                        $retryAfter,
                        __('auth.registration_throttle'),
                    );
                });
        });

        RateLimiter::for('auth-otp-send', function (Request $request) {
            $config = config('rate-limiting.otp_send');
            $email = (string) $request->input('email', '');

            return [
                Limit::perMinutes(
                    $config['decay_minutes'],
                    $config['per_email']
                )->by('email:'.$email),
                Limit::perMinutes(
                    $config['decay_minutes'],
                    $config['per_ip']
                )->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('auth-otp-verify', function (Request $request) {
            $config = config('rate-limiting.otp_verify');
            $email = (string) ($request->input('email') ?? $request->user()?->email ?? '');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['max_attempts']
            )->by($email.'|'.$request->ip());
        });

        RateLimiter::for('auth-reset-password', function (Request $request) {
            $config = config('rate-limiting.reset_password');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['max_attempts']
            )->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('auth-refresh', function (Request $request) {
            $config = config('rate-limiting.refresh');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['max_attempts']
            )->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('auth-logout', function (Request $request) {
            $config = config('rate-limiting.logout');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['max_attempts']
            )->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('public-job-submissions', function (Request $request) {
            $job = $request->route('job');
            $jobKey = $job instanceof UrlRoutable
                ? (string) $job->getRouteKey()
                : (string) $job;

            return [
                Limit::perMinute(5)->by('job:'.$jobKey.'|'.$request->ip()),
                Limit::perHour(30)->by('jobs|'.$request->ip()),
            ];
        });

        RateLimiter::for('talent-resume-parse', function (Request $request) {
            $config = config('rate-limiting.resume_parse');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['max_attempts'],
            )->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('public-hiring-support-requests', function (Request $request) {
            $config = config('rate-limiting.hiring_support_requests');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['max_attempts'],
            )->by($request->ip());
        });

        RateLimiter::for('payment-webhooks', fn (Request $request) => Limit::perMinute(
            (int) config('payments.webhook.max_attempts_per_minute', 120),
        )->by((string) $request->route('provider').'|'.$request->ip()));
    }

    private function loginKey(Request $request): string
    {
        $email = (string) $request->input('email', '');

        return $email.'|'.$request->ip();
    }
}
