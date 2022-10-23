<div class="">
    <a href="{{ route('admin.invoicesExcel') }}" type="button" class="btn btn-outline-success me-2" data-turbo="false">
        <i class="fas fa-file-excel me-1"></i> {{__('messages.invoice.excel_export')}}
    </a>
</div>
<a href="{{ route('invoices.create') }}"
   class="btn btn-primary">{{__('messages.invoice.new_invoice')}}</a>
