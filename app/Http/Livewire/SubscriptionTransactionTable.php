<?php

namespace App\Http\Livewire;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class SubscriptionTransactionTable extends LivewireTableComponent
{
    protected $model = Transaction::class;

    protected string $tableName = 'transactions';

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('created_at', 'desc');
        $this->setQueryStringStatus(false);
        $this->setThAttributes(function (Column $column) {
            if (! in_array($column->getTitle(), ['Users', 'Payment', 'Payment Approved','Amount'])) {
                return [
                    'class' => 'text-center livewire-th-center',
                ];
            }
            if($column->isField('amount')) {
                return [
                    'class' => 'd-flex justify-content-end',
                ];
            }
            return [
                
            ];
          
        });
        $this->setTdAttributes(function (Column $column, $row, $columnIndex, $rowIndex) {
            if (in_array($column->getTitle(), ['Transaction Date', 'Status'])) {
                return [
                    'class' => 'text-center',
                ];
            }
            if($column->getField() === 'amount') {
                return [
                    'class' => 'text-end',
                ];
            }

            return [
                'class' => 'text-left',
            ];
        });
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.users'), "user.first_name")
                ->sortable()
                ->searchable()
                ->format(function($value, $row, Column $column){
                    return $row->user->full_name;
                }),
            Column::make(__('messages.subscription_plans.payment'), "payment_mode")
                ->searchable()
                ->view('subscription_transactions.components.payment-mode'),
            Column::make(__('messages.subscription_plans.amount'), "amount")
                ->sortable()
                ->searchable()
                ->format(function($value, $row, Column $column){
                    return superAdminCurrencyAmount($row->amount,false,getAdminSubscriptionPlanCurrencyIcon($row->transactionSubscription->subscriptionPlan->currency_id));
                }),
            Column::make(__('messages.subscription_plans.transaction_date'), "created_at")
                ->sortable()
                ->searchable()
                ->view('subscription_transactions.components.transaction-date'),
            Column::make(__('messages.subscription_plans.payment_approved'), "id")
                ->searchable()
                ->view('subscription_transactions.components.payment-approved'),
           
            Column::make(__('messages.common.status'), "status")
                ->sortable()
                ->searchable()
                ->view('subscription_transactions.components.payment-status'),
        ];
    }
    public function builder(): Builder
    {
        $query = Transaction::with([
            'transactionSubscription.subscriptionPlan', 'user.media',
        ])->whereHas('transactionSubscription')->select('transactions.*');

        if (getLoggedInUser()->hasRole('admin')) {
            $query->where('user_id', '=', getLogInUserId());
        }
        return $query;
    }

    public function filters(): array {
        $paymentType = Transaction::PAYMENT_TYPES;
        return [
            SelectFilter::make(__('messages.subscription_plans.payment_type').':')
                ->options($paymentType)
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('payment_mode', $value);
                }),
        ];
    }
}
