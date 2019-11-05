@extends('layouts.backend')

@section('css_before')
    <!-- Page JS Plugins CSS -->
    <link rel="stylesheet" href="{{asset('js/plugins/datatables/dataTables.bootstrap4.css')}}">
    <link rel="stylesheet" href="{{asset('js/plugins/datatables/buttons-bs4/buttons.bootstrap4.min.css')}}">
@endsection

@section('content')
    <!-- Hero -->
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">@lang('messages.Blacklist')</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">@lang('messages.App')</li>
                        <li class="breadcrumb-item active" aria-current="page">@lang('messages.Blacklist')</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <!-- END Hero -->

    <!-- Page Content -->
    <div class="content">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default d-flex justify-content-between">
                <h3 class="block-title">@lang('messages.Blacklist')</h3>
                <a class="" href="#" data-toggle="modal" data-target="#help-modal">
                    <i class="si si-question"></i></a>
            </div>
            <div class="block-content block-content-full">
                <div style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                    <a class="btn btn-primary" href="{{url('/blacklist/add')}}">
                        <i class="si si-plus"></i> @lang('messages.Add entry')</a>
                </div>
                <table class="table table-bordered table-striped table-vcenter js-dataTable-full-pagination">
                    <thead>
                    <tr>
                        <th class="text-center" style="width: 80px;">#</th>
                        <th class="d-none d-sm-table-cell" style="">@lang('messages.From')</th>
                        <th class="d-none d-sm-table-cell" style="">@lang('messages.To')</th>
                        <th class="d-none d-sm-table-cell" style="width: 150px;">@lang('messages.Enable')</th>
                        <th class="d-none d-sm-table-cell" style="width: 150px;">@lang('messages.Action')</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($blacklist_array as $one)
                        <tr>
                            <td class="text-center">{{$loop->iteration}}</td>
                            <td class="d-none d-sm-table-cell">
                                {{$one->from}}
                            </td>
                            <td class="d-none d-sm-table-cell">
                                {{$one->rcpt}}
                            </td>
                            <td class="d-none d-sm-table-cell text-center">
                                <div class="custom-control custom-switch custom-control custom-control-inline mb-2"
                                     align="center">
                                    <input type="checkbox" class="custom-control-input"
                                           id="enable-toggle-{{$one->id}}" name="enable-toggle-{{$one->id}}"
                                           @if($one->is_enabled == 1) checked @endif>
                                    <label class="custom-control-label" for="enable-toggle-{{$one->id}}"></label>
                                </div>
                            </td>
                            <td class="d-none d-sm-table-cell text-center">
                                <div class="btn-group">
                                    <a href="{{url('/blacklist/edit').'/'.$one->id}}"
                                       class="btn btn-sm btn-primary" data-toggle="tooltip" title="@lang('messages.Edit')">
                                        <i class="fa fa-pencil-alt"></i>
                                    </a>
                                    <a href="javascript:deleteWL({{$one->id}})" class="btn btn-sm btn-primary"
                                       data-toggle="tooltip" title="@lang('messages.Delete')">
                                        <i class="fa fa-times"></i>
                                    </a>
                                </div>
                            </td>
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
                        <h3 class="block-title">@lang('messages.Blacklist_modal_title')</h3>
                        <div class="block-options">
                            <button type="button" class="btn-block-option" data-dismiss="modal" aria-label="Close">
                                <i class="fa fa-fw fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="block-content">
                        @lang('messages.Blacklist_modal_body')
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
    <script src="{{asset('js/pages/be_tables_datatables.min.js')}}"></script>

    <script>
        function deleteWL(id) {
            if (confirm("Do you want delete this blacklist?")) {
                $.ajax({
                    url: '{{url('/blacklist/delete')}}',
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
            $("[name^='enable-toggle-']").on('change', function () {
                var id = this.name.split("enable-toggle-")[1];
                $.ajax({
                    url: '{{url('/blacklist/toggle-enable')}}',
                    type: "POST",
                    data: {
                        "id": id,
                    },
                    error: function () {
                    },
                    success: function (data) {
                        if (data.message.length == 0) {
                            //window.location.reload();
                        }
                    }
                });
            });
        });
    </script>
@endsection
