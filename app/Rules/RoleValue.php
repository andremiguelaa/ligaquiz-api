<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class RoleValue implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($value === true) {
            return true;
        }
        if ($value === date_format(date_create($value), "Y-m-d")) {
            return true;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Invalid role value.';
    }
}
