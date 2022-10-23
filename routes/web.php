<?php

use App\Http\Controllers\AdminCurrencyController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\AdminPaypalController;
use App\Http\Controllers\AdminRazorpayController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Client as Client;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceTemplateController;
use App\Http\Controllers\Landing as Landing;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\PaypalController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionPlanController;
use App\Http\Controllers\SubscriptionPricingPlanController;
use App\Http\Controllers\SuperAdminEnquiryController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

require __DIR__.'/auth.php';

Route::middleware(['xss'])->group(function () {
    Route::get('/', function () {
        return redirect(route('landing.home'));
    });

    Route::post('update-language', [UserController::class, 'updateLanguage'])->name('change-language');
    Route::get('language/', [UserController::class, 'getAllLanguage'])->name('get.all.language');
    //Notification routes
    Route::get('/notification/{notification}/read',
        [NotificationController::class, 'readNotification'])->name('read.notification')->middleware('multi_tenant');
    Route::post('/read-all-notification',
        [
            NotificationController::class, 'readAllNotification',
        ])->name('read.all.notification')->middleware('multi_tenant');
    //update darkMode Field
    Route::get('update-dark-mode', [UserController::class, 'updateDarkMode'])->name('update-dark-mode');
    Route::get('invoice/{invoiceId}', [InvoiceController::class, 'showPublicInvoice'])->name('invoice-show-url');
    Route::get('invoice-pdf/{invoice}',
        [InvoiceController::class, 'getPublicInvoicePdf'])->name('public-view-invoice.pdf');
    Route::get('quote/{quoteId}', [QuoteController::class, 'showPublicQuote'])->name('quote-show-url');
    Route::get('quote-pdf/{quote}',
        [QuoteController::class, 'getPublicQuotePdf'])->name('public-view-quote.pdf');
});

Route::group([
    'prefix'     => 'admin',
    'middleware' => ['auth', 'xss', 'role:admin', 'check_subscription', 'checkUserStatus', 'multi_tenant'],
], function () {

    // Admin dashboard route
    Route::get('/dashboard',
        [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('payment-overview', [DashboardController::class, 'paymentOverview'])->name('payment-overview');
    Route::get('invoices-overview', [DashboardController::class, 'invoiceOverview'])->name('invoices-overview');
    Route::get('yearly-income-chart',
        [DashboardController::class, 'getYearlyIncomeChartData'])->name('yearly-income-chart');

    // Role route
    Route::group(['middleware' => ['permission:manage_roles']], function () {
        Route::resource('roles', RoleController::class);
    });

    // Client route
    Route::resource('clients', ClientController::class)->except(['show']);
    Route::post('clients', [ClientController::class, 'store'])->name('clients.store')->middleware('checkClientLimit');

    //client total invoice view route
    Route::get('clients/{clientId}/{active?}', [ClientController::class, 'show'])->name('clients.show');
    Route::get('states-list', [ClientController::class, 'getStates'])->name('states-list');
    Route::get('cities-list', [ClientController::class, 'getCities'])->name('cities-list');

    //Category Route
    Route::resource('categories', CategoryController::class)->names([
        'index'   => 'category.index',
        'create'  => 'category.create',
        'show'    => 'category.show',
        'store'   => 'category.store',
        'edit'    => 'category.edit',
        'update'  => 'category.update',
        'destroy' => 'category.destroy',
    ]);

    //Product Route
    Route::resource('products', ProductController::class);
    
    //quote
    Route::resource('quotes', QuoteController::class);
    Route::get('quotes/{quote}/pdf', [QuoteController::class, 'convertToPdf'])->name('quotes.pdf');
    Route::get('quotes/{productId}/product', [QuoteController::class, 'getProduct'])->name('quotes.get-product');
    Route::get('convert-to-invoice', [QuoteController::class, 'convertToInvoice'])->name('quotes.convert-to-invoice');

    //Invoice
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices',
        [InvoiceController::class, 'store'])->name('invoices.store')->middleware('checkInvoiceLimit');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'convertToPdf'])->name('invoices.pdf');
    Route::get('invoices/{productId}/product', [InvoiceController::class, 'getProduct'])->name('invoices.get-product');
    Route::get('invoices/{currencyId}/currency', [InvoiceController::class, 'getInvoiceCurrency'])->name('invoices.get-currency');
    Route::post('change-invoice-status/{invoice}/{status}',
        [InvoiceController::class, 'updateInvoiceStatus'])->name('send-invoice');
    Route::Post('invoice-payment-reminder/{invoiceId}',
        [InvoiceController::class, 'invoicePaymentReminder'])->name('invoice.payment-reminder');
    //Tax
    Route::resource('taxes', TaxController::class);
    Route::post('taxes/{tax}/default-status', [TaxController::class, 'defaultStatus'])->name('taxes.default-status');

    //Payment
    Route::get('transactions', [PaymentController::class, 'index'])->name('transactions.index');
    Route::resource('payments', AdminPaymentController::class);
    Route::get('get-current-date-format',
        [AdminPaymentController::class, 'getCurrentDateFormat'])->name('get-current-date-format');

    //Setting Route
    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('invoice-settings', [SettingController::class, 'invoiceUpdate'])->name('invoice-settings.settings');
    Route::get('invoice-template/{key}',
        [SettingController::class, 'editInvoiceTemplate'])->name('invoice-template.edit');
    Route::get('invoice-settings',[SettingController::class,'invoiceSettings'])->name('settings.invoice-settings');
    //invoice template
    Route::get('template-setting',
        [InvoiceTemplateController::class, 'invoiceTemplateView'])->name('invoiceTemplate');
    Route::post('change-invoice-template',
        [
            InvoiceTemplateController::class, 'invoiceTemplateUpdate',
        ])->name('invoiceTemplate.update');

    // Currency
    Route::resource('currencies', CurrencyController::class);

    Route::post('user/{user}/change-status', [UserController::class, 'changeUserStatus'])->name('users.change-status');

    //getInvoiceDueAmount
    Route::get('payments.get-invoiceAmount/{id}',
        [AdminPaymentController::class, 'getInvoiceDueAmount'])->name('payments.get-invoiceAmount');

    //get  Excel file
    Route::get('/invoices-excel', [InvoiceController::class, 'exportInvoicesExcel'])->name('admin.invoicesExcel');
    Route::get('/quotes-excel', [QuoteController::class, 'exportQuotesExcel'])->name('admin.quotesExcel');
    Route::get('/transactions-excel',
        [PaymentController::class, 'exportTransactionsExcel'])->name('admin.transactionsExcel');
    Route::get('/admin-payments-excel',
        [
            AdminPaymentController::class, 'exportAdminPaymentsExcel',
        ])->name('admin.paymentsExcel');

    //payment-gateway
    Route::post('payment-gateway/store',
        [PaymentGatewayController::class, 'store'])->name('payment-gateway.store')->middleware('multi_tenant');
    Route::get('payment-gateway',
        [PaymentGatewayController::class, 'show'])->name('payment-gateway.show')->middleware('multi_tenant');

    //Clear cache
    Route::get('clear-caches', [DashboardController::class, 'clearCache'])->name('clear-cache');

});

Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'xss', 'role:admin', 'checkUserStatus']], function () {
    // paypal subscription transaction
    Route::get('paypal-onboard', [AdminPaypalController::class, 'onBoard'])->name('admin.paypal.init');
    Route::get('paypal-payment-success', [AdminPaypalController::class, 'success'])->name('admin.paypal.success');
    Route::get('paypal-payment-failed', [AdminPaypalController::class, 'failed'])->name('admin.paypal.failed');

// Razor Pay Routes
    Route::post('razorpay-onboard',
        [AdminRazorpayController::class, 'onBoard'])->name('admin.razorpay.init');
    Route::post('razorpay-payment-success', [AdminRazorpayController::class, 'paymentSuccess'])
        ->name('admin.razorpay.success');
    Route::post('razorpay-payment-failed', [AdminRazorpayController::class, 'paymentFailed'])
        ->name('admin.razorpay.failed');
    Route::post('razorpay-payment-failed-modal', [AdminRazorpayController::class, 'paymentFailedModal'])
        ->name('admin.razorpay.failed.modal');

    //payment-gateway
    Route::post('payment-gateway/store',
        [PaymentGatewayController::class, 'store'])->name('payment-gateway.store');
    Route::get('payment-gateway',
        [PaymentGatewayController::class, 'show'])->name('payment-gateway.show');

    // manual payment route
    Route::post('cash-payment', [CashController::class, 'pay'])->name('subscription.cash-payment');
    Route::get('change-transaction-status/{id}',
        [PaymentController::class, 'changeTransactionStatus'])->name('change-transaction-status');
    Route::get('payment-notes/{paymentId}', [PaymentController::class, 'showPaymentNotes'])->name('payment-notes.show');
});


// Impersonate Logout
Route::get('/users/impersonate-logout',
    [UserController::class, 'userImpersonateLogout'])->name('impersonate.userLogout');

Route::group(['prefix' => 'super-admin', 'middleware' => ['auth', 'xss', 'role:super_admin']],
    function () {
        // View logs
        Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);

        Route::get('/impersonate/{user}', [UserController::class, 'impersonate'])->name('impersonate');

        //super Admin dashboard route
        Route::get('/dashboard',
            [DashboardController::class, 'SuperAdminDashboardData'])->name('super.admin.dashboard');
        Route::get('revenue-chart',
            [DashboardController::class, 'getRevenueChartData'])->name('super-admin.revenue-chart');
        // User route
        Route::resource('users', UserController::class);

        Route::post('users/{id}/is-verified', [UserController::class, 'isVerified'])->name('users.verified');
        Route::post('users/{id}/active-deactive',
            [UserController::class, 'activeDeactiveStatus'])->name('users.status');
        Route::resource('subscription-plans', SubscriptionPlanController::class);
        Route::post('subscription-plans/{user}/make-plan-as-default',
            [SubscriptionPlanController::class, 'makePlanDefault'])->name('make.plan.default');

        // Transactions routes
        Route::get('transactions',
            [SubscriptionPlanController::class, 'showTransactionsLists'])->name('subscriptions.transactions.index');
        Route::get('transactions/{subscription}',
            [SubscriptionPlanController::class, 'viewTransaction'])->name('subscriptions.transactions.show');
        Route::get('change-payment-status/{id}',
            [SubscriptionPlanController::class, 'changePaymentStatus'])->name('change-payment-status');

        Route::group(['as' => 'super.admin.'], function () {
            Route::resource('currencies', AdminCurrencyController::class);
        });

        //enquires
        Route::get('enquiries', [SuperAdminEnquiryController::class, 'index'])->name('super.admin.enquiry.index');
        Route::delete('enquiries/{enquiry}',
            [SuperAdminEnquiryController::class, 'destroy'])->name('super.admin.enquiry.destroy');
        Route::get('enquiries/{enquiry}',
            [SuperAdminEnquiryController::class, 'show'])->name('super.admin.enquiry.show');

        //Landing CMS
        Route::get('section-one', [Landing\SectionOneController::class, 'index'])->name('super.admin.section.one');
        Route::put('section-one/update',
            [Landing\SectionOneController::class, 'update'])->name('super.admin.section.one.update');
        Route::get('section-two', [Landing\SectionTwoController::class, 'index'])->name('super.admin.section.two');
        Route::put('section-two/update',
            [Landing\SectionTwoController::class, 'update'])->name('super.admin.section.two.update');
        Route::get('section-three',
            [Landing\SectionThreeController::class, 'index'])->name('super.admin.section.three');
        Route::put('section-three/update',
            [Landing\SectionThreeController::class, 'update'])->name('super.admin.section.three.update');

        Route::resource('faqs', FaqController::class);
        Route::post('faqs/{faqs}', [FaqController::class, 'update'])->name('faqs-update');
        Route::resource('admin-testimonial', Landing\AdminTestimonialController::class);

        // setting routes
        Route::get('general-settings',
            [SettingController::class, 'editSuperAdminSettings'])->name('super.admin.settings.edit');
        Route::post('general-settings',
            [SettingController::class, 'updateSuperAdminSettings'])->name('super.admin.settings.update');
        Route::get('footer-settings',
            [SettingController::class, 'editSuperAdminFooterSettings'])->name('super.admin.footer.settings.edit');
        Route::post('footer-settings',
            [SettingController::class, 'updateSuperAdminFooterSettings'])->name('super.admin.footer.settings.update');

        // Subscribers Route
        Route::get('subscribers', [Landing\SubscriberController::class, 'index'])->name('super.admin.subscribe.index');
        Route::delete('subscribers/{subscriber}',
            [Landing\SubscriberController::class, 'destroy'])->name('super.admin.subscribe.destroy');
        Route::get('/subscribers-excel',
            [Landing\SubscriberController::class, 'exportSubscribersExcel'])->name('super.admin.subscribe.excel');
    });

Route::get('my-transactions',
    [
        SubscriptionPlanController::class, 'showTransactionsLists',
    ])->name('subscriptions.plans.transactions.index');
Route::get('my-transactions/{subscription}',
    [SubscriptionPlanController::class, 'viewTransaction'])->name('subscriptions.plans.transactions.show');

Route::group(['prefix' => 'client', 'middleware' => ['auth', 'xss', 'role:client']], function () {
    Route::get('dashboard',
        [Client\DashboardController::class, 'index'])->name('client.dashboard');

    //Invoice
    Route::get('invoices',
        [Client\InvoiceController::class, 'index'])->name('client.invoices.index');
    Route::get('invoices/{invoice}',
        [Client\InvoiceController::class, 'show'])->name('client.invoices.show')->withoutMiddleware('multi_tenant');
    Route::get('invoices/{invoice}/pdf', [
        Client\InvoiceController::class, 'convertToPdf',
    ])->name('clients.invoices.pdf')->withoutMiddleware('multi_tenant');

    //Payments
    Route::get('invoices/{invoice}/payment', [Client\PaymentController::class, 'show'])->name('clients.payments.show');
    Route::post('payments', [Client\PaymentController::class, 'store'])->name('clients.payments.store');

    Route::post('stripe-payment', [Client\StripeController::class, 'createSession'])->name('client.stripe-payment');
    Route::get('payment-success', [Client\StripeController::class, 'paymentSuccess'])->name('client.payment-success');
    Route::get('failed-payment',
        [Client\StripeController::class, 'handleFailedPayment'])->name('client.failed-payment');

    Route::get('paypal-onboard', [Client\PaypalController::class, 'onBoard'])->name('paypal.init');
    Route::get('paypal-payment-success', [Client\PaypalController::class, 'success'])->name('paypal.success');
    Route::get('paypal-payment-failed', [Client\PaypalController::class, 'failed'])->name('paypal.failed');
    Route::get('transactions', [Client\PaymentController::class, 'index'])->name('client.transactions.index');

    // razorpay payment
    Route::get('razorpay-onboard', [Client\RazorpayController::class, 'onBoard'])->name('razorpay.init');
    Route::post('razorpay-payment-success', [Client\RazorpayController::class, 'paymentSuccess'])
        ->name('razorpay.success');
    Route::post('razorpay-payment-failed', [Client\RazorpayController::class, 'paymentFailed'])
        ->name('razorpay.failed')->middleware('');
    Route::get('razorpay-payment-webhook', [Client\RazorpayController::class, 'paymentSuccessWebHook'])
        ->name('razorpay.webhook');
    //export Excel file
    Route::get('/invoice-excel',
        [client\InvoiceController::class, 'exportInvoicesExcel'])->name('client.invoicesExcel');
    Route::get('/transactions-excel',
        [client\PaymentController::class, 'exportTransactionsExcel'])->name('client.transactionsExcel');
});

Route::group(['middleware' => ['auth', 'xss', 'multi_tenant', 'checkUserStatus']], function () {
    // Update profile
    Route::get('/profile/edit', [UserController::class, 'editProfile'])->name('profile.setting');
    Route::put('/profile/update', [UserController::class, 'updateProfile'])->name('update.profile.setting');
    Route::put('/change-user-password', [UserController::class, 'changePassword'])->name('user.changePassword');
});


Route::group([
    'middleware' => ['auth', 'xss', 'role:admin', 'checkUserStatus'],
], function () {

//Subscription Pricing Plans
    Route::get('subscription-plans',
        [SubscriptionPricingPlanController::class, 'index'])->name('subscription.pricing.plans.index');
// routes for payment types.
    Route::get('choose-payment-type/{planId}/{context?}/{fromScreen?}',
        [SubscriptionPricingPlanController::class, 'choosePaymentType'])->name('choose.payment.type');

// stripe subscription transaction
    Route::post('purchase-subscription',
        [SubscriptionController::class, 'purchaseSubscription'])->name('purchase-subscription');
    Route::get('payment-success', [SubscriptionController::class, 'paymentSuccess'])->name('payment-success');
    Route::get('failed-payment', [SubscriptionController::class, 'handleFailedPayment'])->name('failed-payment');

});


require __DIR__.'/upgrade.php';

include "landing.php";



