@extends('backend.app', ['title' => 'CMS : News'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                <!-- PAGE-HEADER -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">CMS : News</h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <a href="{{ route('admin.cms.news.create') }}" class="btn btn-primary btn-icon text-white">
                            <i class="fe fe-plus"></i> Create News
                        </a>
                    </div>
                </div>
                <!-- PAGE-HEADER END -->

                <!-- NEWS TABLE -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card shadow-sm rounded-4">
                            <div class="card-body">
                                <table id="newsTable" class="table table-bordered text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Title</th>
                                            <th>Sub Title</th>
                                            <th>Image</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- NEWS TABLE END -->

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $('#newsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.cms.news.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'title',
                        name: 'title',
                        render: function(data, type, row) {
                            if (type === 'display' && data.length > 70) {
                                return data.substring(0, 70) + '...';
                            }
                            return data;
                        }
                    },
                    {
                        data: 'sub_title',
                        name: 'sub_title',
                        render: function(data, type, row) {
                            if (type === 'display' && data.length > 70) {
                                return data.substring(0, 70) + '...';
                            }
                            return data;
                        }
                    },
                    {
                        data: 'image',
                        name: 'image',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ]
            });
        });


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
            let url = "{{ route('admin.cms.news.destroy', ':id') }}".replace(':id', id);
            let csrfToken = '{{ csrf_token() }}';

            $.ajax({
                type: "DELETE",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(resp) {
                    NProgress.done();
                    toastr.success(resp.message || 'Deleted successfully');
                    $('#newsTable').DataTable().ajax.reload(null, false);
                },
                error: function(xhr) {
                    NProgress.done();
                    let errorMsg = xhr.responseJSON?.message || 'An error occurred';
                    toastr.error(errorMsg);
                }
            });
        }
    </script>
@endpush
