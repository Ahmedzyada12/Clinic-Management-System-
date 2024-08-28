<?php

namespace App\Rules;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Rule;

class UniqueEmailAcrossTables implements Rule
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
        $exists = DB::table('patients')->where('email', $value)->exists() ||
            DB::table('assistants')->where('email', $value)->exists() ||
            DB::table('users')->where('email', $value)->exists();

        return !$exists;
    }

    public function message()
    {
        return 'The :attribute must be unique across patients, assistants, and doctors.';
    }
}
