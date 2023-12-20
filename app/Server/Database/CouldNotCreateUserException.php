<?php

namespace App\Server\Database;

use App\Models\Server;
use Exception;

class CouldNotCreateUserException extends Exception
{
    public function __construct(
        public Server $server,
        public $message
    ) {
        parent::__construct($message);
    }

    public function render()
    {
        // TODO: gerer cette exeception et les autres du même style
        //        Toast::warning(__("Could not create the user on server ':server'", [
        //            'server' => $this->server->name,
        //        ]));

        return back(fallback: route('servers.databases.index', $this->server));
    }
}
