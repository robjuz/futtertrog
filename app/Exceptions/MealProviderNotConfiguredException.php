<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class MealProviderNotConfiguredException extends Exception
{
    public function __construct(string $provider)
    {
        parent::__construct("{$provider} credentials not configured", 400);
    }

    public function report(): false
    {
        return false;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): \Illuminate\Http\RedirectResponse
    {
        return back()->with('error', $this->getMessage());
    }
}
