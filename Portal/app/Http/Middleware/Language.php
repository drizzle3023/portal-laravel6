<?php


namespace App\Http\Middleware;


use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class Language
{
    public function handle($request, Closure $next, $guard = null) {

        if (session()->get('locale')) {
            App::setLocale(session()->get('locale'));
        }
        else { // This is optional as Laravel will automatically set the fallback language if there is none specified

            App::setLocale(Config::get('app.fallback_locale'));
        }
        return $next($request);
    }
}
