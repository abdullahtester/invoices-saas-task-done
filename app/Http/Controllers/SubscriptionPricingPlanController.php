<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Repositories\SubscriptionPlanRepository;
use Arr;
use Illuminate\Support\Facades\Auth;
use Laracasts\Flash\Flash;

class SubscriptionPricingPlanController extends Controller
{
    /**
     * @var
     */
    private $subscriptionPlanRepository;

    /**
     * @param  SubscriptionPlanRepository  $subscriptionPlanRepo
     */
    public function __construct(SubscriptionPlanRepository $subscriptionPlanRepo)
    {
        $this->subscriptionPlanRepository = $subscriptionPlanRepo;
    }

    /**
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $data = $this->subscriptionPlanRepository->getSubscriptionPlansData();

        return view('subscription_pricing_plans.index')->with($data);
    }

    public function choosePaymentType($planId, $context = null, $fromScreen = null)
    {
        // code for checking the current plan is active or not, if active then it should not allow to choose that plan
        /** @var Subscription $subscription */
        $subscription = Subscription::whereStatus(Subscription::ACTIVE)
            ->whereUserId(getLogInUserId())
            ->with('subscriptionPlan')
            ->first();

        if ($subscription->subscriptionPlan->id == $planId) {
            $toastData = [
                'toastType'    => 'warning',
                'toastMessage' => $subscription->subscriptionPlan->name.' '.__('has already been subscribed'),
            ];

            if ($context != null && $context == 'landing' && $fromScreen == 'landing.pricing') {
                return redirect(route('landing.pricing'))->with('toast-data', $toastData);
            }
        }

        $subscriptionsPricingPlan = SubscriptionPlan::findOrFail($planId);
        $paymentTypes = Arr::except(Subscription::PAYMENT_TYPES, [Subscription::TYPE_FREE]);
        $transaction = Transaction::whereUserId(Auth::id())
            ->wherePaymentMode(Transaction::TYPE_CASH)
            ->whereStatus(0)
            ->whereIsManualPayment(0)->latest()->exists();
        $paymentTypes = getPaymentMode();

        if ($context != null && $context == 'landing') {
            if ($transaction) {
                Flash::success('Your Manual Transaction Requests pending.');

                return view('landing.landing_pricing_plan.payment_for_subscription_plan',
                    compact('subscriptionsPricingPlan', 'paymentTypes', 'fromScreen', 'transaction'));
            }

            return view('landing.landing_pricing_plan.payment_for_subscription_plan',
                compact('subscriptionsPricingPlan', 'paymentTypes', 'fromScreen', 'transaction'));
        }
        $paymentTypes = Arr::except($paymentTypes, 0);

        if ($transaction) {
            Flash::success('Your Manual Transaction Requests pending.');

            return view('subscription_pricing_plans.payment_for_plan',
                compact('subscriptionsPricingPlan', 'paymentTypes', 'transaction'));
        }

        return view('subscription_pricing_plans.payment_for_plan',
            compact('subscriptionsPricingPlan', 'paymentTypes', 'transaction'));
    }

}
