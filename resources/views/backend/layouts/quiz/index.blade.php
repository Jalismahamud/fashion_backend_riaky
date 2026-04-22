@extends('backend.app', ['title' => 'Style Quiz Questions'])

@push('styles')
    <link href="{{ asset('default/datatable.css') }}" rel="stylesheet" />
@endpush

@section('content')
    <!--app-content open-->
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Style Quiz Questions</h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Quiz</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Index</li>
                        </ol>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class="card product-sales-main">
                            <div class="card-header border-bottom">
                                <h3 class="card-title mb-0">Quiz Questions</h3>
                                <div class="card-options ms-auto">
                                    <a href="{{ route('admin.quiz.create') }}" class="btn btn-primary btn-sm">Add
                                        Question</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="">
                                    <table class="table text-nowrap mb-0 table-bordered" id="datatable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th style="width: 50%;">Question</th>
                                                <th style="width: 40%">Options</th>
                                                <th style="width: 10%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                }
            });

            let dTable = $('#datatable').DataTable({
                order: [],
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                processing: true,
                responsive: true,
                serverSide: true,
                language: {
                    processing: `<div class="text-center">
                <img src="{{ asset('default/loader.gif') }}" alt="Loader" style="width: 50px;">
            </div>`
                },
                pagingType: "full_numbers",
                dom: "<'row justify-content-between table-topbar'<'col-md-4 col-sm-3'l><'col-md-5 col-sm-5 px-0'f>>tipr",
                ajax: {
                    url: "{{ route('admin.quiz.index') }}",
                    type: "GET",
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'question_text',
                        name: 'question_text',
                        className: 'text-wrap '
                    },
                    {
                        data: 'options',
                        name: 'options',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'dt-center text-center'
                    },
                ],
            });

            // Toggle options visibility
            $('#datatable').on('click', '.toggle-options', function() {
                let $btn = $(this);
                let $optionsList = $btn.next('.options-list');

                if ($optionsList.is(':visible')) {
                    $optionsList.slideUp();
                    $btn.text('Show Options');
                } else {
                    $optionsList.slideDown();
                    $btn.text('Hide Options');
                }
            });
        });


        /*
         * Show delete confirmation dialog
         */
        function showDeleteConfirm(id) {
            event.preventDefault();
            Swal.fire({
                title: 'Are you sure you want to delete this record?',
                text: 'If you delete this, it will be gone forever.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteItem(id);
                }
            });
        }

        /*
         * Delete item via AJAX
         */
        function deleteItem(id) {
            NProgress.start();
            let url = "{{ route('admin.quiz.destroy', ':id') }}";
            let csrfToken = '{{ csrf_token() }}';
            $.ajax({
                type: "DELETE",
                url: url.replace(':id', id),
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(resp) {
                    NProgress.done();
                    toastr.success(resp.message);
                    $('#datatable').DataTable().ajax.reload();
                },
                error: function(error) {
                    NProgress.done();
                    toastr.error(error.message);
                }
            });
        }


    </script>
@endpush
