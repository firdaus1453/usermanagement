<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Rate limiting for login attempts
        // Based on email + session ID
        RateLimiter::for('web-login', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email', 'unknown')));
            $sessId = $request->session()->getId() ?: 'no-session';

            // 5 attempts per minute per (email + session)
            return Limit::perMinute(5)->by("web-login:{$email}|{$sessId}");
        });
    }
}
