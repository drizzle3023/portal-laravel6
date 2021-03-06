@extends('layouts.simple')

@section('content')

    <!-- Page Content -->
    <div class="bg-image" style="background-image: url('media/photos/photo19@2x.jpg');">
        <div class="row no-gutters bg-gd-sun-op">
            <!-- Main Section -->
            <div class="hero-static col-md-6 d-flex align-items-center bg-white">
                <div class="p-3 w-100">
                    <!-- Header -->
                    <div class="text-center">
                        <a class="text-warning font-w700 font-size-h1" href="index.html">
                            <img src="{{asset('/media/logo.png')}}" style="width: 250px; margin-bottom: 20px;">
                        </a>
                    </div>
                    <!-- END Header -->

                    <!-- Reminder Form -->
                    <!-- jQuery Validation (.js-validation-reminder class is initialized in js/pages/op_auth_reminder.min.js which was auto compiled from _es6/pages/op_auth_reminder.js) -->
                    <!-- For more info and examples you can check out https://github.com/jzaefferer/jquery-validation -->
                    <div class="row no-gutters justify-content-center">
                        <div class="col-sm-8 col-xl-6">

                            @if (isset($message))
                                @if($message == 'success')
                                    <div class="alert alert-success alert-block">
                                        <button type="button" class="close" data-dismiss="alert">×</button>
                                        <strong>@lang('messages.Recovery password was sent your email.') <br>@lang('messages.Please check your inbox.')</strong>
                                    </div>
                                @else
                                    <div class="alert alert-warning alert-block">
                                        <button type="button" class="close" data-dismiss="alert">×</button>
                                        <strong>@lang('messages.Something went wrong.') <br>@lang('messages.Please try again after a while.')</strong>
                                    </div>
                                @endif
                            @endif

                            <form class="js-validation-reminder" action="{{url('/reset-password')}}" method="post">
                                @csrf
                                <div class="form-group py-3">
                                    <input type="text" class="form-control form-control-lg form-control-alt" id="reminder-credential" name="reminder-credential" placeholder="@lang('messages.Email Address')">
                                </div>
                                <div class="form-group text-center">
                                    <button type="submit" class="btn btn-block btn-hero-lg btn-hero-warning">
                                        <i class="fa fa-fw fa-reply mr-1"></i> @lang('messages.Password Reminder')
                                    </button>
                                    <p class="mt-3 mb-0 d-lg-flex justify-content-lg-between">
                                        <a class="btn btn-sm btn-light d-block d-lg-inline-block mb-1" href="{{'/login'}}">
                                            <i class="fa fa-sign-in-alt text-muted mr-1"></i> @lang('messages.Sign In')
                                        </a>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- END Reminder Form -->
                </div>
            </div>
            <!-- END Main Section -->

            <!-- Meta Info Section -->
            <div class="hero-static col-md-6 d-none d-md-flex align-items-md-center justify-content-md-center text-md-center">
                <div class="p-3">
                    <p class="display-4 font-w700 text-white mb-0">

                    </p>
                    <p class="font-size-h1 font-w600 text-white-75 mb-0">

                    </p>
                </div>
            </div>
            <!-- END Meta Info Section -->
        </div>
    </div>
    <!-- END Page Content -->


@endsection


@section('js_after')
    <!-- Page JS Plugins -->
    <script src="{{asset('js/plugins/jquery-validation/jquery.validate.min.js')}}"></script>

    <!-- Page JS Code -->
    <script src="{{asset('js/pages/op_auth_reminder.min.js')}}"></script>
@endsection
