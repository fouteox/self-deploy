<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use phpseclib3\Crypt\PublicKeyLoader;
use Throwable;

class PublicKey implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            PublicKeyLoader::load($value)->__toString();
        } catch (Throwable) {
            $fail(__('The :attribute is not valid.'));
        }
    }
}
