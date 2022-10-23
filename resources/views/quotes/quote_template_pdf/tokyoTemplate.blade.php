<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <link rel="icon" href="{{ asset('web/media/logos/favicon.ico') }}" type="image/png">
    <title>{{ __('messages.quote.quote_pdf') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
<div {{ $styleCss }}="border-top: 15px solid {{ $quote_template_color }}
;border-bottom: 15px solid {{ $quote_template_color }}">
<table width="100%" class="mt-4">
    <tr>
        <td>
            <div class="logo"><img width="100px" src="{{ getLogoUrl($quote->tenant_id) }}" alt=""></div>
        </td>
            <td width="190px">
                <div class="main-heading" {{ $styleCss }}="color: {{ $quote_template_color }};">Quote</div>
            </td>
        </tr>
        <tr>
            <td>
                <span {{ $styleCss }}="color: {{ $quote_template_color }}">{{ html_entity_decode(getAppName($quote->tenant_id)) }}</span><br>
                <b>Address:&nbsp;</b>{{ $setting['company_address'] }}<br>
                <b>Mo:&nbsp;</b>{{ $setting['company_phone'] }}
            </td>
        </tr>
    </table>
    <br>
    <table width="100%">
        <thead>
        <tr>
            <td class="vertical-align-top">
                <strong class="to-font-size" {{ $styleCss }}="color: {{ $quote_template_color }};">To:</strong><br>
                <b>Name:&nbsp;</b>{{ $client->user->full_name }}<br>
                <b>Email:&nbsp;</b>{{ $client->user->email }}
            </td>
            <td class="vertical-align-top" width="240px">
                <strong>Quote Id:&nbsp;</strong>#{{ $quote->quote_id }}<br>
                <strong>Quote
                    Date:&nbsp;</strong>{{\Carbon\Carbon::parse($quote->quote_date)->translatedFormat(currentDateFormat()) }}
                <br>
                <strong>Due
                    Date:&nbsp;</strong>{{\Carbon\Carbon::parse($quote->due_date)->translatedFormat(currentDateFormat()) }}
            </td>
        </tr>
        </thead>
    </table>
<table width="100%" class="mb-5">
    <tr class="invoice-items">
        <td colspan="2">
            <table class="items-table">
                <thead {{ $styleCss }}="border-top: 2px solid {{ $quote_template_color }}">
                <tr class="tu">
                    <th>#</th>
                    <th>{{ __('messages.product.product') }}</th>
                    <th class="number-align">{{ __('messages.invoice.qty') }}</th>
                    <th class="number-align">{{ __('messages.product.unit_price') }}</th>
                    <th class="number-align">{{ __('messages.invoice.amount') }}</th>
                </tr>
                </thead>
                <tbody {{ $styleCss }}="border-bottom: 2px solid {{ $quote_template_color }}">
                @if(isset($quote) && !empty($quote))
                    @foreach($quote->quoteItems as $key => $quoteItems)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ isset($quoteItems->product->name)?$quoteItems->product->name:$quoteItems->product_name??'N/A' }}</td>
                            <td class="number-align">{{ $quoteItems->quantity }}</td>
                            <td class="number-align"><b class="euroCurrency">{{isset($quoteItems->price) ? getCurrencyAmount($quoteItems->price,true): 'N/A' }}</b>
                            </td>
                            <td class="number-align"><b class="euroCurrency">{{isset($quoteItems->total) ? getCurrencyAmount($quoteItems->total,true): 'N/A'}}</b>
                            </td>
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
            <table class="invoice-footer">
                <tr>
                    <td class="font-weight-bold tu">Amount:</td>
                    <td class="text-nowrap">
                        <b class="euroCurrency">{{ getCurrencyAmount($quote->amount,true) }}</b>
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold tu">Discount:</td>
                    <td class="text-nowrap">
                        @if($quote->discount == 0)
                            <span>N/A</span>
                        @else
                            @if(isset($quote) && $quote->discount_type == \App\Models\Quote::FIXED)
                                <b class="euroCurrency">{{isset($quote->discount) ? getCurrencyAmount($quote->discount,true): 'N/A'}}</b>
                            @else
                                {{ $quote->discount }}<span {{ $styleCss }}="font-family: DejaVu Sans">
                                &#37;</span>
                            @endif
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold tu">Total:</td>
                    <td class="text-nowrap">
                    <b class="euroCurrency">{{ getCurrencyAmount($quote->final_amount,true) }}</b>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table class="w-100">
                <tr>
                    <td>
                            <strong>{{ __('messages.client.notes') }} :</strong>
                            <p class="font-color-gray">{!! nl2br(($quote->note ?? 'N/A')) !!}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{{ __('messages.invoice.terms') }} :</strong><br>
                            <p class="font-color-gray">{!! nl2br(($quote->term ?? 'N/A')) !!}</p>
                        </td>
                    </tr>
                </table>
                <br>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="vertical-align-bottom">
                <br><strong>Regards</strong>
                <br><span {{ $styleCss }}="color: {{ $quote_template_color }}">{{getAppName($quote->tenant_id) }}</span>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
