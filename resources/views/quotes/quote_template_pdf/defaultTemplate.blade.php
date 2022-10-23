<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "//www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <link rel="icon" href="{{ asset('web/media/logos/favicon.ico') }}" type="image/png">
    <title>{{ __('messages.quote.quote_pdf') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Fonts -->
    <!-- General CSS Files -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/css/invoice-pdf.css') }}" rel="stylesheet" type="text/css"/>
    <style>
        * {
            font-family: DejaVu Sans, Arial, "Helvetica", Arial, "Liberation Sans", sans-serif;
        }

        @if(getCurrencySymbol($quote->tenant_id) == '€')
        .euroCurrency {
            font-family: Arial, "Helvetica", Arial, "Liberation Sans", sans-serif;
        }
        @endif
    </style>
</head>
<body>
@php $styleCss = 'style'; @endphp
<table width="100%">
    <tr>
        <td class="header-left">
            <div class="main-heading" {{ $styleCss }}="font-size: 40px">Quote</div>
            <div class="invoice-number font-color-gray">Quote Id:&nbsp;#{{ $quote->quote_id }}</div>
        </td>
        <td class="header-right">
            <div class="logo"><img width="100px" src="{{ getLogoUrl($quote->tenant_id) }}" alt="no-image"></div>
        </td>
    </tr>
</table>
<br>
<table width="100%">
    <thead>
    <tr>
        <td colspan="2" class="vertical-align-top" width="200px">
            <strong class="from-font-size">From :</strong><br>
            {{ html_entity_decode(getAppName($quote->tenant_id)) }}<br>
            <b>Address:&nbsp;</b>{{ $setting['company_address'] }}<br>
            <b>Mo:&nbsp;</b>{{ $setting['company_phone'] }}
        </td>
        <td colspan="5" class="vertical-align-top">
            <strong class="from-font-size">To :</strong><br>
            <b>Name:&nbsp;</b>{{ $client->user->full_name }}<br>
            <b>Email:&nbsp;</b>{{ $client->user->email }}
        </td>
    </tr>
    </thead>
</table>
<br>
<table width="100%">
    <thead class="text-right">
    <tr>
        <td>
            <strong>Quote
                Date:&nbsp;</strong>{{\Carbon\Carbon::parse($quote->quote_date)->translatedFormat(currentDateFormat()) }}<br>
            <strong>Due Date:&nbsp;</strong>{{\Carbon\Carbon::parse($quote->due_date)->translatedFormat(currentDateFormat()) }}
        </td>
    </tr>
    </thead>
</table>

<table class="w-100">
    <tr class="invoice-items">
        <td colspan="2">
            <table class="d-items-table table-striped table-bordered">
                <thead>
                <tr {{ $styleCss }}="background:{{ $quote_template_color }};">
                <th {{ $styleCss }}="border: 1px solid; padding: 5px;">#</th>
                <th {{ $styleCss }}="border: 1px solid; padding: 5px;">{{ __('messages.product.product') }}</th>
                <th class="number-align"
                {{ $styleCss }}="border: 1px solid; padding: 5px;">{{ __('messages.invoice.qty') }}</th>
                <th class="number-align"
                {{ $styleCss }}="border: 1px solid; padding: 5px;">{{ __('messages.product.unit_price') }}</th>
                <th class="number-align"
                {{ $styleCss }}="border: 1px solid; padding: 5px;">{{ __('messages.invoice.amount') }}</th>
                </tr>
                </thead>
                <tbody>
                @if(isset($quote) && !empty($quote))
                    @foreach($quote->quoteItems as $key => $quoteItems)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ isset($quoteItems->product->name)?$quoteItems->product->name:$quoteItems->product_name??'N/A' }}</td>
                            <td class="number-align">{{ $quoteItems->quantity }}</td>
                            <td class="number-align"><b class="euroCurrency">{{isset($quoteItems->price) ? getCurrencyAmount($quoteItems->price,true): 'N/A' }}</b></td>
                            <td class="number-align"><b class="euroCurrency">{{isset($quoteItems->total) ? getCurrencyAmount($quoteItems->total,true): 'N/A'}}</b></td>
                        </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td></td>
        <td>
            <table class="d-invoice-footer">
                <tr>
                    <td class="font-weight-bold">{{ __('messages.invoice.sub_total') }}:</td>
                    <td class="text-nowrap">
                        <b class="euroCurrency">{{ getCurrencyAmount($quote->amount,true) }}</b>
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold">Discount:</td>
                    <td class="text-nowrap">
                        @if($quote->discount == 0)
                            <span>N/A</span>
                        @else
                            @if(isset($quote) && $quote->discount_type == \App\Models\Quote::FIXED)
                                <b class="euroCurrency">{{isset($quote->discount) ? getCurrencyAmount($quote->discount,true): 'N/A'}}</b>
                            @else
                                {{ $quote->discount }}<span {{ $styleCss }}="font-family: DejaVu Sans">&#37;</span>
                            @endif
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold">Total:</td>
                    <td class="text-nowrap">
                        <b class="euroCurrency">{{ getCurrencyAmount($quote->final_amount,true) }}</b>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br><br><br><br><br>
<table class="w-100">
    <tr>
        <td><p class="font-weight-bold mt-2">{{ __('messages.client.notes') }} :</p>
            <p class="font-color-gray">{!! nl2br(($quote->note ?? 'N/A'))!!}</p>
        </td>
    </tr>
</table>
<br>
<table class="w-100">
    <tr>
        <td><p class="font-weight-bold mt-2">{{ __('messages.invoice.terms') }} :</p>
            <p class="font-color-gray">{!!nl2br(($quote->term ?? 'N/A'))!!}</p>
        </td>
    </tr>
</table>
</body>
</html>
