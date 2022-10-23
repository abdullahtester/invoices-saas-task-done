<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laracasts\Flash\Flash;

class CheckClientLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $clientCount = Client::count();
        $clientLimit = currentActiveSubscription()->subscriptionPlan->client_limit;
        if(!($clientLimit > $clientCount)){
            Flash::error('Client create limit exceeded for your account, Update your subscription plan.');
            return redirect()->route('clients.index');
        }

        return $next($request);
    }
}
