<?php

namespace App\Http\Middleware;

use App\Models\Invoice;
use App\Utils\ResponseUtil;
use Closure;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;
use Response;

class CheckInvoiceLimit
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
        $invoiceCount = Invoice::count();
        $invoiceLimit = currentActiveSubscription()->subscriptionPlan->invoice_limit;
        if(!($invoiceLimit > $invoiceCount)) {
            return Response::json(ResponseUtil::makeError('Invoice create limit exceeded for your account, Update your subscription plan.'), 422);
        }

        return $next($request);
    }
}
