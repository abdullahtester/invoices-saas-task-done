<div class="dropup position-static" wire:key="{{ $row->id }}">

    <button wire:key="invoice-{{ $row->id }}" type="button" title="Action" class="dropdown-toggle hide-arrow btn px-2 text-primary fs-3 pe-0"
            id="dropdownMenuButton1" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
        <i class="fa-solid fa-ellipsis-vertical"></i>
    </button>
    @php
        $isEdit = ($row->status == 2 || $row->status == 3)  ? 1 : 0;
        $isPaid = ($row->status == 2 || $row->status == 0) ? 1 : 0;
        $isDraft = ($row->status == 0) ? 0 : 1;
@endphp
<ul class="dropdown-menu min-w-170px" aria-labelledby="dropdownMenuButton1">
@if($isEdit != 1)
    <li>
    <a href="{{route('invoices.edit',$row->id)}}" class="dropdown-item text-hover-primary me-1 edit-btn" data-bs-toggle="tooltip" title="Edit">
            <?php echo __('messages.common.edit') ?>
    </a>
</li>
@endif
    <li>
           <a href="#" data-id="{{$row->id}}" class="invoice-delete-btn dropdown-item me-1 text-hover-primary" data-bs-toggle="tooltip" title="Delete">
                     <?php echo __('messages.common.delete') ?>
    </a>
  </li>
  @if($isPaid != 1)
    <li>
         <a href="#" data-id="{{$row->id}}" class="reminder-btn dropdown-item me-1 text-hover-primary" data-bs-toggle="tooltip" title="Payment Reminder Mail">
                      <?php echo __('messages.common.reminder') ?>
         </a>
    </li>
@endif
    @if($isDraft)
    <li>
          <a href="javascript:void(0)" data-url="{{route('invoice-show-url',$row->invoice_id)}}" class="dropdown-item text-hover-primary me-1 edit-btn  invoice-url" data-bs-toggle="tooltip" title="Copy Invoice URL" onclick="copyToClipboard($(this).data('url'))">
            Invoice URL
            </a>
        </li>
@endif
    </ul>
   </div>

