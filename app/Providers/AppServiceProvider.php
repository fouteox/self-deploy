<?php

namespace App\Providers;

use App\Models;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;

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
        $this->enableSafetyMechanisms();

        Blade::anonymousComponentPath(__DIR__.'/../../resources/views/filament/components');

        //        $this->app->bind(GithubProvider::class, fn () => Socialite::driver('github'));

        /** @var Factory $validatorFactory */
        $validatorFactory = app(Factory::class);

        // Set value names whenever a new Validator instance is created.
        $validatorFactory->resolver(function (Translator $translator, array $data, array $rules, array $messages, array $attributes) {
            return tap(new Validator($translator, $data, $rules, $messages, $attributes), function (Validator $validator) {
                $validator->setValueNames(trans('validation.value_names'));
            });
        });

        Arr::macro('explodePaths', function ($value = null) {
            return Collection::make(explode(PHP_EOL, $value ?? ''))
                ->map(fn ($item) => rtrim(trim($item), '/'))
                ->filter(fn ($item) => $item !== '')
                ->unique()
                ->values()
                ->all();
        });

        Str::macro('generateWordpressKey', function ($length = 64) {
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
            $max = strlen($chars) - 1;

            $key = '';

            for ($i = 0; $i < $length; $i++) {
                $key .= substr($chars, random_int(0, $max), 1);
            }

            return $key;
        });

        URL::macro('relativeSignedRoute', function (string $name, mixed $parameters = []): string {
            $host = rtrim(config('eddy.webhook_url') ?: config('app.url'), '/');

            return $host.URL::signedRoute($name, $parameters, absolute: false);
        });
    }

    private function enableSafetyMechanisms(): void
    {
        if ($this->app->runningInConsole()) {
            // Log slow commands.
            $this->app[ConsoleKernel::class]->whenCommandLifecycleIsLongerThan(
                5000,
                function ($startedAt, $input, $status) {
                    // TODO: Add info about the command
                    Log::warning('A command took longer than 5 seconds.');
                }
            );
        } else {
            // Log slow requests.
            $this->app[HttpKernel::class]->whenRequestLifecycleIsLongerThan(
                5000,
                function ($startedAt, $request, $response) {
                    // TODO: Add info about the request
                    Log::warning('A request took longer than 5 seconds.');
                }
            );
        }

        // Everything strict, all the time.
        Model::shouldBeStrict();

        // But in production, log the violation instead of throwing an exception.
        if ($this->app->isProduction()) {
            Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
                $class = get_class($model);

                Log::info("Attempted to lazy load [$relation] on model [$class].");
            });
        }

        // Enforce a morph map instead of making it optional.
        Relation::enforceMorphMap([
            'backup_job' => Models\BackupJob::class,
            'backup' => Models\Backup::class,
            'cron' => Models\Cron::class,
            'daemon' => Models\Daemon::class,
            'database_user' => Models\DatabaseUser::class,
            'database' => Models\Database::class,
            'deployment' => Models\Deployment::class,
            'disk' => Models\Disk::class,
            'firewall_rule' => Models\FirewallRule::class,
            'server' => Models\Server::class,
            'site' => Models\Site::class,
            'team' => Models\Team::class,
            'user' => Models\User::class,
        ]);

        DB::listen(function ($query) {
            if ($query->time > 1000) {
                Log::warning('An individual database query exceeded 1 second.', [
                    'sql' => $query->sql,
                ]);
            }
        });
    }
}
