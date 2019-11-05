@extends('layouts.backend')

@section('css_before')
    <!-- Page JS Plugins CSS -->
    <link rel="stylesheet" href="{{asset('js/plugins/select2/css/select2.min.css')}}">
@endsection

@section('content')
    <!-- Hero -->
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">@lang('messages.Add Product')</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">@lang('messages.App')</li>
                        <li class="breadcrumb-item " aria-current="page">@lang('messages.Product')</li>
                        <li class="breadcrumb-item active" aria-current="page">@lang('messages.Add')</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <!-- END Hero -->

    <!-- Page Content -->
    <div class="content">
        <div class="block block-rounded block-bordered">
            <div class="block-content">

                @if ($message = Session::get('success'))
                    <div class="alert alert-success alert-block">
                        <button type="button" class="close" data-dismiss="alert">×</button>
                        <strong>@lang('messages.'.$message)</strong>
                    </div>
                @endif

                @if ($message = Session::get('fail'))
                    <div class="alert alert-danger alert-block">
                        <button type="button" class="close" data-dismiss="alert">×</button>
                        <strong>@lang('messages.'.$message)</strong>
                    </div>
                @endif

                @if (count($errors) > 0)
                    <div class="alert alert-danger">
                        @lang('messages.Whoops! There were some problems with your input.')
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>@lang('messages.'.$error)</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{url('/products/add')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <!-- Vital Info -->
                    <h2 class="content-heading pt-0">@lang('messages.Info')</h2>
                    <div class="row push">
                        <div class="col-lg-4">
                            <p class="text-muted">
                                @lang('messages.Information')
                            </p>
                        </div>
                        <div class="col-lg-8 col-xl-5">
                            <div class="form-group">
                                <label for="dm-project-new-name">
                                    @lang('messages.Customer')
                                </label>
                                <!-- <select class="js-select2 form-control" name="customer-id" style="width: 100%;" data-placeholder="Choose one.." disabled> -->
                                    @foreach($customer_array as $customer)
                                        <!-- <option value="{{$customer->id}}" @if($customer->id == $selected_customer_id) selected @endif>
                                            {{$customer->email}}
                                        </option> -->
                                        <?php $selected_customer = $customer->email;?>
                                    @endforeach
                                <!-- </select> -->
                                <input type="text" class="form-control" name="customer-id" value="{{$selected_customer}}" disabled="">
                                <input type="hidden" name="customer-id" value="{{$selected_customer_id}}">
                            </div>
                            <div class="form-group">
                                <label for="dm-project-new-name">
                                    @lang('messages.Domain') <span class="text-danger">*</span>
                                </label>
                                <select class="js-select2 form-control" name="domain-id" style="width: 100%;" data-placeholder="Choose one..">
                                    @foreach($domain_array as $domain)
                                        <option value="{{$domain->id}}">
                                            {{$domain->domain}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="dm-project-new-name">
                                    @lang('messages.Product name') <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="product-name" placeholder="@lang('messages.Product name')">
                            </div>
                            <div class="form-group">
                                <label for="dm-project-new-name">
                                    @lang('messages.Allowed users') <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="allowed-users" placeholder="@lang('messages.Allowed users')">
                            </div>
                        </div>
                    </div>
                    <!-- END Vital Info -->

                    <!-- Submit -->
                    <div class="row push">
                        <div class="col-lg-8 col-xl-5 offset-lg-4">
                            <div class="form-group">
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-check-circle mr-1"></i> @lang('messages.Save')
                                </button>
                                <a class="btn btn-warning" href="{{url('/products/'.$selected_customer_id)}}">
                                    <i class="fa fa-times-circle mr-1"></i> @lang('messages.Cancel')
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- END Submit -->
                </form>
            </div>
        </div>
    </div>
    <!-- END Page Content -->
@endsection

@section('js_after')
    <script src="{{asset('js/plugins/select2/js/select2.full.min.js')}}"></script>

    <!-- Page JS Helpers (Select2 plugin) -->
    <script>jQuery(function(){ Dashmix.helpers('select2'); });</script>
@endsection
