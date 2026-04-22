@extends('backend.app', ['title' => 'General Settings'])

@section('content')
    <!--app-content open-->
    <div class="app-content main-content mt-0">
        <div class="side-app">

            <!-- CONTAINER -->
            <div class="main-container container-fluid">

                {{-- PAGE-HEADER --}}
                <div class="page-header">
                    <div>
                        <h1 class="page-title">General Settings</h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Settings</a></li>
                            <li class="breadcrumb-item active" aria-current="page">General Settings</li>
                        </ol>
                    </div>
                </div>
                {{-- PAGE-HEADER --}}


                <div class="row">
                    <div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
                        <div class="card box-shadow-0">
                            <div class="card-body">
                                <form class="form-horizontal" method="post" action="{{ route('setting.general.update') }}"
                                    enctype="multipart/form-data">
                                    @csrf
                                    @method('PATCH')

                                    <div class="row mb-4">

                                        <div class="form-group">
                                            <label for="username" class="form-label">Name:</label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                name="name" placeholder="Name" id="username"
                                                value="{{ $setting->name ?? (old('name') ?? '') }}">
                                            @error('name')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="title" class="form-label">Title:</label>
                                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                                name="title" placeholder="Title" id="title"
                                                value="{{ $setting->title ?? (old('title') ?? '') }}">
                                            @error('title')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        {{-- <div class="form-group">
                                        <label for="description" class="form-label">Description:</label>
                                        <textarea class="description form-control @error('description') is-invalid @enderror"
                                            name="description" placeholder="Description" id="description">{{ $setting->description ?? old('description') ?? '' }}</textarea>
                                        @error('description')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div> --}}

                                        <div class="form-group">
                                            <label for="keywords" class="form-label">Keywords:</label>
                                            <textarea class="description form-control @error('keywords') is-invalid @enderror" name="keywords"
                                                placeholder="Keywords" id="keywords">{{ $setting->keywords ?? (old('keywords') ?? '') }}</textarea>
                                            @error('keywords')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="author" class="form-label">Author:</label>
                                            <input type="text" class="form-control @error('author') is-invalid @enderror"
                                                name="author" placeholder="Author" id="author"
                                                value="{{ $setting->author ?? (old('author') ?? '') }}">
                                            @error('author')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>



                                        <div class="form-group">
                                            <label for="email" class="form-label">Email:</label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                                name="email" placeholder="Email" id="email"
                                                value="{{ $setting->email ?? (old('email') ?? '') }}">
                                            @error('email')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        {{-- <div class="form-group">
                                        <label for="address" class="form-label">Address:</label>
                                        <input type="text" class="form-control @error('address') is-invalid @enderror"
                                            name="address" placeholder="Address" id="address"
                                            value="{{ $setting->address ?? old('address') ?? '' }}">
                                        @error('address')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div> --}}

                                        {{-- <div class="form-group">
                                        <label for="copyright" class="form-label">Copyright:</label>
                                        <input type="text" class="form-control @error('copyright') is-invalid @enderror"
                                            name="copyright" placeholder="Copyright" id="copyright"
                                            value="{{ $setting->copyright ?? old('copyright') ?? '' }}">
                                        @error('copyright')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div> --}}

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="logo" class="form-label">Logo:</label>
                                                    <input type="file"
                                                        class="dropify form-control @error('logo') is-invalid @enderror"
                                                        data-default-file="{{ !empty($setting->logo) && file_exists(public_path($setting->logo)) ? asset($setting->logo) : asset('default/logo.png') }}"
                                                        name="logo" id="logo">
                                                    @error('logo')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="favicon" class="form-label">Favicon:</label>
                                                    <input type="file"
                                                        class="dropify form-control @error('favicon') is-invalid @enderror"
                                                        data-default-file="{{ !empty($setting->favicon) && file_exists(public_path($setting->favicon)) ? asset($setting->favicon) : asset('default/logo.png') }}"
                                                        name="favicon" id="favicon">
                                                    @error('favicon')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">

                                            <div class="card mt-4">
                                                <div class="card-header">
                                                    <h5 class="card-title">Select Location</h5>
                                                </div>
                                                <div class="card-body">

                                                    {{-- Lat & Lng Fields --}}
                                                    <div class="row mt-3">
                                                        <div class="col-md-6">
                                                            <label for="latitude">Latitude:</label>
                                                            <input type="text" class="form-control" name="latitude"
                                                                id="latitude"
                                                                value="{{ $setting->latitude ?? old('latitude') }}">
                                                        </div>
                                                        <div class="col-md-6 mb-5">
                                                            <label for="longitude">Longitude:</label>
                                                            <input type="text" class="form-control" name="longitude"
                                                                id="longitude"
                                                                value="{{ $setting->longitude ?? old('longitude') }}">
                                                        </div>
                                                    </div>

                                                    {{-- Map Container --}}
                                                    <div id="map" style="height: 400px; width: 100%;"></div>
                                                </div>
                                            </div>


                                        </div>

                                        <div class="form-group">
                                            <button class="btn btn-primary" type="submit">Update</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- CONTAINER CLOSED -->
@endsection

@push('scripts')
    {{-- Leaflet CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />

    {{-- Leaflet JS --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

    <script>
        let map, marker, geocoder;

        function initializeMap() {
            const lat = parseFloat(document.getElementById("latitude").value) || 23.8103;
            const lng = parseFloat(document.getElementById("longitude").value) || 90.4125;
            const defaultLocation = [lat, lng];

            // Initialize map
            map = L.map('map').setView(defaultLocation, 13);

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // Add draggable marker
            marker = L.marker(defaultLocation, {
                draggable: true
            }).addTo(map);

            // Update inputs initially
            document.getElementById("latitude").value = lat;
            document.getElementById("longitude").value = lng;

            // Initialize geocoder
            geocoder = L.Control.Geocoder.nominatim({
                geoCodingQueryParams: {
                    'accept-language': 'en'
                }
            });

            L.Control.geocoder({
                defaultMarkGeocode: false,
                geocoder: geocoder,
                position: 'topright',
                placeholder: 'Search location...'
            }).on('markgeocode', function(e) {
                const {
                    center
                } = e.geocode;
                updateLocation(center.lat, center.lng);
            }).addTo(map);

            // Marker drag event
            marker.on('dragend', function() {
                const pos = marker.getLatLng();
                updateLocation(pos.lat, pos.lng);
            });

            // Map click event
            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                updateLocation(e.latlng.lat, e.latlng.lng);
            });

            // Search button click
            document.getElementById("search-button").addEventListener("click", function() {
                const query = document.getElementById("search-box").value;
                if (query) {
                    geocoder.geocode(query, function(results) {
                        if (results && results.length > 0) {
                            const {
                                center
                            } = results[0];
                            updateLocation(center.lat, center.lng);
                        } else {
                            alert('Location not found');
                        }
                    });
                }
            });
        }

        // Function to update inputs & marker
        function updateLocation(lat, lng) {
            document.getElementById("latitude").value = lat;
            document.getElementById("longitude").value = lng;
            marker.setLatLng([lat, lng]);
            map.setView([lat, lng], 13);
        }

        // Initialize map on page load
        document.addEventListener("DOMContentLoaded", initializeMap);
    </script>
@endpush
