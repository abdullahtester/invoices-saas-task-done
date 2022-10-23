@php
    $notifications = getNotification();
    $notificationCount = count($notifications);
    $styleCss = 'style';
@endphp
<header class='d-flex align-items-center justify-content-between flex-grow-1 header px-3 px-xl-0'>
    <button type="button" class="btn px-0 aside-menu-container__aside-menubar d-block d-xl-none sidebar-btn">
        <i class="fa-solid fa-bars fs-1"></i>
    </button>
    <nav class="navbar navbar-expand-xl navbar-light top-navbar d-xl-flex d-block px-3 px-xl-0 py-4 py-xl-0 "
         id="nav-header">
        <div class="container-fluid pe-0">
            <div class="navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    @include('layouts.sub_menu')
                </ul>
            </div>
        </div>
    </nav>
    <ul class="nav align-items-center">
        @hasrole('admin')
        <li class="px-xxl-3 px-2">
            <a class="btn btn-primary createInvoiceBtn" href="{{ route('invoices.create') }}">{{__('messages.invoice.new_invoice')}}</a>
        </li>
        @endrole
        <li class="px-xxl-3 px-2">
            @if(Auth::user()->dark_mode)
                <a href="javascript:void(0)" data-turbo="false" title="Switch to Light mode">
                    <i class="fa-solid fa-moon text-primary fs-2 apply-dark-mode"></i>
                </a>
            @else
                <a href="javascript:void(0)" data-turbo="false" title="Switch to Dark mode">
                    <i class="fa-solid fa-sun text-primary fs-2 apply-dark-mode"></i></a>
            @endif
        </li>
        <li class="px-xxl-3 px-2">
            <div class="dropdown custom-dropdown d-flex align-items-center py-4">
                <button class="btn dropdown-toggle hide-arrow p-0 position-relative" type="button" id="dropdownMenuButton1"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-bell text-primary fs-2"></i>
                    @if(count(getNotification()) != 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge badge-circle bg-danger notification-count" id="counter">
                        {{ count(getNotification()) }}
                        <span class="visually-hidden">unread messages</span>
                    </span>
                    @endif
                </button>
                <div class="dropdown-menu py-0 my-2" aria-labelledby="dropdownMenuButton1">
                    <div class="text-start border-bottom py-4 px-7">
                        <h3 class="text-gray-900 mb-0">{{ __('messages.notification.notifications') }}</h3>
                    </div>
                    <div class="px-7 mt-5 inner-scroll height-270">
                        @if($notificationCount > 0)
                            @foreach($notifications as $notification)
                                <a data-turbo="false" href="#" data-id="{{ $notification->id }}"
                                   class="notification text-hover-primary text-decoration-none" id="notification">

                                    <div class="d-flex position-relative mb-5 text-hover-primary">
                                        @php
                                            $datework = Carbon\Carbon::parse($notification->created_at);
                                            $now = Carbon\Carbon::now();
                                            $diff = $datework->diffForHumans($now);
                                        @endphp
                                        <span class="me-5 text-primary fs-2 icon-label"><i class="fa-solid {{ getNotificationIcon($notification->type) }}"></i></span>
                                        <div>
                                            <h5 class="text-gray-900 fs-6 mb-2">{{ $notification->title }}</h5>
                                            <h6 class="text-gray-600 fs-small fw-light mb-0">{{ $diff }}</h6>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        @else
                            <div class="empty-state fs-6 text-gray-800 fw-bold text-center mt-5" data-height="400">
                                <p>{{ __('messages.notification.you_don`t_have_any_new_notification') }}</p>
                            </div>
                        @endif
                        <div class="empty-state fs-6 text-gray-800 fw-bold text-center mt-5 d-none" data-height="400">
                            <p>{{ __('messages.notification.you_don`t_have_any_new_notification') }}</p>
                        </div>
                    </div>
                    @if(count(getNotification()) > 0)
                        <div class="text-center border-top p-4">
                        <a href="#" class="read-all-notification text-primary mb-0 fs-5 text-decoration-none"
                           id="readAllNotification">
                            {{ __('messages.notification.mark_all_as_read') }}</a>
                        </div>
                    @endif
                </div>
            </div>
        </li>
        <li class="px-xxl-3 px-2">
            <div class="dropdown d-flex align-items-center py-4">
                <div class="image image-circle image-mini">
                    <img src="{{ getLogInUser()->profile_image }}"
                         class="img-fluid object-contain" alt="profile image">
                </div>
                <button class="btn dropdown-toggle ps-2 pe-0" type="button" id="dropdownMenuButton1"
                        data-bs-toggle="dropdown" aria-expanded="false"  data-bs-auto-close="outside">
                    {{ getLogInUser()->full_name }}
                </button>
                <div class="dropdown-menu py-7 pb-4 my-2" aria-labelledby="dropdownMenuButton1">
                    <div class="text-center border-bottom pb-5">
                        <div class="image image-circle image-tiny mb-5">
                            <img src="{{ getLogInUser()->profile_image }}" class="img-fluid object-contain" alt="profile image">
                        </div>
                        <h3 class="text-gray-900">{{getLogInUser()->full_name}}</h3>
                        <h4 class="mb-0 fw-400 fs-6">{{getLogInUser()->email}}</h4>
                    </div>
                    <ul class="pt-4">
                        <li>
                            <a class="dropdown-item text-gray-900" href="{{route('profile.setting')}}">
                                <span class="dropdown-icon me-4 text-gray-600">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                                {{ __('messages.user.account_setting') }}
                            </a>
                        </li>
                        @if(getLoggedInUser()->hasRole('admin'))
                            <li>
                                <a class="dropdown-item text-gray-900"
                                   href="{{ route('subscription.pricing.plans.index') }}">
                                    <span class="dropdown-icon me-4 text-gray-600">
                                        <i class="fa-solid fa-money-bill"></i>
                                    </span>
                                    {{__('messages.subscription_plans.subscription_plans')}}
                                </a>
                            </li>
                        @endif
                        @if(!session('impersonated_by'))
                            <li>
                                <a class="dropdown-item text-gray-900" id="changePassword">
                                <span class="dropdown-icon me-4 text-gray-600">
                                    <i class="fa-solid fa-lock"></i>
                                </span>
                                    {{ __('messages.user.change_password') }}
                                </a>
                            </li>
                        @endif
                        @if(session('impersonated_by'))
                            <li>
                                <a class="dropdown-item text-gray-900 position-relative cursor-pointer"
                                   href="{{ route('impersonate.userLogout') }}">
                               <span class="dropdown-icon me-4 text-gray-600">
                                   <i class="fa fa-external-link"></i>
                               </span>
                                    {{ __('messages.back_to_super_admin') }}
                                </a>
                            </li>
                        @endif
                        <li>
                            <a class="dropdown-item text-gray-900 position-relative cursor-pointer" id="changeLanguage" href="javascript:void(0)">
                               <span class="dropdown-icon me-4 text-gray-600">
                                   <i class="fa-solid fa-globe"></i>
                               </span>
                                {{__('messages.user.change_language')}}
                            </a>
                        </li>
                        @if(!session('impersonated_by'))
                        <li>
                            <a class="dropdown-item text-gray-900 d-flex cursor-pointer">
                                <span class="dropdown-icon me-4 text-gray-600">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                </span>
                                <form id="logout-form" action="{{ route('logout')}}" method="post">
                                    @csrf
                                </form>
                                <span onclick="event.preventDefault(); localStorage.clear();  document.getElementById('logout-form').submit();">
                                    {{ __('messages.sign_out') }}</span>
                            </a>

                        </li>
                            @endif
                    </ul>
                </div>
            </div>
        </li>
        <li>
            <button type="button" class="btn px-0 d-block d-xl-none header-btn pb-2">
                <i class="fa-solid fa-bars fs-1"></i>
            </button>
        </li>
    </ul>
</header>
<div class="bg-overlay" id="nav-overly"></div>

