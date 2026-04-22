@extends('backend.app', ['title' => 'Create Quiz Question'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Quiz Questions</h1>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body border-0">
                        <form id="quizForm">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Question:</label>
                                <input type="text" class="form-control" name="question_text" placeholder="Question">
                            </div>

                            <div class="mb-3">
                                <label>Answer</label>
                                <div id="options-container">
                                    <div class="d-flex mb-2">
                                        <input type="text" class="form-control me-2" name="options[]"
                                            placeholder="Add option for this question">
                                        <button type="button" class="btn btn-danger btn-sm remove-option">X</button>
                                    </div>
                                </div>
                                <button type="button" id="addOption" class="btn btn-success btn-sm">+ Add Option</button>
                            </div>

                            <!-- Submit button -->
                            <div class="text-end">
                                <a href="{{ route('admin.quiz.index') }}" class="btn btn-outline-warning">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Question</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let optionCount = 0;

            // Check max question limit
            $.ajax({
                url: "{{ route('admin.quiz.index') }}?max_check=1",
                type: "GET",
                dataType: "json",
                success: function(resp) {
                    if (resp.maxReached) {
                        $("#quizForm button[type='submit']").prop('disabled', true);
                        toastr.warning('Maximum number of quiz questions reached.');
                    }
                }
            });

            // Add option input
            $('#addOption').click(function() {
                optionCount++;
                $('#options-container').append(`
                <div class="d-flex mb-2">
                    <input type="text" class="form-control me-2" name="options[]" placeholder="Option ${optionCount}">
                    <button type="button" class="btn btn-danger btn-sm remove-option">X</button>
                </div>
            `);
            });

            // Remove option
            $(document).on('click', '.remove-option', function() {
                $(this).closest('div.d-flex').remove();
            });

            // Handle AJAX form submit
            $('#quizForm').on('submit', function(e) {
                e.preventDefault();
                NProgress.start();

                let formData = new FormData(this);
                $.ajax({
                    type: "POST",
                    url: "{{ route('admin.quiz.store') }}",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(resp) {
                        NProgress.done();
                        toastr.success(resp.message);
                        $('#datatable').DataTable().ajax.reload();
                        $('#quizForm')[0].reset();
                        $('#optionsContainer').empty();
                        optionIndex = 0;
                        setTimeout(function() {
                            window.location.href = "{{ route('admin.quiz.index') }}";
                        }, 1000);
                    },
                    error: function(xhr) {
                        NProgress.done();
                        toastr.error(xhr.responseJSON?.message || "Something went wrong");
                    }
                });
            });
        });
    </script>
@endpush
