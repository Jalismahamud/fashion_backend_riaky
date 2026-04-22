@extends('backend.app', ['title' => 'Edit Quiz Question'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Edit Quiz Question</h1>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body border-0">
                        <form id="quizEditForm">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label">Question:</label>
                                <input type="text" class="form-control" name="question_text"
                                    value="{{ $data->question_text }}">
                            </div>

                            <div class="mb-3">
                                <label>Answer</label>
                                <div id="options-container">
                                    @foreach ($data->options as $option)
                                        <div class="d-flex mb-2">
                                            <input type="text" class="form-control me-2" name="options[]"
                                                value="{{ $option->option_text }}">
                                            <button type="button" class="btn btn-danger btn-sm remove-option">X</button>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" id="addOption" class="btn btn-success btn-sm">+ Add Option</button>
                            </div>

                            <!-- Submit button -->
                            <div class="text-end">
                                <a href="{{ route('admin.quiz.index') }}" class="btn btn-outline-warning">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Question</button>
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
            let optionCount = $('#options-container input').length;

            // Add option
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

            // Submit AJAX
            $('#quizEditForm').on('submit', function(e) {
                e.preventDefault();
                NProgress.start();

                let formData = new FormData(this);
                $.ajax({
                    type: "POST",
                    url: "{{ route('admin.quiz.update', $data->id) }}",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(resp) {
                        NProgress.done();
                        toastr.success(resp.message);
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
