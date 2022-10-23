<div class="dropup position-static" wire:key="{{ $row->id }}">
    <button wire:key="client-invoice-{{ $row->id }}" type="button" title="Action"
            class="dropdown-toggle hide-arrow btn px-2 text-primary fs-3 pe-0"
            id="dropdownMenuButton1" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
        <i class="fa-solid fa-ellipsis-vertical"></i>
    </button>
    <ul class="dropdown-menu min-w-170px" aria-labelledby="dropdownMenuButton1">
        <li><a class="dropdown-item" href="{{route('clients.invoices.pdf', $row->id)}}"
               target="_blank"><?php echo __('messages.invoice.download') ?></a></li>
        @if(!empty($row->payments) && !empty($row->payments->last()))
                @if($row->payments->last()->is_approved != \App\Models\Payment::APPROVED && $row->payments->last()->payment_mode == 1
 || $row->status_label == 'Partially Paid')
                    <li><a data-turbo="false" class="dropdown-item payment"
                           href="{{route('clients.payments.show',$row->id)}}"
                           data-id="{{$row->id}}"><?php echo __('messages.invoice.make_payment') ?></a></li>
                @endif
        @else
            @if($row->status_label != 'Paid')
            <li><a data-turbo="false" class="dropdown-item payment" href="{{route('clients.payments.show',$row->id)}}"
                   data-id="{{$row->id}}"><?php echo __('messages.invoice.make_payment') ?></a></li>
                @endif
        @endif
    </ul>
</div>

