<?php

namespace App\Http\Middleware;

use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Closure;

class CheckBlocked extends BaseController
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::user()->isBlocked()) {
            return $this->sendError('blocked', [], 403);
        }
        return $next($request);
    }
}
