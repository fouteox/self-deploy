<?php

namespace App\Http\Controllers;

use App\Enum;
use App\Http\Requests\UpdateSiteRequest;
use App\Jobs\UninstallSite;
use App\KeyPairGenerator;
use App\Models\Server;
use App\Models\Site;
use App\Models\SiteType;
use App\Server\PhpVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use ProtoneMedia\Splade\Facades\Toast;
use ProtoneMedia\Splade\SpladeTable;

/**
 * @codeCoverageIgnore Handled by Dusk tests.
 */
class SiteController extends Controller
{
    /**
     * Create the controller instance.
     */
    public function __construct()
    {
        $this->authorizeResource(Site::class, 'site');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Server $server)
    {
        return view('sites.index', [
            'server' => $server,
            'sites' => SpladeTable::for($server->sites()->with('latestDeployment'))
                ->withGlobalSearch(columns: ['address'])
                ->column('address', __('Address'))
                ->column('php_version_formatted', __('PHP Version'))
                ->column('latestDeployment.updated_at', __('Deployed'))
                ->rowLink(fn (Site $site) => route('servers.sites.show', [$server, $site]))
                ->selectFilter('php_version', $server->installedPhpVersions(), __('PHP Version'))
                ->defaultSort('address')
                ->paginate(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, Server $server, KeyPairGenerator $keyPairGenerator)
    {
        if (! $this->team()->subscriptionOptions()->canCreateSiteOnServer($server)) {
            Toast::center()
                ->backdrop()
                ->autoDismiss(0)
                ->warning(__('You have reached the maximum number of sites on this server for your current subscription plan.'));

            return to_route('servers.sites.index', $server);
        }

        $deployKeyUuid = Str::uuid()->toString();

        $keyPair = Cache::remember(
            key: "deploy-key-{$server->id}-{$deployKeyUuid}",
            ttl: config('session.lifetime') * 60,
            callback: fn () => $keyPairGenerator->ed25519()
        );

        return view('sites.create', [
            'uuid' => Str::uuid()->toString(),
            'deployKey' => $keyPair,
            'deployKeyUuid' => $deployKeyUuid,
            'server' => $server,
            'phpVersions' => $server->installedPhpVersions(),
            'types' => Enum::options(SiteType::class),
            'hasGithubCredentials' => $this->user()->hasGithubCredentials(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Server $server, Site $site)
    {
        return view('sites.show', [
            'server' => $server,
            'site' => $site,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Server $server, Site $site)
    {
        if ($site->pending_caddyfile_update_since?->diffInMinutes() > 3) {
            $site->forceFill(['pending_caddyfile_update_since' => null])->saveQuietly();
        }

        return view('sites.edit', [
            'server' => $server,
            'site' => $site,
            'phpVersions' => $server->installedPhpVersions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSiteRequest $request, Server $server, Site $site)
    {
        $data = $request->validated();

        if ($site->type !== SiteType::Wordpress) {
            $site->fill([
                'repository_url' => $data['repository_url'] ?? $site->repository_url,
                'repository_branch' => $data['repository_branch'] ?? $site->repository_branch,
            ]);
        }

        $newPhpVersion = PhpVersion::from($data['php_version']);

        $this->logActivity(__("Updated site ':address' on server ':server'", ['address' => $site->address, 'server' => $server->name]), $site);

        if ($site->php_version !== $newPhpVersion || $site->web_folder !== $data['web_folder']) {
            $site->updateCaddyfile($newPhpVersion, $data['web_folder'], $this->user());

            Toast::info(__('The site settings are being saved. The Caddyfile will be updated and the site will be deployed.'));

            return to_route('servers.sites.edit', [$server, $site]);
        }

        if (! $site->isDirty()) {
            Toast::info(__('No changes were made.'));

            return to_route('servers.sites.edit', [$server, $site]);
        }

        $site->save();
        $deployment = $site->deploy(user: $this->user());

        Toast::info(__('The site settings have been saved and the site is being deployed.'));

        return to_route('servers.sites.deployments.show', [$server, $site, $deployment]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Server $server, Site $site)
    {
        $site->delete();

        dispatch(new UninstallSite($server, $site->path));

        $this->logActivity(__("Deleted site ':address' from server ':server'", ['address' => $site->address, 'server' => $server->name]), $site);

        Toast::message(__('The site is deleted and will be uninstalled from the server shortly.'));

        return to_route('servers.sites.index', $server);
    }
}
