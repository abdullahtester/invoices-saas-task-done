@php
    $auth =  \Illuminate\Support\Facades\Auth::check();
@endphp
<div>
    @if($auth && $isPublicView)
        <div class="d-flex overflow-auto h-55px">
            <ul class="nav nav-tabs mb-5 pb-1 overflow-auto flex-nowrap text-nowrap">
                <li class="nav-item position-relative me-7 mb-3" role="presentation">
                    <button class="nav-link active p-0" id="overview-tab" data-bs-toggle="tab"
                            data-bs-target="#overview"
                            type="button" role="tab" aria-controls="overview" aria-selected="true">
                        {{ __('messages.invoice.overview') }}
                    </button>
                </li>
                <li class="nav-item position-relative me-7 mb-3" role="presentation">
                    <button class="nav-link p-0" id="note_terms-tab" data-bs-toggle="tab" data-bs-target="#note_terms"
                            type="button" role="tab" aria-controls="note_terms" aria-selected="false">
                        {{ __('messages.invoice.note_terms') }}
                    </button>
                </li>
                <li class="nav-item position-relative me-7 mb-3" role="presentation">
                    <button class="nav-link p-0" id="paymentHistory-tab" data-bs-toggle="tab"
                            data-bs-target="#paymentHistory"
                            type="button" role="tab" aria-controls="paymentHistory" aria-selected="false">
                        {{ __('messages.invoice.payment_history') }}
                    </button>
                </li>
            </ul>
        </div>
    @endif
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <div class="">
                <div class="d-flex flex-column">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-xxl-9">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6">
                                            <div class="d-flex align-items-center mb-md-10 mb-5">
                                                <div class="image image-circle image-lg-small">
                                                    <img src="{{ getLogoUrl($invoice->tenant_id) }}" alt="logo" class="object-contain">
                                                </div>
                                                <h3 class="ps-7">Invoice #{{ $invoice->invoice_id }}</h3>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-3 col-6">
                                            <div class="d-flex flex-column mb-md-10 mb-5 mt-3 mt-md-0">
                                                <label for="name"
                                                       class="pb-2 fs-4 text-gray-600">{{ __('messages.invoice.invoice_date').':' }}</label>
                                                <span class="fs-4 text-gray-800">{{ \Carbon\Carbon::parse($invoice->invoice_date)->translatedFormat(currentDateFormat()) }}</span>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-3 col-6">
                                            <div class="d-flex flex-column mb-md-10 mb-5 mt-3 mt-md-0">
                                                <label for="name"
                                                       class="pb-2 fs-4 text-gray-600">{{ __('messages.invoice.due_date').':' }}</label>
                                                    <span class="fs-4 text-gray-800">{{ \Carbon\Carbon::parse($invoice->due_date)->translatedFormat(currentDateFormat()) }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-6">
                                            @if($isPublicView)
                                                <a target="_blank"
                                                   href="{{ route('invoices.pdf',['invoice' => $invoice->id]) }}"
                                                   class="btn btn-sm btn-success text-white">{{ __('messages.invoice.print_invoice') }}</a>
                                            @else
                                                <a target="_blank"
                                                   href="{{ route('public-view-invoice.pdf',['invoice' => $invoice->invoice_id]) }}"
                                                   class="btn btn-sm btn-success text-white">{{ __('messages.invoice.print_invoice') }}</a>
                                            @endif
                                        </div>
                                        <div class="col-lg-5 col-md-6">
                                            <div class="d-flex flex-column mb-md-10 mb-5">
                                                <label for="name"
                                                       class="pb-2 fs-4 text-gray-600">{{ __('messages.invoice.issue_for').':' }}</label>
                                                <span class="fs-4 text-gray-800 mb-3">{{ $invoice->client->user->full_name }}</span>
                                                <p class="text-gray-700 fs-4 mb-0">
                                                    @if(isset($invoice->client->address) && !empty($invoice->client->address))
                                                        {{ ucfirst($invoice->client->address) }}
                                                    @else
                                                        {{ "N/A" }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-md-6">
                                            <div class="d-flex flex-column mb-md-10 mb-5">
                                                <label for="name"
                                                       class="pb-2 fs-4 text-gray-600">{{ __('messages.invoice.issue_by').':' }}</label>
                                                <span class="fs-4 text-gray-800 mb-3">{{ getSettingValue('app_name', $invoice->tenant_id) }}</span>
                                                <p class="text-gray-700 fs-4 mb-0">
                                                    {{ getSettingValue('company_address', $invoice->tenant_id) }}</p>
                                            </div>
                                        </div>
                                        <div class="col-12 overflow-auto">
                                            <table class="table table-striped box-shadow-none mt-4">
                                                <thead>
                                                <tr>
                                                    <th scope="col">{{ __('messages.product.product') }}</th>
                                                    <th scope="col">{{ __('messages.invoice.qty') }}</th>
                                                    <th scope="col">{{ __('messages.invoice.price') }}</th>
                                                    <th scope="col">{{ __('messages.invoice.tax').' (in %)' }}</th>
                                                    <th scope="col">{{ __('messages.invoice.amount') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($invoice->invoiceItems as $index => $invoiceItem)
                                                    <tr>
                                                        <td class="py-4">{{ isset($invoiceItem->product->name)?$invoiceItem->product->name :$invoiceItem->product_name ?? 'N/A' }}</td>
                                                        <td class="py-4">{{ $invoiceItem->quantity }}</td>
                                                        <td class="py-4">{{ !empty($invoiceItem->price) ? getInvoiceCurrencyAmount($invoiceItem->price,$invoice->currency_id,true,$invoice->tenant_id) : 'N/A' }}</td>
                                                        <td class="py-4">
                                                            @foreach($invoiceItem->invoiceItemTax as $keys => $tax)
                                                                {{ ($tax->tax != 0) ? $tax->tax : 'N/A' }}
                                                                @if (!$loop->last)
                                                                    ,
                                                                @endif
                                                            @endforeach
                                                        </td>
                                                        <td class="py-4">{{ !empty($invoiceItem->total) ? getInvoiceCurrencyAmount($invoiceItem->total,$invoice->currency_id,true,$invoice->tenant_id) : 'N/A' }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="col-lg-5 ms-lg-auto mt-4">
                                            <div class="border-top">
                                                <table class="table table-borderless  box-shadow-none mb-0 mt-5">
                                                    <tbody>
                                                    <tr>
                                                        <td class="ps-0">{{ __('messages.invoice.sub_total').(':') }}</td>
                                                        <td class="text-gray-900 text-end pe-0">{{ !empty($invoice->amount) ? getInvoiceCurrencyAmount($invoice->amount,$invoice->currency_id,true,$invoice->tenant_id) : 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="ps-0">{{ __('messages.invoice.discount').(':') }}</td>
                                                        <td class="text-gray-900 text-end pe-0">
                                                        @if($invoice->discount == 0 || !isset($invoice))
                                                                <span>N/A</span>
                                                            @else
                                                                @if( $invoice->discount_type == \App\Models\Invoice::FIXED)
                                                                    {{getInvoiceCurrencyAmount($invoice->discount,$invoice->currency_id,true,$invoice->tenant_id) }}
                                                                @else
                                                                    {{getInvoiceCurrencyAmount(($invoice->amount * $invoice->discount / 100),$invoice->currency_id,true,$invoice->tenant_id)}}
                                                                @endif
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="ps-0">{{ __('messages.invoice.tax').(':') }}</td>
                                                        <td class="text-gray-900 text-end pe-0">
                                                            {!!   (numberFormat(array_sum($totalTax)) != 0 )
                                                              ? getInvoiceCurrencyAmount(array_sum($totalTax),$invoice->currency_id,true,$invoice->tenant_id) :'N/A' !!}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="ps-0">{{ __('messages.invoice.total').(':') }}</td>  
                                                        <td class="text-gray-900 text-end pe-0">
                                                            {{ getInvoiceCurrencyAmount($invoice->final_amount,$invoice->currency_id,true,$invoice->tenant_id) }}
                                                        </td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xxl-3">
                                    <div
                                        class="bg-gray-100 rounded-15 p-md-7 p-5 h-100 mt-xxl-0 mt-5 col-xxl-9 ms-xxl-auto w-100">
                                        <div class="mb-8">
                                            @if($invoice->status == \App\Models\Invoice::UNPAID)
                                                <span class="badge bg-light-danger">Pending Payment</span>
                                            @elseif($invoice->status == \App\Models\Invoice::PAID)
                                                <span class="badge bg-light-success me-2">Paid</span>
                                            @elseif($invoice->status == \App\Models\Invoice::PARTIALLY)
                                                <span class="badge bg-light-primary">Partially Paid</span>
                                            @elseif($invoice->status == \App\Models\Invoice::DRAFT)
                                                <span class="badge bg-light-warning me-5">Draft</span>
                                            @elseif($invoice->status == \App\Models\Invoice::OVERDUE)
                                                <span class="badge bg-light-danger">Overdue</span>
                                            @elseif($invoice->status == \App\Models\Invoice::PROCESSING)
                                                <span class="badge bg-light-primary">Processing</span>
                                            @endif
                                            @if($invoice->status == \App\Models\Invoice::DRAFT)
                                                <button class="btn btn-success send-btn btn-sm"
                                                        data-id="{{ $invoice->id }}">Send
                                                </button>
                                            @endif
                                        </div>

                                        <h3 class="mb-5">{{ __('messages.invoice.client_overview') }}</h3>
                                        <div class="row">
                                            <div class="col-xxl-12 col-lg-4 col-sm-6 d-flex flex-column mb-xxl-7 mb-lg-0 mb-4">
                                                <label for="name" class="pb-2 fs-4 text-gray-600">{{ __('messages.invoice.client_name') }}</label>
                                                @if($auth && \Illuminate\Support\Facades\Auth::user()->hasRole('admin'))
                                                    <a href="{{ route('clients.show', ['clientId' => $invoice->client->id]) }}"
                                                       class="link-primary text-decoration-none">{{ $invoice->client->user->full_name }}</a>
                                                @else
                                                    <a href="#"
                                                       class="link-primary fs-4 text-decoration-none">{{ $invoice->client->user->full_name }}</a>
                                                @endif
                                            </div>
                                            <div
                                                class="col-xxl-12 col-lg-4 col-sm-6 d-flex flex-column mb-xxl-7 mb-lg-0 mb-4">
                                                <label for="name"
                                                       class="pb-2 fs-4 text-gray-600">{{ __('messages.invoice.client_email') }}</label>
                                                <span
                                                    class="fs-4 text-gray-900">{{ $invoice->client->user->email ?? 'N/A'}}</span>
                                            </div>
                                            @php
                                                $dueAmount = 0;
                                                $paid = 0;
                                                foreach ($invoice->payments as $payment){
                                                    if($payment->payment_mode == \App\Models\Payment::MANUAL && $payment->is_approved !== \App\Models\Payment::APPROVED){
                                                        continue;
                                                    }
                                                    $paid += $payment->amount;
                                                }
                                                $dueAmount = $invoice->final_amount - $paid;
                                            @endphp
                                            
                                            <div class="col-xxl-12 col-lg-4 col-sm-6 d-flex flex-column mb-xxl-7">
                                                <label for="name"
                                                       class="pb-2 fs-4 text-gray-600">{{ __('messages.invoice.paid_amount') }}</label>
                                                <span class="fs-4 text-gray-900">{{getInvoiceCurrencyAmount($paid,$invoice->currency_id,true,$invoice->tenant_id) }}</span>
                                            </div>
                                            <div class="col-xxl-12 col-lg-4 col-sm-6 d-flex flex-column mb-xxl-7">
                                                <label for="name"
                                                       class="pb-2 fs-4 text-gray-600">{{ __('messages.invoice.remaining_amount') }}</label>
                                                <span class="fs-4 text-gray-900">{{ getInvoiceCurrencyAmount($dueAmount,$invoice->currency_id,true,$invoice->tenant_id) }}
                                                </span>
                                            </div>
                                            @if(!$auth)
                                                <a target="_blank"
                                                   href="{{ route('client.invoices.index') }}"
                                                   class="btn btn-sm btn-warning mt-5">{{ __('messages.invoice.make_payment') }}</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade show" id="note_terms" role="tabpanel">
            <div class="card">
                <div class="card-body pt-5">
                    <div class="row">
                        <div class="col-lg-12 mb-5">
                            <div class="fw-bold text-gray-600 fs-7">{{ __('messages.invoice.note').':' }}</div>
                            <div class="fw-bolder text-gray-800 fs-6">{!! $invoice->note ?? __('messages.invoice.note_not_found') !!}</div>
                        </div>
                        <div class="col-lg-12 mb-5">
                            <div class="fw-bold text-gray-600 fs-7">{{ __('messages.invoice.terms').':' }}</div>
                            <div
                                class="fw-bolder text-gray-800 fs-6">{!! $invoice->term ?? __('messages.invoice.terms_not_found') !!}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade show" id="paymentHistory" role="tabpanel">
            <div class="row">
                <div class="col-lg-12 ">
                    <livewire:payment-history-table invoiceId="{{ $invoice->id }}"/>
                </div>
            </div>
        </div>
    </div>
</div>
