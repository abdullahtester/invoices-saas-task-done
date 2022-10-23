<a class="show-payment-notes cursor-pointer" data-id="{{$row->id}}" wire:key="transaction-{{ $row->id }}">
    <span class="badge bg-light-info fs-7 mt-1">
        {{ is_null($row->transaction_id) ?  __('messages.common.n/a') : $row->transaction_id}}
    </span>
</a>
