<?php

namespace App\Rules;

use App\Models\CouldNotConnectToServerException;
use App\Models\NoConnectionSelectedException;
use App\Models\Server;
use App\Models\TaskFailedException;
use App\Tasks\ValidateCaddyfile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

readonly class CaddyfileOnServer implements ValidationRule
{
    public function __construct(
        private Server $server,
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     *
     * @throws CouldNotConnectToServerException
     * @throws NoConnectionSelectedException
     * @throws TaskFailedException
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $output = $this->server->runTask(new ValidateCaddyfile($value))
            ->asUser()
            ->throw()
            ->dispatch();

        if (! $output->isSuccessful() || ! Str::contains($output->getBuffer(), 'Valid configuration')) {
            $lineWithError = Collection::make($output->getLines())
                ->first(fn (string $line) => Str::contains($line, '.caddyfile:'));

            $fail(__('The Caddyfile is invalid: :error', [
                'error' => $lineWithError,
            ]));
        }
    }
}
