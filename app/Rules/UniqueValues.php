<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class UniqueValues implements Rule
{
    protected $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function passes($attribute, $value)
    {
        $count = array_count_values($this->values)[$value] ?? 0;

        return $count === 1;
    }

    public function message()
    {
        return 'The :attribute must be unique within the array.';
    }
}
