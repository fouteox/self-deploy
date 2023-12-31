<?php

namespace App\Http\Controllers;

use App\Filament\Resources\CredentialResource;
use App\Provider;
use App\SourceControl\Entities\GitRepository;
use App\SourceControl\Github;
use App\SourceControl\ProviderFactory;
use Exception;
use Filament\Notifications\Notification;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Two\GithubProvider;

class GithubController extends Controller
{
    /**
     * Redirects the user to the Github OAuth page.
     */
    public function redirect(GithubProvider $githubProvider): RedirectResponse
    {
        if ($this->user()->credentials()->where('provider', Provider::Github)->exists()) {
            Notification::make()
                ->title(__('You already have a Github account connected.'))
                ->warning()
                ->send();

            return redirect(CredentialResource::getUrl());
        }

        return $githubProvider->setScopes([
            'repo',
            'admin:public_key',
            'admin:repo_hook',
        ])->redirect();
    }

    /**
     * Handles the callback from Github.
     *
     * @throws BindingResolutionException
     */
    public function callback(GithubProvider $githubProvider)
    {
        if ($this->user()->credentials()->where('provider', Provider::Github)->exists()) {
            Notification::make()
                ->title(__('You already have a Github account connected.'))
                ->warning()
                ->send();

            return redirect(CredentialResource::getUrl());
        }

        try {
            $user = $githubProvider->user();
        } catch (ClientException) {
            Notification::make()
                ->title(__('Failed to connect to Github.'))
                ->warning()
                ->send();

            return redirect(CredentialResource::getUrl());
        }

        if (! app()->makeWith(Github::class, ['token' => $user->token])->canConnect()) {
            Notification::make()
                ->title(__('Failed to connect to Github.'))
                ->warning()
                ->send();

            return redirect(CredentialResource::getUrl());
        }

        $this->user()->credentials()->create([
            'name' => Provider::Github->getDisplayName(),
            'provider' => Provider::Github,
            'credentials' => [
                'id' => $user->getId(),
                'token' => $user->token,
            ],
        ]);

        Notification::make()
            ->title(__('Successfully connected to Github.'))
            ->success()
            ->send();

        return redirect(CredentialResource::getUrl());
    }

    /**
     * Returns a list of all the repositories that the user has access to.
     *
     * @throws Exception
     */
    public function repositories(ProviderFactory $providerFactory)
    {
        $githubCredentials = $this->user()->githubCredentials;

        if (! $githubCredentials) {
            return [];
        }

        $github = $providerFactory->forCredentials($githubCredentials);

        return Cache::remember("github_repositories.$githubCredentials->id", 5 * 60, function () use ($github) {
            $repositories = rescue(fn () => $github->findRepositories(), Collection::make(), false);

            return $repositories->mapWithKeys(function (GitRepository $repository) {
                return [$repository->url => $repository->name];
            })->all();
        });
    }
}
