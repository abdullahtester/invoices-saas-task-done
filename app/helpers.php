<?php

use App\Models\AdminCurrency;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SuperAdminSetting;
use App\Models\Tax;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Coduo\PHPHumanizer\NumberHumanizer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Database\TenantScope;
use Stripe\Stripe;

/**
 *
 * @return Authenticatable|null
 */
function getLogInUser()
{
    return Auth::user();
}

/**
 * @return User|Builder|Model|object|null
 */
function getSuperAdmin()
{
    static $superAdmin;
    if ( $superAdmin === null ) {
        $superAdmin = User::role(Role::ROLE_SUPER_ADMIN)->withoutGlobalScope(new TenantScope())->first();
    }

    return $superAdmin;
}

/**
 *
 *
 * @return mixed
 */
function getHomePageLanguage()
{
    static $superAdminSettings;
    if ( $superAdminSettings === null ) {
        $superAdminSettings = User::role(Role::ROLE_SUPER_ADMIN)->withoutGlobalScope(new TenantScope())->first();
    }
    return $superAdminSettings['language'];
}

/**
 *
 * @return array
 */
function getSuperAdminSettingValue()
{
    /** @var SuperAdminSetting $superAdminSettings */
    static $superAdminSettings;
    if ($superAdminSettings === null) {
        $superAdminSettings = SuperAdminSetting::all()->keyBy('key');
    }

    return $superAdminSettings;
}

/**
 * @param $fileName
 * @param $attachment
 *
 * @return string
 */
function getFileName($fileName, $attachment)
{
    $fileNameExtension = $attachment->getClientOriginalExtension();

    $newName = $fileName.'-'.time();

    return $newName.'.'.$fileNameExtension;
}

function getPaymentMode()
{
    if (isAuth() && Auth::user()->hasRole(\App\Models\Role::ROLE_CLIENT)) {

        $keyArray = [
            'stripe_enabled',
            'paypal_enabled',
            'razorpay_enabled',
        ];
        $setting = Setting::where('tenant_id', getClientAdminTenantId())->whereIn('key',
            $keyArray)->get(['key', 'value']);

        foreach ($setting as $key => $value) {

            if ($value->key == "stripe_enabled" && $value->value == "1") {
                $stripeKey = Payment::STRIPE;
                $paymentModeArr[$stripeKey] = 'Stripe';
            }

            if ($value->key == "paypal_enabled" && $value->value == "1") {
                $paypalKey = Payment::PAYPAL;
                $paymentModeArr[$paypalKey] = 'Paypal';
            }

            if ($value->key == "razorpay_enabled" && $value->value == "1") {
                $RazorpayKey = Payment::RAZORPAY;
                $paymentModeArr[$RazorpayKey] = 'Razorpay';
            }

            $manualKey = Payment::MANUAL;
            $paymentModeArr[$manualKey] = 'Manual';

            $cashKey = Payment::CASH;
            $paymentModeArr[$cashKey] = 'Cash';

        }
        array_push($paymentModeArr);

    } elseif (isAuth() && Auth::user()->hasRole(\App\Models\Role::ROLE_ADMIN)) {
        $keyArray = [
            'stripe_enabled',
            'paypal_enabled',
            'razorpay_enabled',
        ];
        $superAdminSetting = SuperAdminSetting::whereIn('key', $keyArray)->get(['key', 'value']);

        foreach ($superAdminSetting as $key => $value) {
            $manualKey = 0;
            $paymentModeArr[$manualKey] = 'Select payment method';
            if ($value->key == "stripe_enabled") {
                if ($value->key == "stripe_enabled" && $value->value == "1") {
                    $stripeKey = Subscription::TYPE_STRIPE;
                    $paymentModeArr[$stripeKey] = 'Stripe';
                } else {
                    if (config('services.stripe.secret_key') != null && config('services.stripe.key') != null) {
                        $stripeKey = Subscription::TYPE_STRIPE;
                        $paymentModeArr[$stripeKey] = 'Stripe';
                    }
                }
            }

            if ($value->key == "paypal_enabled") {
                if ($value->key == "paypal_enabled" && $value->value == "1") {
                    $paypalKey = Subscription::TYPE_PAYPAL;
                    $paymentModeArr[$paypalKey] = 'PayPal';
                } else {
                    if (config('payments.paypal.client_id') != null && config('payments.paypal.client_secret') != null) {
                        $paypalKey = Subscription::TYPE_PAYPAL;
                        $paymentModeArr[$paypalKey] = 'PayPal';
                    }
                }
            }

            if ($value->key == "razorpay_enabled") {
                if ($value->key == "razorpay_enabled" && $value->value == "1") {
                    $RazorpayKey = Subscription::TYPE_RAZORPAY;
                    $paymentModeArr[$RazorpayKey] = 'Razorpay';
                } else {
                    if (config('payments.razorpay.key') != null && config('payments.razorpay.secret') != null) {
                        $RazorpayKey = Subscription::TYPE_RAZORPAY;
                        $paymentModeArr[$RazorpayKey] = 'Razorpay';
                    }
                }
            }
            $manualKey = Subscription::TYPE_MANUAL;
            $paymentModeArr[$manualKey] = 'Manual';

        }
        array_push($paymentModeArr);

    }

    return $paymentModeArr;
}

/**
 * @return mixed
 */
function getAppName($tenantId = null)
{
    /** @var Setting $appName */
    static $appName;
    if (empty($appName)) {
        if (isAuth() && Auth::user()->hasRole(\App\Models\Role::ROLE_CLIENT)) {
            $appName = Setting::where('tenant_id', getClientAdminTenantId())->where('key', '=', 'app_name')->first();
        } else {
            if (isAuth() && Auth::user()->hasRole(\App\Models\Role::ROLE_ADMIN)) {
                $appName = Setting::where('tenant_id',Auth::user()->tenant_id)->where('key', '=', 'app_name')->first();
            } else {
                $appName = Setting::where('key', '=', 'app_name')->first();
                if (!empty($tenantId)){
                    $appName = Setting::where('tenant_id', $tenantId)->where('key', '=', 'app_name')->first();
                }
            }
        }
    }

    return $appName->value;
}

/**
 * @return array
 */
function getRazorPaySupportedCurrencies(): array
{
    return [
        'USD', 'EUR', 'GBP', 'SGD', 'AED', 'AUD', 'CAD', 'CNY', 'SEK', 'NZD', 'MXN', 'HKD', 'NOK', 'RUB', 'ALL', 'AMD',
        'ARS', 'AWG', 'BBD', 'BDT', 'BMD', 'BND', 'BOB', 'BSD', 'BWP', 'BZD', 'CHF', 'COP', 'CRC', 'CUP', 'CZK', 'DKK',
        'DOP', 'DZD', 'EGP', 'ETB', 'FJD', 'GIP', 'GMD', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS',
        'INR', 'JMD', 'KES', 'KGS', 'KHR', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'MAD', 'MDL', 'MKD', 'MMK',
        'MNT', 'MOP', 'MUR', 'MVR', 'MWK', 'MYR', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'PEN', 'PGK', 'PHP', 'PKR', 'QAR',
        'SAR', 'SCR', 'SLL', 'SOS', 'SSP', 'SVC', 'SZL', 'THB', 'TTD', 'TZS', 'UYU', 'UZS', 'YER', 'ZAR', 'GHS',
    ];
}

/**
 * @param  null  $tenantId
 *
 * @return mixed|string|void
 */
function getLogoUrl($tenantId = null)
{

    static $appLogo;

    $img = 'assets/images/infyom.png';

    if (empty($appLogo)) {
        if (isAuth() && Auth::user()->hasRole(\App\Models\Role::ROLE_CLIENT)) {

            /** @var Setting $setting */
            static $setting;
            if ($setting === null) {
                $setting = Setting::where('tenant_id', getClientAdminTenantId())->where('key', '=','app_logo')->first();
            }
            if ($setting->value == $img) {
                return asset('assets/images/infyom.png');
            } else {
                return $setting->value;
            }

        } else {
            if (isAuth() && Auth::user()->hasRole(\App\Models\Role::ROLE_ADMIN)) {

                /** @var Setting $setting */
                static $setting;
                if ($setting === null) {
                    $setting = Setting::where('tenant_id',Auth::user()->tenant_id)->where('key', '=', 'app_logo')->first();
                }
                if ($setting->value == $img) {
                    return asset('assets/images/infyom.png');
                } else {
                    return $setting->value;
                }

            } else {
                $appLogo = Setting::where('key', '=', 'app_logo')->first();
                if (!empty($tenantId)){
                    $appLogo = Setting::where('key', '=', 'app_logo')->where('tenant_id', $tenantId)->first();
                }

                return asset($appLogo->logo_url);
            }
        }
    }

}


/**
 *
 */
function headerLanguageName()
{
    if (Session::has('languageChangeName')) {
        return Session::get('languageChangeName');
    }

    return 'en';
}

/**
 *
 * @return string[]
 */
function getUserLanguages()
{
    $language = User::LANGUAGES;
    asort($language);

    return $language;
}

/**
 * @return mixed|null
 */
function getHeaderLanguageName()
{
    return getUserLanguages()[headerLanguageName()];
}

/**
 * @return bool
 */
function isAuth(): bool
{
    return Auth::check();
}

/**
 * @param $date
 *
 * @return string
 */
function getParseDate($date)
{
    return Carbon::parse($date);
}

/**
 *
 * @return int
 */
function getLogInUserId()
{
    static $authUser;
    if (empty($authUser)) {
        $authUser = Auth::user();
    }

    return $authUser->id;
}

/**
 * @return User
 */
function getLoggedInUser()
{
    return Auth::user();
}

/**
 * @return string
 */
function getSuperAdminDashboardURL()
{
    return RouteServiceProvider::SUPER_ADMIN;
}

/**
 * @return array
 */
function getCurrentPlanDetails()
{
    $currentSubscription = currentActiveSubscription();
    $isExpired = $currentSubscription->isExpired();
    $currentPlan = $currentSubscription->subscriptionPlan;


    if ($currentPlan->price != $currentSubscription->plan_amount) {
        $currentPlan->price = $currentSubscription->plan_amount;
    }

    $startsAt = Carbon::now();
    $totalDays = Carbon::parse($currentSubscription->start_date)->diffInDays($currentSubscription->end_date);
    $usedDays = Carbon::parse($currentSubscription->start_date)->diffInDays($startsAt);
    $remainingDays = $totalDays - $usedDays;

    $frequency = $currentSubscription->plan_frequency == SubscriptionPlan::MONTH ? 'Monthly' : 'Yearly';

    $days = $currentSubscription->plan_frequency == SubscriptionPlan::MONTH ? 30 : 365;

    $perDayPrice = round($currentPlan->price / $days, 2);

    if (checkIfPlanIsInTrial($currentSubscription)) {
        $remainingBalance = 0.00;
        $usedBalance = 0.00;
    } else {
        $remainingBalance = $currentPlan->price - ($perDayPrice * $usedDays);
        $usedBalance = $currentPlan->price - $remainingBalance;
    }

    return [
        'name'             => $currentPlan->name.' / '.$frequency,
        'trialDays'        => $currentPlan->trial_days,
        'startAt'          => Carbon::parse($currentSubscription->start_date)->translatedFormat('jS M, Y'),
        'endsAt'           => Carbon::parse($currentSubscription->end_date)->translatedFormat('jS M, Y'),
        'usedDays'         => $usedDays,
        'remainingDays'    => $remainingDays,
        'totalDays'        => $totalDays,
        'usedBalance'      => $usedBalance,
        'remainingBalance' => $remainingBalance,
        'isExpired'        => $isExpired,
        'currentPlan'      => $currentPlan,
    ];
}

/**
 * @param $planIDChosenByUser
 *
 * @return array
 */
function getProratedPlanData($planIDChosenByUser): array
{
    /** @var SubscriptionPlan $subscriptionPlan */
    $subscriptionPlan = SubscriptionPlan::findOrFail($planIDChosenByUser);
    $newPlanDays = $subscriptionPlan->frequency === SubscriptionPlan::MONTH ? 30 : 365;

    /** @var Subscription $currentSubscription */
    $currentSubscription = currentActiveSubscription();
    $frequency = $subscriptionPlan->frequency === SubscriptionPlan::MONTH ? 'Monthly' : 'Yearly';

    $startsAt = Carbon::now();

    $carbonParseStartAt = Carbon::parse($currentSubscription->start_date);
    $usedDays = $carbonParseStartAt->copy()->diffInDays($startsAt);
    $totalExtraDays = 0;
    $totalDays = $newPlanDays;

    $endsAt = Carbon::now()->addDays($newPlanDays);

    $startsAt = $startsAt->copy()->format('jS M, Y');
    if ($usedDays <= 0) {
        $startsAt = $carbonParseStartAt->copy()->format('jS M, Y');
    }

    if (!$currentSubscription->isExpired() && !checkIfPlanIsInTrial($currentSubscription)) {
        $amountToPay = 0;

        $currentPlan = $currentSubscription->subscriptionPlan; // TODO: take fields from subscription

        // checking if the current active subscription plan has the same price and frequency in order to process the calculation for the proration
        $planPrice = $currentPlan->price;
        $planFrequency = $currentPlan->frequency;
        if ($planPrice != $currentSubscription->plan_amount || $planFrequency != $currentSubscription->plan_frequency) {
            $planPrice = $currentSubscription->plan_amount;
            $planFrequency = $currentSubscription->plan_frequency;
        }

        $frequencyDays = $planFrequency === SubscriptionPlan::MONTH ? 30 : 365;
        $perDayPrice = round($planPrice / $frequencyDays, 2);

        $remainingBalance = round($planPrice - ($perDayPrice * $usedDays), 2);

        if ($remainingBalance < $subscriptionPlan->price) { // adjust the amount in plan
            $amountToPay = round($subscriptionPlan->price - $remainingBalance, 2);
        } else {
            $endsAt = Carbon::now();
            $perDayPriceOfNewPlan = round($subscriptionPlan->price / $newPlanDays, 2);
            $totalExtraDays = round($remainingBalance / $perDayPriceOfNewPlan);

            $endsAt = $endsAt->copy()->addDays($totalExtraDays);
            $totalDays = $totalExtraDays;
        }

        return [
            'startDate'        => $startsAt,
            'name'             => $subscriptionPlan->name.' / '.$frequency,
            'trialDays'        => $subscriptionPlan->trial_days,
            'remainingBalance' => $remainingBalance,
            'endDate'          => $endsAt->format('jS M, Y'),
            'amountToPay'      => $amountToPay,
            'usedDays'         => $usedDays,
            'totalExtraDays'   => $totalExtraDays,
            'totalDays'        => $totalDays,
        ];
    }


    return [
        'name'             => $subscriptionPlan->name.' / '.$frequency,
        'trialDays'        => $subscriptionPlan->trial_days,
        'startDate'        => $startsAt,
        'endDate'          => $endsAt->format('jS M, Y'),
        'remainingBalance' => 0,
        'amountToPay'      => $subscriptionPlan->price,
        'usedDays'         => $usedDays,
        'totalExtraDays'   => $totalExtraDays,
        'totalDays'        => $totalDays,
    ];
}

/**
 * @return array
 */
function isSubscriptionExpired(): array
{
    /** @var Subscription $subscription */
    static $subscription;
    if ($subscription === null) {
        $subscription = currentActiveSubscription();
    }

    if ($subscription && $subscription->isExpired()) {
        return [
            'status'  => true,
            'message' => 'Your current plan is expired, please choose new plan.',
        ];
    }

    if ($subscription == null) {
        return [
            'status'  => true,
            'message' => 'Please choose a plan to continue the service.',
        ];
    }

    $subscriptionEndDate = Carbon::parse($subscription->end_date);
    $currentDate = Carbon::parse(Carbon::now())->format('Y-m-d');

    $expirationMessage = '';
    $diffInDays = $subscriptionEndDate->diffInDays($currentDate);
    $superAdminSettingValue = getSuperAdminSettingValue();
    if ($diffInDays <= $superAdminSettingValue['plan_expire_notification']['value'] && $diffInDays != 0) {
        $expirationMessage = "Your '{$subscription->subscriptionPlan->name}' is about to expired in {$diffInDays} days.";
    }

    return [
        'status'  => $subscriptionEndDate->diffInDays($currentDate) <= $superAdminSettingValue['plan_expire_notification']['value'],
        'message' => $expirationMessage,
    ];
}


/**
 * @param Subscription $currentSubscription
 *
 * @return bool
 */
function checkIfPlanIsInTrial($currentSubscription)
{
    $now = Carbon::now();
    if (!empty($currentSubscription->trial_ends_at) && $currentSubscription->trial_ends_at > $now) {
        return true;
    }

    return false;

}

/**
 * @param $key
 * @return string
 */
function getSubscriptionPlanCurrencyCode($key): string
{
    $currencyPath = file_get_contents(storage_path().'/currencies/defaultCurrency.json');
    $currenciesData = json_decode($currencyPath, true)['currencies'];
    $currency = collect($currenciesData)->firstWhere('code',
        strtoupper($key));

    return $currency['code'];
}

/**
 * @param $key
 *
 * @return string
 */
function getAdminSubscriptionPlanCurrencyCode($key)
{
    $currencyData = AdminCurrency::where('id',$key)->first();
    if($currencyData != null){
        $currencyCode = $currencyData->code;
        return $currencyCode;
    }
}

/**
 * @return array
 */
function zeroDecimalCurrencies()
{
    return [
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
    ];
}

/**
 *
 * @return string
 */
function getAdminDashboardURL()
{
    return RouteServiceProvider::ADMIN;
}

/**
 * @return string
 */
function getClientDashboardURL()
{
    return RouteServiceProvider::CLIENT_HOME;
}

/**
 * @return mixed
 */
function getClientRoleId()
{
    return Role::whereName('client')->first()->id;
}

/**
 * @return mixed
 */
function getAdminRoleId()
{
    return Role::whereName('admin')->first()->id;
}

/**
 * @param $number
 *
 * @return string|string[]
 */
function removeCommaFromNumbers($number)
{
    return (gettype($number) == 'string' && !empty($number)) ? str_replace(',', '', $number) : $number;
}

/**
 * @param $countryId
 *
 * @return array
 */
function getStates($countryId)
{
    return \App\Models\State::where('country_id', $countryId)->toBase()->pluck('name', 'id')->toArray();
}

/**
 * @param $stateId
 *
 * @return array
 */
function getCities($stateId): array
{
    return \App\Models\City::where('state_id', $stateId)->pluck('name', 'id')->toArray();
}

/**
 * @return mixed
 */
function getCurrentTimeZone()
{
    /** @var Setting $currentTimezone */
    static $currentTimezone;

    try {
        if (empty($currentTimezone)) {
            $currentTimezone = Setting::where('tenant_id',Auth::user()->tenant_id)->where('key', 'time_zone')->first();
        }
        if ($currentTimezone != null) {
            return $currentTimezone->value;
        } else {
            return null;
        }
    } catch (Exception $exception) {
        return 'Asia/Kolkata';
    }
}

/**
 * @return array
 */
function getCurrencies()
{
    return Currency::all();
}

/**
 * @return array
 */
function getAdminCurrencies()
{
    return AdminCurrency::all();
}

/**
 * @return array
 */
function getCurrencyFullName(): array
{
    $currencyPath = file_get_contents(storage_path().'/currencies/defaultCurrency.json');
    $currenciesData = json_decode($currencyPath, true);
    $currencies = [];

    foreach ($currenciesData['currencies'] as $currency) {
        $convertCode = strtolower($currency['code']);
        $currencies[$convertCode] = $currency['icon'].' - '.$currency['code'].' '.$currency['name'];
    }

    return $currencies;
}

/**
 * @return array
 */
function getAdminCurrencyFullName(): array
{
    $currenciesData = AdminCurrency::get();
    $currencies = [];

    foreach ($currenciesData as $currency) {
        $convertCode = strtolower($currency['code']);
        $currencies[$convertCode] = $currency['icon'].' - '.$currency['code'].' '.$currency['name'];
    }

    return $currencies;
}



/**
 * @return mixed
 */
function getCurrencySymbol($tenantId = null)
{
    /** @var Setting $currencySymbol */
    static $currencySymbol;
    if ($currencySymbol === null) {
        if (Auth::check()) {
            $currencySymbol = Currency::where('id', getSettingValue('current_currency'))->pluck('icon')->first();
        } else {
            $adminCurrencySymbol = Setting::where('tenant_id', $tenantId)->where('key', '=',
                'current_currency')->first()->value;
            $currencySymbol = Currency::where('id', $adminCurrencySymbol)->pluck('icon')->first();
        }
    }

    return $currencySymbol;
}

/**
 * @param $currencyId
 * @param null $tenantId
 * @return HigherOrderBuilderProxy|mixed|string
 */
function getInvoiceCurrencySymbol($currencyId,$tenantId = null)
{
    $invoiceCurrencySymbol = Currency::whereId($currencyId)->first();

    return $invoiceCurrencySymbol->icon;
}


function getDefaultTax()
{
    return Tax::where('is_default', '=', '1')->first()->id ?? null;
}

/**
 *
 *
 * @return bool
 */
function setStripeApiKey()
{
    $stripeSecret = Setting::where('tenant_id', getClientAdminTenantId())->where('key', 'stripe_secret')->first();

    Stripe::setApiKey($stripeSecret->value);

    return true;
}

/**
 *
 *
 * @return mixed
 */
function getStripeKey()
{
    $stripeKey = Setting::where('tenant_id', getClientAdminTenantId())->where('key', 'stripe_key')->first();

    return $stripeKey->value;
}

/**
 *
 *
 * @return mixed
 */
function getPaypalClientId()
{
    $paypalClientId = Setting::where('tenant_id', getClientAdminTenantId())->where('key', 'paypal_client_id')->first();

    return $paypalClientId->value;
}

/**
 *
 *
 * @return mixed
 */
function getPaypalSecret()
{
    $paypalSecret = Setting::where('tenant_id', getClientAdminTenantId())->where('key', 'paypal_secret')->first();

    return $paypalSecret->value;
}

/**
 *
 *
 * @return mixed
 */
function getRazorpayKey()
{
    $razorpayKey = Setting::where('tenant_id', getClientAdminTenantId())->where('key', 'razorpay_key')->first();

    return $razorpayKey->value;
}

/**
 *
 *
 * @return mixed
 */
function getRazorpaySecret()
{
    $razorpaySecret = Setting::where('tenant_id', getClientAdminTenantId())->where('key', 'razorpay_secret')->first();

    return $razorpaySecret->value;
}

/**
 *
 *
 * @return bool
 */
function setSuperAdminStripeApiKey()
{
    $stripeSecret = SuperAdminSetting::where('key', 'stripe_secret')->first();

    if ($stripeSecret == null || $stripeSecret->value == null) {
        Stripe::setApiKey(config('services.stripe.secret_key'));
    } else {
        Stripe::setApiKey($stripeSecret->value);
    }

    return true;
}

/**
 *
 *
 * @return mixed
 */
function getSuperAdminStripeSecret()
{
    $stripeSecret = SuperAdminSetting::where('key', 'stripe_secret')->first();

    if ($stripeSecret == null || $stripeSecret->value == null) {
        return config('services.stripe.secret_key');
    } else {
        return $stripeSecret->value;
    }
}

/**
 * @return SuperAdminSetting|\Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application
 */
function getSuperAdminStripeKey()
{
    /**@var SuperAdminSetting $stripeKeyValue */
    static $stripeKeyValue;

    if (!isset($stripeKeyValue) && empty($stripeKeyValue)) {
        $stripeKey = SuperAdminSetting::where('key', 'stripe_key')->first();
        if ($stripeKey == null || $stripeKey->value == null) {
            $stripeKeyValue = config('services.stripe.key');
        } else {
            $stripeKeyValue = $stripeKey->value;
        }
    }
    return $stripeKeyValue;
}

/**
 *
 *
 * @return mixed
 */
function getSuperAdminPaypalClientId()
{
    $paypalClientId = SuperAdminSetting::where('key', 'paypal_client_id')->first();

    if ($paypalClientId == null || $paypalClientId->value == null) {
        return config('payments.paypal.client_id');
    } else {
        return $paypalClientId->value;
    }
}

/**
 *
 *
 * @return mixed
 */
function getSuperAdminPaypalSecret()
{
    $paypalSecret = SuperAdminSetting::where('key', 'paypal_secret')->first();

    if ($paypalSecret == null || $paypalSecret->value == null) {
        return config('payments.paypal.client_secret');
    } else {
        return $paypalSecret->value;
    }
}

/**
 *
 *
 * @return mixed
 */
function getSuperAdminRazorpayKey()
{
    $razorpayKey = SuperAdminSetting::where('key', 'razorpay_key')->first();

    if ($razorpayKey == null || $razorpayKey->value == null) {
        return config('payments.razorpay.key');
    } else {
        return $razorpayKey->value;
    }

}

/**
 *
 *
 * @return mixed
 */
function getSuperAdminRazorpaySecret()
{
    $razorpaySecret = SuperAdminSetting::where('key', 'razorpay_secret')->first();

    if ($razorpaySecret == null || $razorpaySecret->value == null) {
        return config('payments.razorpay.secret');
    } else {
        return $razorpaySecret->value;
    }
}

// current date format
/**
 *
 * @return mixed
 */
function currentDateFormat()
{
    /** @var Setting $dateFormat */
    static $dateFormat;
    if ($dateFormat === null) {
        if (isAuth() && Auth::user()->hasRole(\App\Models\Role::ROLE_CLIENT)) {
            $dateFormat = Setting::where('tenant_id', getClientAdminTenantId())->where('key', 'date_format')->first();
        } else {
            if (isAuth() && Auth::user()->hasRole(\App\Models\Role::ROLE_ADMIN)) {
                $dateFormat = Setting::where('tenant_id',Auth::user()->tenant_id)->where('key', 'date_format')->first();
            } else {
                $dateFormat = Setting::where('key', 'date_format')->first();
            }
        }
    }

    return $dateFormat->value;
}

/**
 *
 * @return mixed
 */
function getAuthLogintenantId()
{
    $user = User::where('id', Auth::id())->first();

    return $user;
}

/**
 *
 * @return string
 */
function momentJsCurrentDateFormat()
{
    /** @var Setting $key */
    static $key;
    if ($key === null) {
        $key = Setting::DateFormatArray[currentDateFormat()];
    }

    return $key;
}

/**
 * @param array $data
 */
function addNotification($data)
{
    $notificationRecord = [
        'type' => $data[0],
        'user_id' => $data[1],
        'title' => $data[2],
    ];

    $user = User::withoutGlobalScope(new TenantScope())->findOrFail($data[1]);

    if ($user) {
        Notification::create($notificationRecord);
    }
}

/**
 * @return array
 */
function getPayPalSupportedCurrencies()
{
    return [
        'AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK',
        'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'USD',
    ];
}

/**
 * @return Builder|Model|object|null
 */
function currentActiveSubscription()
{
    if (!Auth::user()) {
        return null;
    }
    /** @var Subscription $currentActivePlan */
    static $currentActivePlan;
    if ($currentActivePlan === null) {
        $currentActivePlan = Subscription::whereHas('subscriptionPlan')->with('subscriptionPlan')
            ->where('status', Subscription::ACTIVE)
            ->where('user_id', Auth::user()->id)
            ->first();
    }

    return $currentActivePlan;
}

/**
 * @return Collection
 */
function getNotification()
{
    /** @var Setting $notification */
    static $notification;
    if (empty($notification)) {
        $notification = Notification::whereUserId(Auth::id())->where('read_at',
            null)->orderByDesc('created_at')->toBase()->get();
    }

    return $notification;
}

/**
 * @param array $data
 *
 * @return array
 */
function getAllNotificationUser($data)
{
    return array_filter($data, function ($key) {
        return $key != getLogInUserId();
    }, ARRAY_FILTER_USE_KEY);
}

/**
 * @param $notificationType
 *
 * @return string|void
 */
function getNotificationIcon($notificationType)
{
    switch ($notificationType) {
        case 1:
        case 2:
            return 'fas fa-file-invoice';
        case 3:
            return 'fas fa-wallet';
        case 5:
            return 'fas fa-money-bill-wave-alt text-success';
    }
}

/**
 * @return User|Builder|Model|object|null
 */
function getAdminUser()
{
    /** @var User $user */
    static $user;
    if (empty($user)) {
        $user = User::with([
            'roles' => function ($q) {
                $q->where('name', 'Admin');
            },
        ])->first();
    }

    return $user;
}

/**
 *
 * @return Model|object|User|null
 */
function getAdminUserIds()
{
    static $userIds;
    if ($userIds === null) {
        $userIds = User::role('admin')->pluck('id')->toArray();
    }

    return $userIds;
}

/**
 * @param array $models
 * @param string $columnName
 * @param string $id
 *
 * @return bool
 */
function canDelete(array $models, string $columnName, string $id)
{

    foreach ($models as $model) {
        $result = $model::where($columnName, $id)->exists();

        if ($result) {
            return true;
        }
    }

    return false;
}

function numberFormat(float $num, int $decimals = 2)
{
    /** @var Setting $decimal_separator */
    /** @var Setting $thousands_separator */
    static $decimal_separator;
    static $thousands_separator;
    if (empty($decimal_separator) || empty($thousands_separator)) {
        $decimal_separator = getSettingValue('decimal_separator');
        $thousands_separator = getSettingValue('thousand_separator');
    }

    return number_format($num, $decimals, $decimal_separator, $thousands_separator);
}

if (!function_exists('formatTotalAmount')) {
    /**
     * @param $totalAmount
     * @param $precision
     *
     * @return string
     */
    function formatTotalAmount($totalAmount, $precision = 2): string
    {
        if ($totalAmount < 900) {
            // 0 - 900
            $numberFormat = number_format($totalAmount, $precision);
            $suffix = '';
        } elseif ($totalAmount < 900000) {
            // 0.9k-850k
            $numberFormat = number_format($totalAmount / 1000, $precision);
            $suffix = 'K';
        } elseif ($totalAmount < 900000000) {
            // 0.9m-850m
            $numberFormat = number_format($totalAmount / 1000000, $precision);
            $suffix = 'M';
        } elseif ($totalAmount < 900000000000) {
            // 0.9b-850b
            $numberFormat = number_format($totalAmount / 1000000000, $precision);
            $suffix = 'B';
        } else {
            // 0.9t+
            $numberFormat = number_format($totalAmount / 1000000000000, $precision);
            $suffix = 'T';
        }

        // Remove unecessary zeroes after decimal. "1.0" -> "1"; "1.00" -> "1"
        // Intentionally does not affect partials, eg "1.50" -> "1.50"
        if ($precision > 0) {
            $dotZero = '.'.str_repeat('0', $precision);
            $numberFormat = str_replace($dotZero, '', $numberFormat);
        }

        return $numberFormat.$suffix;
    }
}

if (!function_exists('getSettingValue')) {
    /**
     * @param $keyName
     * @param $tenantId
     *
     *
     * @return mixed
     */
    function getSettingValue($keyName , $tenantId = null)
    {
        $key = 'setting'.'-'.$keyName;
        static $settingValues;

        if (isset($settingValues[$key])) {
            return $settingValues[$key];
        }
        /** @var User $user */
        $user = Auth::user();
        /** @var Setting $setting */
        if ($tenantId) {
            $setting = Setting::where('tenant_id', $tenantId)->where('key', '=', $keyName)->first();
        } elseif (isAuth() && $user->hasRole(\App\Models\Role::ROLE_CLIENT)) {
            $setting = Setting::where('tenant_id', $user->tenant_id)->where('key', '=', $keyName)->first();
        } elseif (isAuth() && $user->hasRole(\App\Models\Role::ROLE_ADMIN)) {
            $setting = Setting::where('tenant_id', $user->tenant_id)->where('key', '=', $keyName)->first();
        } else {
            $setting = Setting::where('key', '=', $keyName)->first();
        }
        $settingValues[$key] = $setting->value;

        return $setting->value;
    }
}

/**
 *
 */
function getClientAdminTenantId()
{
    $user = Auth::user();
    if ($user->hasRole(\App\Models\Role::ROLE_CLIENT))
    {
        $clinetAdminTenantId = Auth::user()->tenant_id;

        return $clinetAdminTenantId;
    }
}

/**
 *
 * @return mixed
 */
function getCurrencyCode()
{
    $currencyId = Setting::where('key', 'current_currency')->value('value');
    $user = Auth::user();
    if ($user->hasRole(\App\Models\Role::ROLE_CLIENT)) {
        $currencyId = Setting::where('tenant_id', getClientAdminTenantId())->where('key', 'current_currency')->value('value');
    }

    $currencyCode = Currency::whereId($currencyId)->first();

    return $currencyCode->code;
}

/**
 * @param $currencyId
 * @return HigherOrderBuilderProxy|mixed
 */
function getInvoiceCurrencyCode($currencyId)
{
    $currencyCode = Currency::whereId($currencyId)->first();

    return $currencyCode->code;
}

/**
 * @param $key
 * @return string
 */
function getSubscriptionPlanCurrencyIcon($key): string
{
    $currencyPath = file_get_contents(storage_path().'/currencies/defaultCurrency.json');
    $currenciesData = json_decode($currencyPath, true)['currencies'];
    $currency = collect($currenciesData)->firstWhere('code',
        strtoupper($key));

    return $currency['icon'];
}

/**
 * @param $key
 *
 * @return string
 */
function getAdminSubscriptionPlanCurrencyIcon($key)
{
    static $adminCurrencyIcon;

    if (!isset($adminCurrencyIcon[$key]) && empty($adminCurrencyIcon[$key])) {
        $currencyData = AdminCurrency::where('id', $key)->first();
        if ($currencyData != null) {
            $adminCurrencyIcon[$key] = $currencyData->icon;
        }
    }
    return $adminCurrencyIcon[$key];
}

/**
 * @param $key
 *
 * @return string
 */
function getAdminPlanCurrencyCode($key)
{
    $currencyData = AdminCurrency::where('code',$key)->first();
    if($currencyData != null){
        $currencyIcon = $currencyData->code;
        return $currencyIcon;
    }
}

/**
 * @param array $input
 * @param string $key
 *
 * @return string|null
 */
function preparePhoneNumber($input, $key)
{
    return (!empty($input[$key])) ? '+'.$input['prefix_code'].$input[$key] : null;
}

/**
 * @return mixed
 */
function getCurrentVersion()
{
    $composerFile = file_get_contents('../composer.json');
    $composerData = json_decode($composerFile, true);
    $currentVersion = $composerData['version'];

    return $currentVersion;
}

/**
 * @param null $tenantId
 *
 *
 * @return mixed
 */
function currencyPosition($tenantId = null){
    static $currencyPosition;
    if (empty($currencyPosition)) {
        $currencyPosition = Setting::where([
            'tenant_id' => (Auth::check()) ? Auth::user()->tenant_id : $tenantId, 'key' => 'currency_after_amount'
        ])->value('value');
    }

    return $currencyPosition;
}

/**
 *
 *
 * @return mixed
 */
function superAdminCurrencyPosition(){
    static $currencyPosition;
    if (empty($currencyPosition)) {
        $currencyPosition = SuperAdminSetting::where('key', 'currency_after_amount')->first()->value;
    }
    return $currencyPosition;
}

/**
 * @param $amount
 * @param false $formatting
 *
 *
 * @param null $currency
 * @return string
 */
function superAdminCurrencyAmount($amount,$formatting = false,$currency = null){

    $formattedAmount = $formatting ? numberFormat($amount) : $amount;
    $currencySymbol = is_null($currency) ? getCurrencySymbol() : $currency;
    if(superAdminCurrencyPosition()) {
        return $formattedAmount.' '.$currencySymbol;
    }

    return $currencySymbol.' '.$formattedAmount;
}

/**
 * @param $amount
 * @param  false  $formatting
 * @param  null  $tenantId
 *
 * @return string
 */
function getCurrencyAmount($amount, $formatting = false, $tenantId = null): string
{
    if (empty($tenantId) && Auth::check()){
        /** @var User $user */
        $user = Auth::user();
        $tenantId = $user->tenant_id;
    }
    $currencyPosition = is_null($tenantId) ? currencyPosition() : currencyPosition($tenantId);
    $currencySymbol = is_null($tenantId) ? getCurrencySymbol() : getCurrencySymbol($tenantId);
    $formattedAmount = $formatting ? numberFormat($amount) : formatTotalAmount($amount);
    if($currencyPosition) {
        return $formattedAmount.' '.$currencySymbol;
    }

    return $currencySymbol.' '.$formattedAmount;
}

/**
 * @param $amount
 * @param $currencyId
 * @param false $formatting
 * @param null $tenantId
 * @return string
 */
function getInvoiceCurrencyAmount($amount, $currencyId, $formatting = false, $tenantId = null): string
{
    if (empty($tenantId) && Auth::check()){
        /** @var User $user */
        $user = Auth::user();
        $tenantId = $user->tenant_id;
    }
    
    $currencyPosition = is_null($tenantId) ? currencyPosition() : currencyPosition($tenantId);
    $currencySymbol = Currency::where('id', $currencyId)->pluck('icon')->first();
    $formattedAmount = $formatting ? numberFormat($amount) : formatTotalAmount($amount);
    if($currencyPosition) {
        return $formattedAmount.' '.$currencySymbol;
    }

    return $currencySymbol.' '.$formattedAmount;
}


if (!function_exists('currentTenantId')) {

    function currentTenantId()
    {
        static $currentTenantId;
        if (empty($currentTenantId) && Auth::check()){
            $currentTenantId = Auth::user()->tenant_id;
        }

        return $currentTenantId;
    }
}

function getManualPayment($tenantId)
{
    static $manualPayment;

    if (empty($tenantId)){
        $tenantId = Auth::user()->tenant_id;
    }

    if (empty($manualPayment)) {
        $manualPayment = Setting::where('key', '=', 'payment_auto_approved')->where('tenant_id', $tenantId)->firstOrFail();
    }

    return $manualPayment->value;
}

/**
 * @param $invoiceId
 *
 * @return float|int
 */
function getInvoicePaidAmount($invoiceId)
{
    $dueAmount = 0;
    $paid = 0;
    $invoice = Invoice::whereId($invoiceId)->with('payments')->firstOrFail();

    foreach ($invoice->payments as $payment) {
        if ($payment->payment_mode == \App\Models\Payment::MANUAL && $payment->is_approved !== \App\Models\Payment::APPROVED) {
            continue;
        }
        $paid += $payment->amount;
    }

    return $paid;
}

/**
 * @param $invoiceId
 * @return float|HigherOrderBuilderProxy|int|mixed|null
 */
function getInvoiceDueAmount($invoiceId)
{
    $dueAmount = 0;
    $paid = 0;
    $invoice = Invoice::whereId($invoiceId)->with('payments')->firstOrFail();

    foreach ($invoice->payments as $payment) {
        if ($payment->payment_mode == \App\Models\Payment::MANUAL && $payment->is_approved !== \App\Models\Payment::APPROVED) {
            continue;
        }
        $paid += $payment->amount;
    }
    $dueAmount = $invoice->final_amount - $paid;

    return $dueAmount;
}


/**
 *
 *
 * @return string
 */
function checkLanguageSession()
{
    if (Session::has('languageName')) {
        return Session::get('languageName');
    }

    return 'en';
}

/**
 *
 *
 * @return string[]
 */
function getLanguages()
{
    return User::LANGUAGES;
}

///**
// *
// *
// * @return string
// */
//function getCurrentLanguageName()
//{
//    return getLanguages()[checkLanguageSession()];
//}

/**
 * @return mixed
 */
function getCurrentLanguageName(){
    return Auth::user()->language;
}
