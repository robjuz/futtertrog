<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest as Middleware;

class CastFormValuesToFloat extends Middleware
{
    /**
     * When the request is not a json request
     * transform all numeric value to float
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function transform($key, $value)
    {
        if (request()->isJson()) {
            return $value;
        };

        return is_numeric($value) ? floatval($value) : $value;
    }
}
