<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cookie;

class ToggleDogController extends Controller
{
    public function __invoke()
    {
        return back()->withCookie(cookie()->forever('show_dog', !Cookie::get('show_dog', true)));
    }
}
