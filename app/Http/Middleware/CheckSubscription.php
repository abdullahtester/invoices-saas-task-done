<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laracasts\Flash\Flash;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check() || (Auth::check() && Auth::user()->hasRole('super_admin'))) {
            return $next($request);
        }

        if (! Auth::check() || (Auth::check() && ! Auth::user()->hasRole('admin'))) {
            return $next($request);
        }

        $subscription = currentActiveSubscription();

        if (!$subscription) {
            Flash::error('Please choose a plan to continue the service.');
            return redirect()->route('subscription.pricing.plans.index');
        }

        if ($subscription->isExpired()) {
            Flash::error('Your current plan is expired, please choose new plan.');
            return redirect()->route('subscription.pricing.plans.index');
        }

        return $next($request);
    }
}
