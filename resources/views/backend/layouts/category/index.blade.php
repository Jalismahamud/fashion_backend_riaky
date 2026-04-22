@extends('backend.app', ['title' => 'Categories'])

@push('styles')
    <link href="{{ asset('default/datatable.css') }}" rel="stylesheet" />
@endpush

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Categories</h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Category</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Index</li>
                        </ol>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class="card product-sales-main">
                            <div class="card-header border-bottom">
                                <h3 class="card-title mb-0">Category List </h3>
                                <div class="card-options ms-auto">
                                    <a href="#" class="btn btn-primary btn-sm" onclick="showCreateModal()">Add Category</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="">
                                    <table class="table text-nowrap mb-0 table-bordered" id="datatable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Action</th>
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

    <!-- Create Modal -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="createForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="create_name" class="form-label">Name:</label>
                            <input type="text" class="form-control" id="create_name" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">Submit</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editForm">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="edit_name" class="form-label">Name:</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">Update</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
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
        if (!$.fn.DataTable.isDataTable('#datatable')) {
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
                scroller: { loadingIndicator: false },
                pagingType: "full_numbers",
                dom: "<'row justify-content-between table-topbar'<'col-md-4 col-sm-3'l><'col-md-5 col-sm-5 px-0'f>>tipr",
                ajax: {
                    url: "{{ route('admin.category.index') }}",
                    type: "GET",
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'name', name: 'name', orderable: true, searchable: true },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'dt-center text-center' },
                ],
            });
        }

        // Create
        $('#createForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: "{{ route('admin.category.store') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function(resp) {
                    $('#createModal').modal('hide');
                    $('#datatable').DataTable().ajax.reload();
                    toastr.success(resp.message);
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON.message);
                }
            });
        });

        // Edit
        $('#editForm').on('submit', function(e) {
            e.preventDefault();
            let id = $('#edit_id').val();
            $.ajax({
                url: "{{ url('admin/category/update') }}/" + id,
                type: "PUT",
                data: $(this).serialize(),
                success: function(resp) {
                    $('#editModal').modal('hide');
                    $('#datatable').DataTable().ajax.reload();
                    toastr.success(resp.message);
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON.message);
                }
            });
        });
    });

    function showCreateModal() {
        $('#createForm')[0].reset();
        $('#createModal').modal('show');
    }

    function showEditModal(id, name) {
        $('#edit_id').val(id);
        $('#edit_name').val(name);
        $('#editModal').modal('show');
    }

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

    function deleteItem(id) {
        NProgress.start();
        let url = "{{ url('admin/category/destroy') }}/" + id;
        let csrfToken = '{{ csrf_token() }}';
        $.ajax({
            type: "DELETE",
            url: url,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(resp) {
                NProgress.done();
                toastr.success(resp.message);
                $('#datatable').DataTable().ajax.reload();
            },
            error: function(xhr) {
                NProgress.done();
                toastr.error(xhr.responseJSON.message);
            }
        });
    }
</script>
@endpush
