<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Item;

class OpenAiWeatherSuggestionService
{
    public function getSuggestions($userId, $items, $weatherData)
    {
        try {

            $itemData = collect($items)->map(function ($item) {
                return "Item: {$item->item_name}, Type: {$item->clouth_type}, Material: {$item->material}, Pattern: {$item->pattern}, Color: {$item->color}, Season: {$item->season}, Image: {$item->image_path}, ID: {$item->id}";
            })->implode("\n");

            $weatherSummary = $weatherData['summary'] ?? '';

            $prompt = <<<EOD
                        User's Items (choose only from this list, do not create new ones):
                        $itemData

                        Today's Weather:
                        $weatherSummary

                        Task: Based on the user's items and today's weather, suggest the 3 best matching items for today.
                        - Select ONLY from the provided user's items above.
                        - Ensure variety: do not return two items of the same category/type (e.g., two shirts, two pants).
                        - If multiple items of the same category exist, choose the single best match.
                        - For each suggestion, return the exact "item_id", "item_name", and "image_path" from the given items.
                        - For each suggestion, return the exact "item_id", "item_name", and "image_path" from the given items.
                        - Do not invent or modify item names, image paths, or IDs.

                        Respond ONLY in the following strict JSON format:
                        {
                            "items": [
                                {"item_id": "...", "item_name": "...", "image_path": "..."},
                                {"item_id": "...", "item_name": "...", "image_path": "..."},
                                {"item_id": "...", "item_name": "...", "image_path": "..."}
                            ]
                        }

                        Do not include any explanation or extra text.
                    EOD;



            $response = Http::withToken(config('services.openai.api_key'))
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a fashion assistant.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 500,
                ]);

            if (!$response->successful()) {
                return [
                    'error' => 'OpenAI API request failed',
                    'details' => $response->json()
                ];
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? '';
            $suggestionsArray = [];
            try {
                $json = json_decode($content, true);
                if (isset($json['items']) && is_array($json['items'])) {
                    $suggestionsArray = $json['items'];
                } else {
                    return [
                        'error' => 'OpenAI did not return items in expected format',
                        'details' => $content
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'error' => 'Failed to parse OpenAI response as JSON',
                    'details' => $content
                ];
            }

            return [
                'success' => true,
                'suggestions' => $suggestionsArray
            ];
        } catch (\Exception $e) {
            Log::error('Weather Suggestion Error: ' . $e->getMessage());
            return ['error' => 'Internal server error. Please try again later.'];
        }
    }
}
