@extends('backend.app', ['title' => 'Dashboard'])

@section('content')
    <!--app-content open-->
    <div class="app-content main-content mt-0">
        <div class="side-app">

            <!-- CONTAINER -->
            <div class="main-container container-fluid">

                <!-- PAGE-HEADER -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Dashboard</h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </div>
                </div>
                <!-- PAGE-HEADER END -->

                <!-- ROW-1 -->
                <div class="row">
                    @php
                        use App\Helper\Helper;
                    @endphp

                    {{-- Total User --}}
                    <x-dashboard.card title="Total Users" value="{{ Helper::formatNumberShort($totalUsers) }}"
                        icon="users" color="primary" />
                    <x-dashboard.card title="Total Questions" value="{{ Helper::formatNumberShort($totalQuestions) }}"
                        icon="question" color="success" />
                    <x-dashboard.card title="Total Pending Reviews" value="{{ Helper::formatNumberShort($totalReviews) }}"
                        icon="star" color="warning" />
                    <x-dashboard.card title="Total API Hits" value="{{ Helper::formatNumberShort($totalHits) }}"
                        icon="chart-line" color="warning" />
                </div>

                <div class="row mt-5">
                    <div class="col-xl-12 col-lg-12 col-md-12">
                        <div class="card">
                            <div class="card-header border-bottom-0 pb-0 d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">API Usage Analytics</h4>
                                <p class="tx-12 text-muted mb-0">
                                    Total Hit Counts: {{ Helper::formatNumberShort($totalHits) }}
                                </p>
                            </div>
                            <div class="card-body">
                                <canvas id="apiHitChart" height="500"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Load Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Chart JS --}}
    <script>
        const hitLabels = @json($dates);
        const hitData = @json($hitChartData);

        const hitCtx = document.getElementById('apiHitChart').getContext('2d');
        new Chart(hitCtx, {
            type: 'line',
            data: {
                labels: hitLabels,
                datasets: [{
                    label: 'OpenAI API Hits',
                    data: hitData,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0,123,255,0.2)',
                    pointBackgroundColor: '#007bff',
                    pointBorderColor: '#fff',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f3f3'
                        }
                    },
                    x: {
                        grid: {
                            color: '#f3f3f3'
                        }
                    }
                }
            }
        });
    </script>
@endsection
