@props(['title', 'value', 'icon', 'color'])

<div class="col-lg-6 col-sm-12 col-md-6 col-xl-3">
    <div class="card overflow-hidden">
        <div class="card-body">
            <div class="row">
                <div class="col">
                    <h3 class="mb-2 fw-semibold">{{ $value }}</h3>
                    <p class="text-muted fs-13 mb-0">{{ $title }}</p>
                </div>
                <div class="col col-auto top-icn dash">
                    <div class="counter-icon bg-{{ $color }} dash ms-auto box-shadow-{{ $color }}">
                        <i class="fa fa-{{ $icon }} text-white fs-20 p-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
