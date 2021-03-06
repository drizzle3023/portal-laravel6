@extends('layouts.backend')

@section('css_before')
    <!-- Page JS Plugins CSS -->
    <link rel="stylesheet" href="{{asset('js/plugins/select2/css/select2.min.css')}}">
    <link rel="stylesheet" href="{{asset('js/plugins/datatables/dataTables.bootstrap4.css')}}">
    <link rel="stylesheet" href="{{asset('js/plugins/datatables/buttons-bs4/buttons.bootstrap4.min.css')}}">
@endsection

@section('content')
    <!-- Hero -->
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">@lang('messages.Products')</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">@lang('messages.App')</li>
                        <li class="breadcrumb-item active" aria-current="page">@lang('messages.Products')</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <!-- END Hero -->

    <!-- Page Content -->
    <div class="content">

        @if(Session::get('user-type')!=3)
            <div class="row" style="margin-bottom: 10px;">
                <div class="col-md-6" style="display: flex;">
                    <div style="display: flex; align-items: center; margin-right: 20px;">
                        <span>Customer:</span>
                    </div>
                    <select class="js-select2 form-control" id="sel-customer" name="client-id" style="width: 100%;" data-placeholder="Choose one..">
                        @foreach($customer_array as $customer)
                            <option value="{{$customer->id}}" @if($customer->id == $selected_customer_id) selected @endif>
                                {{$customer->email}}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default d-flex justify-content-between">
                <h3 class="block-title">Product List</h3>
                <a class="" href="#" data-toggle="modal" data-target="#help-modal">
                    <i class="si si-question"></i></a>
            </div>
            <div class="block-content block-content-full">

                @if(Session::get('user-type')!=3)
                    <div style="margin-bottom: 10px;">
                        <a class="btn btn-primary" href="{{url('/products/').'/'.$selected_customer_id.'/add'}}"><i
                                class="si si-plus"></i> @lang('messages.Add Product')</a>
                    </div>
                @endif

                <table class="table table-bordered table-striped table-vcenter js-dataTable-full-pagination">
                    <thead>
                    <tr>
                        <th class="text-center" style="width: 80px;">#</th>
                        <th class="d-none d-sm-table-cell" style="width: 20%;">@lang('messages.Name')</th>
                        @if(Session::get('user-type')!==3)
                            <th class="d-none d-sm-table-cell" style="width: 300px;">@lang('messages.Customer')</th>
                        @endif
                        <th class="d-none d-sm-table-cell" style="width: 20%;">@lang('messages.Domain')</th>
                        <th class="d-none d-sm-table-cell" style="width: 180px;">@lang('messages.Allowed user')</th>
                        <th class="d-none d-sm-table-cell" style="width: 180px;">@lang('messages.Currently Used')</th>
                        <th class="d-none d-sm-table-cell" style="width: 180px;">@lang('messages.Free')</th>
                        @if(Session::get('user-type')!==3)
                            <th class="d-none d-sm-table-cell" style="width: 120px;">@lang('messages.Action')</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($product_array as $product)
                        <tr>
                            <td class="text-center">{{$loop->iteration}}</td>
                            <td class="d-none d-sm-table-cell">
                                {{$product->name}}
                            </td>
                            @if(Session::get('user-type')===2)
                                <td class="d-none d-sm-table-cell">
                                    {{$product->customer->email}}
                                </td>
                            @endif
                            <td class="d-none d-sm-table-cell">
                                {{$product->domain->domain}}
                            </td>
                            <td class="d-none d-sm-table-cell">
                                {{$product->alloweduser}}
                            </td>
                            <td class="d-none d-sm-table-cell">
                                @if($product->used > $product->alloweduser)
                                    <span class="badge badge-danger">{{$product->used}}</span>
                                @else
                                    <span class="badge badge-success">{{$product->used}}</span>
                                @endif

                            </td>
                            <td class="d-none d-sm-table-cell">
                                {{$product->free}}
                            </td>
                            @if(Session::get('user-type')!==3)
                                <td class="d-none d-sm-table-cell text-center">
                                    <div class="btn-group">
                                        <a href="{{url('/products/edit').'/'.$product->id}}"
                                           class="btn btn-sm btn-primary" data-toggle="tooltip" title="@lang('messages.Edit')">
                                            <i class="fa fa-pencil-alt"></i>
                                        </a>
                                        <a href="javascript:deleteWL({{$product->id}})" class="btn btn-sm btn-primary"
                                           data-toggle="tooltip" title="@lang('messages.Delete')">
                                            <i class="fa fa-times"></i>
                                        </a>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="help-modal" tabindex="-1" role="dialog" aria-labelledby="modal-block-fadein" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="block block-themed block-transparent mb-0" >
                    <div class="block-header bg-primary-dark">
                        <h3 class="block-title">@lang('messages.Products_modal_title')</h3>
                        <div class="block-options">
                            <button type="button" class="btn-block-option" data-dismiss="modal" aria-label="Close">
                                <i class="fa fa-fw fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="block-content">
                        @lang('messages.Products_modal_body')
                    </div>
                    <div class="block-content block-content-full text-right bg-light">
                        <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- END Page Content -->
@endsection

@section('js_after')
    <!-- Page JS Plugins -->
    <script src="{{asset('js/plugins/datatables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('js/plugins/datatables/dataTables.bootstrap4.min.js')}}"></script>

    <!-- Page JS Code -->
    <script src="{{asset('js/plugins/select2/js/select2.full.min.js')}}"></script>
    <script src="{{asset('js/pages/be_tables_datatables.min.js')}}"></script>

    <!-- Page JS Helpers (Select2 plugin) -->
    <script>jQuery(function(){ Dashmix.helpers('select2'); });</script>

    <script>
        function deleteWL(id) {
            if (confirm("Do you want delete this product?")) {
                $.ajax({
                    url: '{{url('/products/delete')}}',
                    type: "POST",
                    data: {
                        "id": id,
                    },
                    error: function () {
                    },
                    success: function (data) {
                        if (data.message.length == 0) {
                            window.location.reload();
                        }
                    }
                });
            }
        }
        $(document).ready(function () {
            $("#sel-customer").on("change", () => {
                window.location.href = $("#sel-customer").val();
            });
        });

    </script>
@endsection
