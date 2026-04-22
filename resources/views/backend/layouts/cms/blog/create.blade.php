{{-- filepath: c:\laragon\www\anand_kasi_backend\resources\views\backend\layouts\cms\blog\create.blade.php --}}
@extends('backend.app', ['title' => 'CMS : Create Blog'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                <!-- PAGE-HEADER -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">CMS : Create Blog</h1>
                    </div>
                </div>
                <!-- PAGE-HEADER END -->

                <!-- CREATE BLOG FORM -->
                <div class="row">
                    <div class="col-md-12 mx-auto">
                        <div class="card shadow-sm rounded-4">
                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.cms.blog.store') }}"
                                    enctype="multipart/form-data">
                                    @csrf

                                    <!-- Title -->
                                    <div class="mb-4">
                                        <label for="title" class="form-label fw-semibold">Title</label>
                                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                                            name="title" id="title" placeholder="Enter blog title"
                                            value="{{ old('title') }}">
                                        @error('title')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Sub Title -->
                                    <div class="mb-4">
                                        <label for="sub_title" class="form-label fw-semibold">Sub Title</label>
                                        <input type="text" class="form-control @error('sub_title') is-invalid @enderror"
                                            name="sub_title" id="sub_title" placeholder="Enter blog sub title"
                                            value="{{ old('sub_title') }}">
                                        @error('sub_title')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Description -->
                                    <div class="mb-4">
                                        <label for="description" class="form-label fw-semibold">Description</label>
                                        <textarea class="summernote form-control @error('description') is-invalid @enderror" name="description" id="description"
                                            rows="4" placeholder="Enter blog description">{{ old('description') }}</textarea>
                                        @error('description')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Short Description -->
                                    <div class="mb-4">
                                        <label for="sub_description" class="form-label fw-semibold">Short
                                            Description</label>
                                        <textarea class="summernote form-control @error('sub_description') is-invalid @enderror" name="sub_description"
                                            id="sub_description" rows="2" placeholder="Enter blog short description">{{ old('sub_description') }}</textarea>
                                        @error('sub_description')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Image -->
                                    <div class="mb-4">
                                        <label for="image" class="form-label fw-semibold">Image</label>
                                        <input type="file"
                                            class="form-control dropify @error('image') is-invalid @enderror" name="image"
                                            id="image">
                                        @error('image')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Video -->
                                    <div class="mb-4">
                                        <label for="video" class="form-label fw-semibold">Video</label>
                                        <input type="file"
                                            class="form-control dropify @error('video') is-invalid @enderror" name="video"
                                            id="video">
                                        @error('video')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Submit and Back Buttons -->
                                    <div class="text-center mt-4">
                                        <a href="{{ route('admin.cms.blog.index') }}"
                                            class="btn btn-danger px-4 py-2 rounded-pill">
                                            <i class="fe fe-arrow-left me-1"></i> Back
                                        </a>
                                        <button class="btn btn-primary px-4 py-2 rounded-pill" type="submit">
                                            <i class="fe fe-check-circle me-1"></i> Create Blog
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- CREATE BLOG FORM END -->

            </div>
        </div>
    </div>
@endsection

