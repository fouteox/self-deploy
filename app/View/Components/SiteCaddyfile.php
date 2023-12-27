<?php

namespace App\View\Components;

use App\Models\Site;
use Closure;
use Illuminate\Contracts\View\View;

class SiteCaddyfile extends Component implements Caddyfile
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(public Site $site)
    {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|string|Closure
    {
        return view('components.server.site-caddyfile');
    }
}
