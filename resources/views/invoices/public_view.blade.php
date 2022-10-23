<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Invoice | {{ getAppName($invoice->tenant_id) }}</title>
    <!-- Favicon -->
    <link rel="icon" href="{{ asset(getSettingValue('favicon_icon', $invoice->tenant_id)) }}" type="image/png">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700"/>
@yield('page_css')
<!-- General CSS Files -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/third-party.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ mix('assets/css/page.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/plugins.css') }}">
    @yield('css')
    @livewireStyles
    @routes
    @livewireScripts
    <script src="https://cdn.jsdelivr.net/gh/livewire/turbolinks@v0.1.x/dist/livewire-turbolinks.js"
            data-turbolinks-eval="false" data-turbo-eval="false"></script>
    <script data-turbo-eval="false">
        let decimalsSeparator = "{{ getSettingValue('decimal_separator') }}"
        let thousandsSeparator = "{{ getSettingValue('thousand_separator') }}"
        let currentDateFormat = "{{ currentDateFormat() }}"
        let momentDateFormat = "{{ momentJsCurrentDateFormat() }}"
        let ajaxCallIsRunning = false
        let phoneNo = ''
    </script>
    <script src="{{ asset('assets/js/third-party.js')}}"></script>
    <script src="{{ mix('assets/js/pages.js') }}"></script>
</head>
@php $styleCss = 'style'; @endphp
<body>
<div class="d-flex flex-column flex-root">
    <div class="d-flex flex-row flex-column-fluid">
        <div class="container">
            <div class="d-flex flex-column flex-lg-row">
                <div class="flex-lg-row-fluid mb-10 mb-lg-0 me-lg-7 me-xl-10">
                    <div class="p-12">
                        @include('flash::message')
                        @include('invoices.show_fields',['isPublicView'=> false])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
