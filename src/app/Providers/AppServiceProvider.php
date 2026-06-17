<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Validation\Rules\Password::defaults(function () {
            $rule = \Illuminate\Validation\Rules\Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();

            return app()->isProduction() ? $rule->uncompromised() : $rule;
        });

        \Dedoc\Scramble\Scramble::afterOpenApiGenerated(function (\Dedoc\Scramble\Support\Generator\OpenApi $openApi) {
            foreach ($openApi->components->schemas as $key => $schema) {
                if (str_ends_with($key, 'Request')) {
                    $newTitle = 'Requests.' . $key;
                } elseif (str_ends_with($key, 'Resource') || str_ends_with($key, 'Collection')) {
                    $newTitle = 'Resources.' . $key;
                } else {
                    $newTitle = 'Models.' . $key;
                }
                
                // Only modify the title to properly group them in Stoplight Elements
                // without breaking the $ref connections
                $schema->setTitle($newTitle);
            }
        });
    }
}
