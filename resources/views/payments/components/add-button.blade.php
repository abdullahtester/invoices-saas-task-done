<div class="me-1">
    <a href="{{ route('admin.paymentsExcel') }}" type="button" class="btn btn-outline-success me-2" data-turbo="false">
        <i class="fas fa-file-excel me-1"></i> {{__('messages.invoice.excel_export')}}
    </a>
</div>
<div class="me-4">
    <a class="btn btn-primary addPayment">
        {{ __('messages.payment.add_payment') }}
    </a>
</div>

