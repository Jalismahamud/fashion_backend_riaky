@extends('backend.app', ['title' => 'CMS : Blog Banner'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                <!-- PAGE-HEADER -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">CMS : Blog Banner</h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">CMS</li>
                            <li class="breadcrumb-item">Blog</li>
                            <li class="breadcrumb-item active" aria-current="page">Banner Section</li>
                        </ol>
                    </div>
                </div>
                <!-- PAGE-HEADER END -->

                <!-- CARD -->
                <div class="row">
                    <div class="col-md-12 mx-auto">
                        <div class="card shadow-sm rounded-4">
                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.cms.blog.banner.content') }}" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')

                                    <!-- Title -->
                                    <div class="mb-4">
                                        <label for="title" class="form-label fw-semibold">Title</label>
                                        <input type="text"
                                            class="form-control @error('title') is-invalid @enderror"
                                            name="title"
                                            id="title"
                                            placeholder="Enter blog banner title"
                                            value="{{ old('title', $section->title ?? '') }}">
                                        @error('title')
                                            <span class="invalid-feedback d-block" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <!-- Description -->
                                    <div class="mb-4">
                                        <label for="description" class="form-label fw-semibold">Description</label>
                                        <textarea class="summernote form-control @error('description') is-invalid @enderror"
                                            name="description"
                                            id="description"
                                            rows="4"
                                            placeholder="Enter blog banner description">{{ old('description', $section->description ?? '') }}</textarea>
                                        @error('description')
                                            <span class="invalid-feedback d-block" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <!-- Button Text -->
                                    <div class="mb-4">
                                        <label for="btn_text" class="form-label fw-semibold">Button Text</label>
                                        <input type="text"
                                            class="form-control @error('btn_text') is-invalid @enderror"
                                            name="btn_text"
                                            id="btn_text"
                                            placeholder="Enter button text"
                                            value="{{ old('btn_text', $section->btn_text ?? '') }}">
                                        @error('btn_text')
                                            <span class="invalid-feedback d-block" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>


                                    <!-- Submit Button -->
                                    <div class="text-center mt-4">
                                        <button class="btn btn-primary px-4 py-2 rounded-pill" type="submit">
                                            <i class="fe fe-check-circle me-1"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div> <!-- card-body -->
                        </div> <!-- card -->
                    </div> <!-- col -->
                </div> <!-- row -->
            </div> <!-- main-container -->
        </div> <!-- side-app -->
    </div> <!-- app-content -->
@endsection