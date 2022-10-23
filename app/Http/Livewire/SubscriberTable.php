<?php

namespace App\Http\Livewire;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;

class SubscriberTable extends LivewireTableComponent
{
    protected $model = Subscriber::class;
    protected string $tableName = 'subscribers';

    public $showButtonOnHeader = true;
    public $buttonComponent = 'subscribe.components.export-button';

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('created_at', 'desc');
        $this->setQueryStringStatus(false);

        $this->setTdAttributes(function (Column $column, $row, $columnIndex, $rowIndex) {
            if ($columnIndex == '0') {
                return [
                    'class' => 'w-90',
                ];
            }

            return [];
        });
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.user.email'), "email")
                ->sortable()
                ->searchable(),
            Column::make(__('messages.common.action'), "id")
                ->format(function($value, $row, Column $column) {
                    return view('livewire.action-button')
                        ->withValue([
                            'data-id' => $row->id,
                            'data-delete-id' => 'subscriber-delete-btn',
                        ]);
                })
        ];
    }

    public function builder(): Builder
    {
        return Subscriber::select('subscribers.*');
    }
}
