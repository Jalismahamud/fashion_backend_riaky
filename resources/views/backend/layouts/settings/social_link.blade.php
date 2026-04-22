@extends('backend.app', ['title' => 'Social link'])

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
                        <h1 class="page-title">Social Profile</h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Link</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Index</li>
                        </ol>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class="card product-sales-main">
                            <div class="card-header border-bottom">
                                <h3 class="card-title mb-0">Link</h3>
                                <div class="card-options ms-auto">
                                    @if (\App\Models\SocialLink::count() < 4)
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#createItemModal">Add Profile</button>
                                    @else
                                        <button class="btn btn-primary btn-sm" disabled>Add Profile</button>
                                    @endif
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="">
                                    <table class="table text-nowrap mb-0 table-bordered" id="datatable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th style="width: 30%;">Profile Name</th>
                                                <th style="width: 50%">URL</th>
                                                <th style="width: 50%">Icon</th>
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


    <!-- Create Social Link Modal -->
    <div class="modal fade" id="createItemModal" tabindex="-1" aria-labelledby="createItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="createItemForm" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createItemModalLabel">Add Social Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                    </div>
                    <div class="modal-body">
                        <!-- Name -->
                        <div class="form-group mb-2">
                            <label for="createName" class="form-label">Profile Name</label>
                            <input type="text" class="form-control" name="name" id="createName"
                                placeholder="Enter profile name">
                            <span class="text-danger error-text name_error"></span>
                        </div>

                        <!-- URL -->
                        <div class="form-group mb-2">
                            <label for="createURL" class="form-label">Profile URL</label>
                            <input type="url" class="form-control" name="url" id="createURL"
                                placeholder="Enter URL">
                            <span class="text-danger error-text url_error"></span>
                        </div>

                        <!-- Icon -->
                        <div class="form-group mb-2">
                            <label for="createIcon" class="form-label">Icon</label>
                            <input type="file" class="form-control" name="icon" id="createIcon" accept="image/*">
                            <span class="text-danger error-text icon_error"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="createSubmitBtn" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Edit Social Link Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editItemForm" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <input type="hidden" name="id" id="editID">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editItemModalLabel">Edit Social Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Name -->
                        <div class="form-group mb-2">
                            <label for="editName" class="form-label">Profile Name</label>
                            <input type="text" class="form-control" name="name" id="editName">
                            <span class="text-danger error-text name_error"></span>
                        </div>

                        <!-- URL -->
                        <div class="form-group mb-2">
                            <label for="editURL" class="form-label">Profile URL</label>
                            <input type="url" class="form-control" name="url" id="editURL">
                            <span class="text-danger error-text url_error"></span>
                        </div>

                        <!-- Icon -->
                        <div class="form-group mb-2">
                            <label for="editIcon" class="form-label">Icon</label>
                            <input type="file" class="form-control" name="icon" id="editIcon" accept="image/*">
                            <span class="text-danger error-text icon_error"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="editSubmitBtn" class="btn btn-primary">Update</button>
                    </div>
                </form>
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
                    url: "{{ route('social.link.index') }}",
                    type: "GET",
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name',
                        className: 'text-wrap '
                    },
                    {
                        data: 'url',
                        name: 'url',
                        className: 'text-wrap '
                    },
                    {
                        data: 'icon',
                        name: 'icon',
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

            // ==============================
            // CREATE Social Link
            // ==============================
            $('#createItemForm').on('submit', function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                $.ajax({
                    url: "{{ route('social.link.store') }}",
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $('#createSubmitBtn').prop('disabled', true).text('Saving...');
                    },
                    success: function(res) {
                        if (res.success) {
                            $('#createItemModal').modal('hide');
                            $('#createItemForm')[0].reset();
                            dTable.ajax.reload(); // refresh DataTable
                            toastr.success(res.message);
                        } else {
                            toastr.error(res.message);
                        }
                        $('#createSubmitBtn').prop('disabled', false).text('Save');
                    },
                    error: function(err) {
                        if (err.responseJSON && err.responseJSON.errors) {
                            $.each(err.responseJSON.errors, function(key, val) {
                                $('.' + key + '_error').text(val[0]);
                            });
                        } else {
                            toastr.error("Something went wrong!");
                        }
                        $('#createSubmitBtn').prop('disabled', false).text('Save');
                    }
                });
            });

            // ==============================
            // OPEN EDIT Modal
            // ==============================
            $(document).on('click', '.editBtn', function() {
                let id = $(this).data('id');
                let name = $(this).data('name');
                let url = $(this).data('url');

                $('#editID').val(id);
                $('#editName').val(name);
                $('#editURL').val(url);
                $('#editItemModal').modal('show');
            });

            // ==============================
            // UPDATE Social Link
            // ==============================
            $('#editItemForm').on('submit', function(e) {
                e.preventDefault();
                let id = $('#editID').val();
                let formData = new FormData(this);
                $.ajax({
                    url: "{{ route('social.link.update', ':id') }}".replace(':id', id),
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $('#editSubmitBtn').prop('disabled', true).text('Updating...');
                    },
                    success: function(res) {
                        if (res.success) {
                            $('#editItemModal').modal('hide');
                            dTable.ajax.reload(); // refresh DataTable
                            toastr.success(res.message);
                        } else {
                            toastr.error(res.message);
                        }
                        $('#editSubmitBtn').prop('disabled', false).text('Update');
                    },
                    error: function(err) {
                        if (err.responseJSON && err.responseJSON.errors) {
                            $.each(err.responseJSON.errors, function(key, val) {
                                $('.' + key + '_error').text(val[0]);
                            });
                        } else {
                            toastr.error("Something went wrong!");
                        }
                        $('#editSubmitBtn').prop('disabled', false).text('Update');
                    }
                });
            });
        });
    </script>


    <script>
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
            let url = "{{ route('social.link.delete', ':id') }}";
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
