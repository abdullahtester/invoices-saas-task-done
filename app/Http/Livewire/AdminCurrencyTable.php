<?php

namespace App\Http\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\AdminCurrency;

class AdminCurrencyTable extends LivewireTableComponent
{
    protected $model = AdminCurrency::class;
    
    protected string $tableName = 'admin_currencies';

    // for table header button
    public $showButtonOnHeader = true;
    public $buttonComponent = 'super_admin.currencies.components.add-button';

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('created_at', 'desc');
        $this->setQueryStringStatus(false);
        $this->setThAttributes(function (Column $column) {
            if ($column->isField('id')) {
                return [
                    'class' => 'text-center p-r-23',
                ];
            }
            if ($column->isField('icon')) {
                return [
                    'class' => 'w-25',
                ];
            }

            return [];
        });
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.common.name'), "name")
                ->sortable()
                ->searchable(),
            Column::make(__('messages.currency.icon'), "icon")
                ->sortable()
                ->searchable(),
            Column::make(__('messages.currency.currency_code'), "code")
                ->sortable()
                ->searchable(),
            Column::make(__('messages.common.action'), "id")
                ->format(function ($value, $row, Column $column) {
                    return view('livewire.modal-action-button')
                        ->withValue([
                            'data-id' => $row->id,
                            'data-delete-id' => 'admin-currency-delete-btn',
                            'data-edit-id'   => 'admin-currency-edit-btn',
                        ]);
                }),
        ];
    }
    public function builder(): Builder
    {
        return AdminCurrency::query()->select('admin_currencies.*');
    }
}
