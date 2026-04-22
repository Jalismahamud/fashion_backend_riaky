<?php

namespace App\Http\Controllers\Web\Backend;

use Carbon\Carbon;
use App\Models\User;
use App\Models\ApiHit;
use App\Models\Review;
use App\Models\ContactUs;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StyleQuizQuestion;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class DashboardController extends Controller
{
    public function index()
    {

        try {
            $totalUsers     = User::where('role', 'user')->count();
            $totalQuestions = StyleQuizQuestion::count();
            $totalReviews   = Review::where('status', 'pending')->count();
            $totalHits      = ApiHit::count();

            $days       = 7;
            $dates      = [];
            $hitCounts  = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date       = Carbon::today()->subDays($i)->format('Y-m-d');
                $dates[]    = $date;
                $hitCounts[] = ApiHit::whereDate('created_at', $date)->count();
            }

            $apiKey = '5c2d6ea61332c16efdb958fb992d8bab';
            $weatherData = null;

            try {

                $setting = \App\Models\Setting::first();
                $lat = $setting->latitude ?? 23.8103;
                $lon = $setting->longitude ?? 90.4125;

                $response = \Illuminate\Support\Facades\Http::get('https://api.openweathermap.org/data/2.5/weather', [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $apiKey,
                    'units' => 'metric',
                ]);

                if ($response->successful()) {
                    $weatherData = $response->json();
                }
            } catch (\Exception $e) {
                $weatherData = null;
            }

            return view(
                'backend.layouts.dashboard',
                [
                    'totalUsers'     => $totalUsers,
                    'totalQuestions' => $totalQuestions,
                    'totalReviews'   => $totalReviews,
                    'dates'          => $dates,
                    'hitChartData'   => $hitCounts,
                    'totalHits'      => $totalHits,
                    'weatherData'    => $weatherData,
                ]
            );
        } catch (\Exception $e) {

            Log::info($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    //show contact
    public function showContact(Request $request)
    {
        if ($request->ajax()) {
            $data = ContactUs::latest('id')->get();

            return DataTables::of($data)
                ->addIndexColumn()

                // Full Name Column
                ->addColumn('full_name', function ($row) {
                    return $row->full_name ?? '-';
                })

                // Email Column
                ->addColumn('email', function ($row) {
                    return $row->email ?? '-';
                })

                // Phone Column
                ->addColumn('phone', function ($row) {
                    return $row->phone ?? '-';
                })

                // Subject Column
                ->addColumn('subject', function ($row) {
                    return $row->subject ?? '-';
                })

                // Message Column
                ->addColumn('message', function ($row) {
                    return Str::limit($row->message, 50);
                })

                ->rawColumns([])
                ->make(true);
        }

        return view('backend.layouts.contact.index');
    }
}
