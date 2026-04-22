<?php

namespace App\Http\Controllers\Api\Backend;

use App\Models\Item;
use App\Models\ApiHit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Services\OpenAiWeatherSuggestionService;
use Illuminate\Support\Facades\Cache; 

class OpenAiWeatherSuggestionController extends Controller
{
    use ApiResponse;

    protected $weatherSuggestionService;

    public function __construct(OpenAiWeatherSuggestionService $weatherSuggestionService)
    {
        $this->weatherSuggestionService = $weatherSuggestionService;
    }

    public function weatherBasedSuggestion(Request $request)
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return $this->error([], 'Unauthorized', 401);
            }

            $items = Item::where('user_id', $user->id)->get();

            if ($items->isEmpty()) {

                $lat = $user->latitude;
                $lon = $user->longitude;

                $apiKey = '5c2d6ea61332c16efdb958fb992d8bab';
                $weatherApiUrl = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";

                $weatherResponse = Http::timeout(10)->get($weatherApiUrl);
                $weatherData = [];
                if ($weatherResponse->successful()) {
                    $weatherJson = $weatherResponse->json();
                    $weatherData['summary'] = 'Temperature: ' . ($weatherJson['main']['temp'] ?? '-') . '°C, ' . ($weatherJson['weather'][0]['description'] ?? '');
                    $weatherData['raw'] = $weatherJson;
                } else {
                    $weatherData['summary'] = 'Weather data unavailable.';
                }

                $weatherData = [
                    'temperature' => $weatherData['raw']['main']['temp'] ?? null,
                    'description' => $weatherData['raw']['weather'][0]['description'] ?? null,
                    'humidity' => $weatherData['raw']['main']['humidity'] ?? null,
                    'wind_speed' => $weatherData['raw']['wind']['speed'] ?? null,
                ];


                return $this->success(['suggestions' => [], 'weather' => $weatherData], 'Items is empty.', 200);
            }

            $lat = $user->latitude;
            $lon = $user->longitude;

            $apiKey = '5c2d6ea61332c16efdb958fb992d8bab';
            $weatherApiUrl = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";

            $weatherResponse = Http::timeout(10)->get($weatherApiUrl);
            $weatherData = [];
            if ($weatherResponse->successful()) {
                $weatherJson = $weatherResponse->json();
                $weatherData['summary'] = 'Temperature: ' . ($weatherJson['main']['temp'] ?? '-') . '°C, ' . ($weatherJson['weather'][0]['description'] ?? '');
                $weatherData['raw'] = $weatherJson;
            } else {
                $weatherData['summary'] = 'Weather data unavailable.';
            }

            $cacheKey = "weather_suggestion_user_{$user->id}";

            if (Cache::has($cacheKey)) {
                $cachedData = Cache::get($cacheKey);
                return $this->success($cachedData, 'Weather-based suggestions (cached) fetched successfully.', 200);
            }


            DB::beginTransaction();

            $result = $this->weatherSuggestionService->getSuggestions(
                $user->id,
                $items,
                $weatherData
            );

            if (isset($result['error'])) {
                DB::rollBack();
                return $this->error([], $result['error'], 500);
            }

            ApiHit::create([
                'user_id' => $user->id,
                'success' => true,
            ]);

            DB::commit();

            $responseData = [
                'suggestions' => $result['suggestions'],
                'weather' => [
                    'temperature' => $weatherData['raw']['main']['temp'] ?? null,
                    'description' => $weatherData['raw']['weather'][0]['description'] ?? null,
                    'humidity' => $weatherData['raw']['main']['humidity'] ?? null,
                    'wind_speed' => $weatherData['raw']['wind']['speed'] ?? null,
                ]
            ];

            Cache::put($cacheKey, $responseData, now()->addHour());

            return $this->success($responseData, 'Weather-based suggestions generated successfully.', 200);
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error('Weather Suggestion Error: ' . $e->getMessage());
            return $this->error([], 'Something went wrong while generating suggestions.', 500);
        }
    }
}
