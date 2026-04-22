@extends('backend.app', ['title' => 'Subscription Plans'])

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
                        <h1 class="page-title">Subscription Plans</h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Plans</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Index</li>
                        </ol>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class="card product-sales-main">
                            <div class="card-header border-bottom d-flex justify-content-between">
                                <h3 class="card-title mb-0">Plans</h3>
                                <div>
                                    {{-- <button type="button" class="btn btn-primary ms-3" id="addPlanBtn">
                                        <i class="fa fa-plus me-1"></i> Create New Plan
                                    </button> --}}
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="">
                                    <table class="table text-nowrap mb-0 table-bordered" id="datatable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Price</th>
                                                <th style="width: 40%;">Features</th>
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

    {{-- add/edit plan modal --}}
    <div class="modal fade" id="createPlanModal" tabindex="-1" aria-labelledby="createPlanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="" method="POST" id="createPlanForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createPlanModalLabel">Create New Subscription Plan</h5>
                        <button type="button" class="btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="planID" name="id">

                        {{-- Plan Name --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">Plan Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        {{-- Description --}}
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>

                        {{-- Price --}}
                        <div class="mb-3">
                            <label for="price" class="form-label">Price (in USD)</label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price"
                                required>
                            <small class="text-muted">Enter amount in dollars (will be saved as cents).</small>
                        </div>

                        {{-- Currency --}}
                        <div class="mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <input type="text" class="form-control" id="currency" name="currency" value="usd"
                                maxlength="3" required>
                        </div>

                        {{-- Interval --}}
                        <div class="mb-3">
                            <label for="interval" class="form-label">Interval</label>
                            <select class="form-select" id="interval" name="interval" required>
                                <option value="">Select interval</option>
                                <option value="month">Monthly</option>
                                <option value="year">Yearly</option>
                            </select>
                        </div>

                        {{-- Interval Count --}}
                        <div class="mb-3">
                            <label for="interval_count" class="form-label">Interval Count</label>
                            <input type="number" class="form-control" id="interval_count" name="interval_count"
                                value="1" min="1" required>
                            <small class="text-muted">e.g. 1 = every month/year, 3 = every 3 months</small>
                        </div>

                        {{-- Trial Days --}}
                        <div class="mb-3">
                            <label for="trial_days" class="form-label">Trial Days</label>
                            <input type="number" class="form-control" id="trial_days" name="trial_days" value="0"
                                min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Create Plan</button>
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
                    url: "{{ route('admin.plan.index') }}",
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
                        name: 'name'
                    },
                    {
                        data: 'price',
                        name: 'price'
                    },
                    {
                        data: 'features',
                        name: 'features',
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

            // Toggle features visibility
            $('#datatable').on('click', '.toggle-features', function() {
                let $btn = $(this);
                let $featuresList = $btn.next('.features-list');

                if ($featuresList.is(':visible')) {
                    $featuresList.slideUp();
                    $btn.text('Show Features');
                } else {
                    $featuresList.slideDown();
                    $btn.text('Hide Features');
                }
            });

            // Reset form & open modal for adding
            $('#addPlanBtn').click(function() {
                $('#createPlanModalLabel').text('Create New Subscription Plan');
                $('#createPlanForm')[0].reset();
                $('#planID').val('');
                $('.error-text').text('');
                $('#createPlanModal').modal('show');
            });

            // Handle form submit (Create / Update)
            $('#createPlanForm').on('submit', function(e) {
                e.preventDefault();
                var id = $('#planID').val();
                var url = id ? "{{ route('admin.plan.update', ':id') }}".replace(':id', id) :
                    "{{ route('admin.plan.store') }}";
                var method = id ? 'POST' : 'POST'; // both POST, backend decides

                $.ajax({
                    url: url,
                    type: method,
                    data: new FormData(this),
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                        $('#createPlanForm button[type=submit]').prop('disabled', true).text(
                            'Processing...');
                    },
                    success: function(res) {
                        if (res.status == 0) {
                            $.each(res.error, function(prefix, val) {
                                $('span.' + prefix + '_error').text(val[0]);
                            });
                        } else {
                            $('#createPlanModal').modal('hide');
                            $('#createPlanForm')[0].reset();
                            $('#datatable').DataTable().ajax.reload();
                            toastr.success(res.message);
                        }
                        $('#createPlanForm button[type=submit]').prop('disabled', false).text(
                            'Save changes');
                    },
                    error: function() {
                        $('#createPlanForm button[type=submit]').prop('disabled', false).text(
                            'Save changes');
                        toastr.error('Something went wrong. Please try again.');
                    }
                });
            });

            // Edit Plan
            $(document).on('click', '.editPlan', function() {
                var id = $(this).data('id');
                var url = "{{ route('admin.plan.edit', ':id') }}".replace(':id', id);

                $.get(url, function(res) {
                    $('#createPlanModalLabel').text('Edit Subscription Plan');
                    $('#submitBtn').text('Update Plan');
                    $('#planID').val(res.data.id);
                    $('#name').val(res.data.name);
                    $('#description').val(res.data.description);
                    $('#price').val(res.data.price);
                    $('#currency').val(res.data.currency);
                    $('#price').val(res.data.price);
                    $('#interval').val(res.data.interval);
                    $('#interval_count').val(res.data.interval_count);
                    $('#trial_days').val(res.data.trial_days);
                    $('#createPlanModal').modal('show');
                });
            });
        });
    </script>

    <script>
        // make confirmDelete GLOBAL
        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure you want to delete this plan?',
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
            let url = "{{ route('admin.plan.destroy', ':id') }}".replace(':id', id);
            let csrfToken = '{{ csrf_token() }}';
            $.ajax({
                type: "DELETE",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(resp) {
                    NProgress.done();
                    toastr.success(resp.message);
                    $('#datatable').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    NProgress.done();
                    toastr.error(xhr.responseJSON?.message || 'Delete failed');
                }
            });
        }
    </script>
@endpush
