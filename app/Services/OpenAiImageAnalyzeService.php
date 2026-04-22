<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class OpenAiImageAnalyzeService
{
    public function analyze($imagePath)
    {
        try {
            if (!file_exists($imagePath)) {
                return ['error' => 'Image file not found.'];
            }

            $imageData = base64_encode(file_get_contents($imagePath));

            // $prompt = "You are an expert fashion AI. Analyze ONLY the clothing in this single image.

            //     Rules:
            //     1. If more than one person is present, return a JSON object with {\"error\": \"Multiple people detected. Only one clothing item allowed\"}.
            //     2. If more than one clothing item is detected, return a JSON object with {\"error\": \"Multiple clothing items detected. Only one allowed\"}.
            //     3. If no clothing is detected, return a JSON object with {\"error\": \"No clothing detected\"}.
            //     4. Otherwise, return a JSON object with these fields exactly:
            //     - Clothing_Type
            //     - Material
            //     - Pattern
            //     - Color
            //     - Season";

             $prompt = "You are an expert fashion AI. Analyze ONLY the fashion item in this single image.

                Rules:
                1. If more than one person is present, return a JSON object with {\"error\": \"Multiple people detected. Only one fashion item allowed\"}.
                2. If multiple DIFFERENT types of fashion items are detected (e.g., both shoes AND bag, or shirt AND pants), return a JSON object with {\"error\": \"Multiple different fashion items detected. Only one type allowed\"}.
                3. EXCEPTION: The following are considered SINGLE fashion items even if they appear as pairs or sets:
                   - A pair of shoes (left and right shoe together)
                   - A pair of earrings
                   - A pair of gloves
                   - A pair of socks
                   - A matching set of the same item type
                4. Fashion items include: clothing (shirts, pants, dresses, etc.), shoes, bags, accessories (jewelry, belts, scarves, hats, etc.).
                5. If no fashion item is detected, return a JSON object with {\"error\": \"No fashion item detected\"}.
                6. Otherwise, return a JSON object with these fields exactly:
                   - Clothing_Type
                   - Material
                   - Pattern
                   - Color
                   - Season";


            $payload = [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:image/jpeg;base64,' . $imageData,
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 500,
            ];

            $response = Http::withToken(config('services.openai.api_key'))
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', $payload);

            if (!$response->successful()) {
                return [
                    'error' => 'OpenAI API request failed',
                    'details' => $response->json()
                ];
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                return ['error' => 'No response content from AI'];
            }

            $cleanContent = preg_replace('/```json|```/i', '', trim($content));
            $json = json_decode($cleanContent, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }

            return [
                'error' => 'Invalid JSON response',
                'raw'   => $content
            ];
        } catch (\Exception $e) {

            Log::error('Image Analyze Error: ' . $e->getMessage());
            return ['error' => 'Internal server error. Please try again later.'];
        }
    }
}
