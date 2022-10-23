<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * App\Models\Transaction
 *
 * @method static create(array $transactionData)
 * @property int $id
 * @property string $transaction_id
 * @property string|null $tenant_id
 * @property int|null $payment_mode
 * @property float $amount
 * @property int $user_id
 * @property bool $status
 * @property int|null $is_manual_payment
 * @property array $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Subscription $subscription
 * @property-read \App\Models\MultiTenant|null $tenant
 * @property-read \App\Models\Subscription|null $transactionSubscription
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereIsManualPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction wherePaymentMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUserId($value)
 * @mixin \Eloquent
 */
class Transaction extends Model
{
    use HasFactory, BelongsToTenant, Multitenantable;

    protected $table = 'transactions';
    public $fillable = ['transaction_id', 'amount', 'status', 'meta', 'tenant_id', 'user_id', 'payment_mode'];


    protected $casts = [
        'meta'   => 'json',
        'status' => 'boolean',
    ];

    const PAID = 'Paid';
    const UNPAID = 'Unpaid';

    const APPROVED = 1;
    const DENIED = 2;
    
    const TYPE_STRIPE = 1;
    const TYPE_PAYPAL = 2;
    const TYPE_RAZORPAY = 3;
    const TYPE_CASH = 4;

    const PAYMENT_TYPES = [
        self::TYPE_STRIPE   => 'Stripe',
        self::TYPE_PAYPAL   => 'PayPal',
        self::TYPE_RAZORPAY => 'RazorPay',
        self::TYPE_CASH     => 'Manual',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'transaction_id');
    }

    /**
     * @return HasOne
     */
    public function transactionSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'transaction_id', 'id');
    }
}
