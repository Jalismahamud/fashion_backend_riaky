@extends('backend.app' , ['title' => 'Review Details'])

@section('content')

<div class="container py-4">

    {{-- Back Button --}}
    <div class="mb-3">
        <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
            ← Back
        </a>
    </div>

    {{-- Review Card --}}
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-body p-4">

            {{-- User Info --}}
            <div class="d-flex align-items-center mb-3">
                <img src="{{ asset($review->user->avatar ?? 'images/default-avatar.png') }}"
                     alt="User Avatar"
                     class="rounded-circle me-3"
                     style="width:70px; height:70px; object-fit:cover;">

                <div>
                    <h5 class="mb-0">{{ $review->user->name ?? 'Unknown User' }}</h5>
                    <small class="text-muted">{{ $review->user->email ?? 'No Email' }}</small>
                </div>
            </div>

            <hr>

            {{-- Review Text --}}
            <div class="mb-3">
                <h6 class="fw-bold">Review:</h6>
                <p class="text-secondary">{{ $review->review_text ?? 'No review provided.' }}</p>
            </div>

            {{-- Rating --}}
            <div class="mb-3">
                <h6 class="fw-bold">Rating:</h6>
                @if(!empty($review->rating))
                    <div class="text-warning fs-5">
                        @for ($i = 1; $i <= 5; $i++)
                            @if($i <= $review->rating)
                                ★
                            @else
                                ☆
                            @endif
                        @endfor
                        <span class="ms-2 text-dark">({{ $review->rating }}/5)</span>
                    </div>
                @else
                    <span class="text-muted">No rating given.</span>
                @endif
            </div>

        </div>
    </div>
</div>

@endsection
