<?php

namespace App\Http\Controllers;

use App\Models\PendingDeploymentException;
use App\Models\Site;

/**
 * @codeCoverageIgnore Handled by Dusk tests.
 */
class SiteDeploymentController extends Controller
{
    /**
     * Deploy the site with the given token.
     *
     * @throws PendingDeploymentException
     */
    public function deployWithToken(Site $site, string $token)
    {
        if ($token !== $site->deploy_token) {
            abort(403);
        }

        $site->deploy();

        return response()->noContent(200);
    }
}
