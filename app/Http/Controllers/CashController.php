<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Transaction;
use App\Repositories\SubscriptionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laracasts\Flash\Flash;

class CashController extends AppBaseController
{
    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @param SubscriptionRepository $subscriptionRepository
     */
    public function __construct(SubscriptionRepository $subscriptionRepository)
    {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pay(Request $request)
    {
        $input = $request->all();

        $data = $this->subscriptionRepository->manageCashSubscription($input['plan_id']);

        if (!isset($data['plan'])) { 
          
            if (isset($data['status']) && $data['status'] == true) {
                return $this->sendSuccess($data['subscriptionPlan']->name.' '.__('messages.subscription_pricing_plans.has_been_subscribed'));
            } else {
                if (isset($data['status']) && $data['status'] == false) {
                    return $this->sendError('Cannot switch to zero plan if trial is available / having a paid plan which is currently active');
                }
            }
        }

        // If call returns body in response, you can get the deserialized version from the result attribute of the response
        $subscriptionId = $data['subscription']->id;
        $subscriptionAmount = $data['amountToPay'];
        
        $transaction = Transaction::create([
            'transaction_id' => '',
            'payment_mode'   => Transaction::TYPE_CASH,
            'amount'         => $subscriptionAmount,
            'user_id'        => getLogInUserId(),
            'tenant_id'      => Auth::user()->tenant_id,
            'status'         => Subscription::INACTIVE,
            'meta'           => '',
        ]);

        // updating the transaction id on the subscription table
        $subscription = Subscription::with('subscriptionPlan')->findOrFail($subscriptionId);
        $subscription->update(['transaction_id' => $transaction->id]);

        Flash::success(trans('Your payment is done and your subscription will be activated once the admin approve your transaction.', [], getLoggedInUser()->language));
        
        return response()->json(['url' => route('subscription.pricing.plans.index')]);
     
    }
}
