<div class="">
        <a href="{{ route('admin.quotesExcel') }}" type="button" class="btn btn-outline-success me-2" data-turbo="false">
            <i class="fas fa-file-excel me-1"></i>  {{__('messages.quote.excel_export')}}
        </a>
</div>

<a href="{{ route('quotes.create') }}"
   class="btn btn-primary">{{__('messages.quote.new_quote')}}</a>
