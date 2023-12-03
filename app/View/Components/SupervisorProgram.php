<?php

namespace App\View\Components;

use App\Models\Daemon;
use Closure;
use Illuminate\Contracts\View\View;

class SupervisorProgram extends Component implements BashScript
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(public Daemon $daemon)
    {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.server.supervisor-program');
    }
}
