<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OpenAiStyleServiceImageGenerateVersion
{
    private const GPT_MODEL = 'gpt-4o-mini';
    private const DALLE_MODEL = 'dall-e-3';
    private const IMAGE_SIZE = '1024x1024';
    private const IMAGE_QUALITY = 'hd';
    private const MAX_ANALYSIS_TOKENS = 500;
    private const TEMPERATURE = 0.7;
    private const REQUEST_TIMEOUT = 90;

    public function getItemStyleSuggestions(Item $item, $preferences, $webSiteNames = [], $userGender = 'unisex')
    {
        try {
            // Step 1: Deep analyze item image to understand what it is
            $analysisResult = $this->analyzeItemImageDeep($item, $userGender, $preferences);

            if (isset($analysisResult['error'])) {
                return $analysisResult;
            }

            // Step 2: Generate dynamic outfit flat-lay based on analysis
            $generatedImagePath = $this->generateDynamicOutfitImage(
                $item,
                $analysisResult,
                $userGender,
                $preferences
            );

            // Step 3: Build response matching the required format
            return [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'category_id' => $item->category_id,
                'clouth_type' => $item->clouth_type ?? 'Clothing',
                'material' => $item->material ?? 'Not specified',
                'pattern' => $item->pattern ?? 'Plain',
                'color' => $item->color ?? 'Not specified',
                'season' => $item->season ?? 'All seasons',
                'item_name' => $analysisResult['enhanced_name'] ?? $item->item_name,
                'slug' => $item->slug,
                'image' => $item->image ? url($item->image) : null,
                'buying_info' => $this->generateBuyingInfo($webSiteNames),
                'site_links' => $this->mergeSiteLinks([], $webSiteNames),
                'created_at' => $item->created_at?->diffForHumans(),
                'styling_suggestion' => $analysisResult['styling_tip'] ?? 'This item can be styled in multiple ways for different occasions.',
                'generated_outfit_image' => $generatedImagePath ? url($generatedImagePath) : null,
            ];

        } catch (\Exception $e) {
            Log::error('Item Styling Error', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['error' => 'Failed to generate outfit collection.'];
        }
    }

    /**
     * Deep analyze item image - understand what it is and how to style it
     */
    private function analyzeItemImageDeep(Item $item, $userGender, $preferences)
    {
        try {
            $imageData = $this->prepareImageForAnalysis($item);

            if (!$imageData) {
                Log::warning('Image preparation failed', ['item_id' => $item->id]);
                return $this->createFallbackAnalysis($item);
            }

            $prompt = $this->buildDeepAnalysisPrompt($item, $userGender, $preferences);

            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are an expert fashion stylist and image analyzer. Your job is to identify clothing items precisely and suggest the best complementary pieces for a complete outfit. Always return valid JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $imageData,
                                'detail' => 'high'
                            ]
                        ]
                    ]
                ]
            ];

            $response = Http::withToken(config('services.openai.api_key'))
                ->timeout(self::REQUEST_TIMEOUT)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::GPT_MODEL,
                    'messages' => $messages,
                    'max_tokens' => self::MAX_ANALYSIS_TOKENS,
                    'temperature' => self::TEMPERATURE,
                    'response_format' => ['type' => 'json_object']
                ]);

            if (!$response->successful()) {
                Log::error('OpenAI Analysis Error', [
                    'status' => $response->status(),
                    'item_id' => $item->id
                ]);
                return $this->createFallbackAnalysis($item);
            }

            $result = $response->json();
            $aiResponse = $result['choices'][0]['message']['content'] ?? null;

            if (!$aiResponse) {
                return $this->createFallbackAnalysis($item);
            }

            return $this->parseDeepAnalysisResponse($aiResponse, $item);

        } catch (\Exception $e) {
            Log::error('AI Deep Analysis Error', [
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ]);
            return $this->createFallbackAnalysis($item);
        }
    }

    /**
     * Build deep analysis prompt
     */
    private function buildDeepAnalysisPrompt(Item $item, $userGender, $preferences)
    {
        $styleType = $preferences['type'] ?? 'Casual';
        $styleKeywords = $preferences['keywords'] ?? '';

        $prompt = "Analyze this clothing item image in detail:\n\n";
        $prompt .= "Context:\n";
        $prompt .= "- Item Name: " . ($item->item_name ?? 'Unknown') . "\n";
        $prompt .= "- Type: " . ($item->clouth_type ?? 'Unknown') . "\n";
        $prompt .= "- Gender: " . ucfirst($userGender) . "\n";
        $prompt .= "- Style Preference: {$styleType}\n";
        if ($styleKeywords) {
            $prompt .= "- Style Keywords: {$styleKeywords}\n";
        }

        $prompt .= "\nYour tasks:\n";
        $prompt .= "1. Identify the EXACT clothing type from the image (e.g., t-shirt, dress shirt, jeans, dress, blazer, suit, etc.)\n";
        $prompt .= "2. Detect the dominant color accurately from the image\n";
        $prompt .= "3. Identify material/fabric if visible (cotton, wool, silk, denim, etc.)\n";
        $prompt .= "4. Identify pattern from image (plain, striped, floral, plaid, etc.)\n";
        $prompt .= "5. Create an attractive, marketable item name\n";
        $prompt .= "6. Suggest 2-4 SPECIFIC complementary items needed to complete this outfit\n";
        $prompt .= "7. Provide detailed styling advice (50-70 words) with specific recommendations\n\n";

        $prompt .= "CRITICAL RULES for complementary items:\n";
        $prompt .= "- If it's a TOP (shirt, t-shirt, blouse, sweater): suggest ONLY bottom wear + footwear + 1-2 accessories\n";
        $prompt .= "- If it's a BOTTOM (pants, jeans, skirt): suggest ONLY top wear + footwear + 1-2 accessories\n";
        $prompt .= "- If it's a DRESS or FULL OUTFIT or SUIT: suggest ONLY accessories (shoes, bag, jewelry) NO additional clothing\n";
        $prompt .= "- If it's OUTERWEAR (jacket, coat, blazer): suggest complete outfit to wear underneath\n";
        $prompt .= "- Consider the {$styleType} style preference\n";
        $prompt .= "- Keep suggestions realistic, specific, and practical\n";
        $prompt .= "- Mention specific colors and styles that would complement the main item\n\n";

        $prompt .= "Return ONLY this exact JSON structure:\n";
        $prompt .= json_encode([
            'detected_type' => 'specific clothing type from image',
            'detected_color' => 'exact color name from image',
            'detected_material' => 'fabric/material from image',
            'detected_pattern' => 'pattern from image',
            'enhanced_name' => 'attractive product name',
            'styling_tip' => 'detailed styling advice 50-70 words',
            'complementary_items' => [
                ['item' => 'specific item name', 'description' => 'why this item works'],
                ['item' => 'specific item name', 'description' => 'why this item works']
            ]
        ], JSON_PRETTY_PRINT);

        return $prompt;
    }

    /**
     * Generate dynamic outfit image based on deep analysis
     */
    private function generateDynamicOutfitImage(Item $item, array $analysis, $userGender, $preferences)
    {
        try {
            $imagePrompt = $this->buildDynamicFlatLayPrompt($item, $analysis, $userGender, $preferences);

            Log::info('Generating dynamic outfit flat-lay', [
                'item_id' => $item->id,
                'detected_type' => $analysis['detected_type'] ?? 'unknown',
                'complementary_count' => count($analysis['complementary_items'] ?? [])
            ]);

            $response = Http::withToken(config('services.openai.api_key'))
                ->timeout(self::REQUEST_TIMEOUT)
                ->post('https://api.openai.com/v1/images/generations', [
                    'model' => self::DALLE_MODEL,
                    'prompt' => $imagePrompt,
                    'n' => 1,
                    'size' => self::IMAGE_SIZE,
                    'quality' => self::IMAGE_QUALITY,
                    'response_format' => 'url'
                ]);

            if (!$response->successful()) {
                Log::error('DALL-E API Error', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'item_id' => $item->id
                ]);
                return null;
            }

            $result = $response->json();
            $imageUrl = $result['data'][0]['url'] ?? null;

            if (!$imageUrl) {
                Log::error('No image URL in response', ['item_id' => $item->id]);
                return null;
            }

            $savedPath = $this->downloadAndSaveImage($imageUrl, $item->user_id);

            Log::info('Dynamic outfit image generated', [
                'item_id' => $item->id,
                'saved_path' => $savedPath
            ]);

            return $savedPath;

        } catch (\Exception $e) {
            Log::error('Image Generation Error', [
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Build dynamic flat-lay prompt based on analysis
     */
    private function buildDynamicFlatLayPrompt(Item $item, array $analysis, $userGender, $preferences)
    {
        $detectedType = $analysis['detected_type'] ?? $item->clouth_type ?? 'clothing item';
        $color = $analysis['detected_color'] ?? $item->color ?? 'neutral';
        $material = $analysis['detected_material'] ?? '';
        $pattern = $analysis['detected_pattern'] ?? 'plain';
        $styleType = $preferences['type'] ?? 'Casual';
        $complementaryItems = $analysis['complementary_items'] ?? [];

        // Base prompt
        $prompt = "Create a high-quality fashion flat lay photograph on a pristine white background. ";
        $prompt .= "Professional top-down view, perfectly arranged and organized. ";
        $prompt .= "Photography style: {$styleType} {$this->getGenderStyleDescription($userGender)} fashion editorial. ";

        // Main item (center piece)
        $prompt .= "\n\nMAIN FOCAL ITEM (center): ";
        $prompt .= "{$color} {$detectedType}";
        if ($material) {
            $prompt .= " in {$material}";
        }
        if ($pattern && $pattern !== 'plain' && $pattern !== 'solid') {
            $prompt .= " with {$pattern} pattern";
        }
        $prompt .= ". Position this as the hero piece, prominently displayed in the center. ";

        // Dynamic complementary items from AI analysis
        if (!empty($complementaryItems) && count($complementaryItems) > 0) {
            $prompt .= "\n\nCOMPLEMENTARY ITEMS (arranged aesthetically around the main item):\n";

            foreach ($complementaryItems as $index => $compItem) {
                $itemName = $compItem['item'] ?? '';
                $itemDesc = $compItem['description'] ?? '';

                if ($itemName) {
                    $prompt .= ($index + 1) . ". {$itemName}";
                    if ($itemDesc) {
                        $prompt .= " ({$itemDesc})";
                    }
                    $prompt .= " - positioned with proper spacing and balance. ";
                }
            }
        } else {
            // Intelligent fallback based on item type
            $prompt .= $this->getIntelligentComplementaryItems($detectedType, $color, $userGender, $styleType);
        }

        // Photography specifications
        $prompt .= "\n\nPHOTOGRAPHY REQUIREMENTS: ";
        $prompt .= "Magazine-quality editorial flat lay. ";
        $prompt .= "Soft, even studio lighting with minimal shadows. ";
        $prompt .= "Items arranged with professional spacing and visual balance. ";
        $prompt .= "Clean minimalist aesthetic. ";
        $prompt .= "All items clearly visible with realistic textures, fabrics, and materials. ";
        $prompt .= "High-end fashion catalog quality. ";
        $prompt .= "Color coordination that complements the {$color} main piece. ";
        $prompt .= "Overall style: {$styleType}, suitable for {$userGender}.";

        return substr($prompt, 0, 4000);
    }

    /**
     * Intelligent complementary items based on detected type
     */
    private function getIntelligentComplementaryItems($itemType, $color, $gender, $style)
    {
        $itemTypeLower = strtolower($itemType);
        $items = "\n\nCOMPLEMENTARY ITEMS: ";

        // Detect if it's a top
        if (preg_match('/\b(shirt|blouse|top|t-shirt|tee|sweater|cardigan|hoodie|tank)\b/i', $itemTypeLower)) {
            if ($gender === 'female') {
                $items .= "1. High-waisted dark wash jeans or tailored trousers. ";
                $items .= "2. Elegant heeled sandals or ankle boots in neutral tone. ";
                $items .= "3. Structured leather handbag or tote. ";
            } else {
                $items .= "1. Slim-fit dark wash jeans or chinos. ";
                $items .= "2. Clean white sneakers or leather casual shoes. ";
                $items .= "3. Minimalist leather watch with matching strap. ";
            }
        }
        // Detect if it's a bottom
        elseif (preg_match('/\b(pant|jean|trouser|skirt|short|legging)\b/i', $itemTypeLower)) {
            if ($gender === 'female') {
                $items .= "1. Elegant fitted blouse or silk top in complementary color. ";
                $items .= "2. Pointed-toe pumps or stylish ballet flats. ";
                $items .= "3. Statement necklace or earrings with small clutch. ";
            } else {
                $items .= "1. Crisp button-down shirt or fitted polo in white or complementary color. ";
                $items .= "2. Brown leather belt with silver buckle. ";
                $items .= "3. Leather oxford shoes or clean white sneakers. ";
            }
        }
        // Detect if it's a complete outfit (dress, suit, jumpsuit)
        elseif (preg_match('/\b(dress|suit|jumpsuit|romper|gown|outfit)\b/i', $itemTypeLower)) {
            if ($gender === 'female') {
                $items .= "1. Elegant heels or stylish flats matching the outfit tone. ";
                $items .= "2. Delicate gold or silver jewelry set (necklace and earrings). ";
                $items .= "3. Small structured clutch or crossbody bag. ";
            } else {
                $items .= "1. Polished dress shoes in black or brown leather. ";
                $items .= "2. Classic leather watch with metal or leather band. ";
                $items .= "3. Sleek designer sunglasses. ";
            }
        }
        // Outerwear
        elseif (preg_match('/\b(jacket|coat|blazer|cardigan)\b/i', $itemTypeLower)) {
            if ($gender === 'female') {
                $items .= "1. Simple fitted tee or blouse underneath. ";
                $items .= "2. Dark skinny jeans or midi skirt. ";
                $items .= "3. Ankle boots and structured handbag. ";
            } else {
                $items .= "1. White or light colored shirt underneath. ";
                $items .= "2. Dark slim-fit jeans or dress pants. ";
                $items .= "3. Clean sneakers or leather shoes with watch. ";
            }
        }
        // Fallback for unknown items
        else {
            $items .= "1. Complementary " . ($gender === 'female' ? 'clothing piece' : 'shirt or pants') . " in neutral tone. ";
            $items .= "2. Appropriate footwear matching the style. ";
            $items .= "3. Coordinating accessories (watch, bag, or jewelry). ";
        }

        return $items;
    }

    /**
     * Parse deep analysis response
     */
    private function parseDeepAnalysisResponse($aiResponse, Item $item)
    {
        try {
            $data = json_decode($aiResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('JSON parse error in AI response', ['item_id' => $item->id]);
                return $this->createFallbackAnalysis($item);
            }

            return [
                'detected_type' => $data['detected_type'] ?? $item->clouth_type ?? 'Clothing',
                'detected_color' => $data['detected_color'] ?? $item->color ?? 'Neutral',
                'detected_material' => $data['detected_material'] ?? $item->material ?? 'Cotton blend',
                'detected_pattern' => $data['detected_pattern'] ?? $item->pattern ?? 'Plain',
                'enhanced_name' => $data['enhanced_name'] ?? $item->item_name,
                'styling_tip' => $data['styling_tip'] ?? 'Style this versatile piece with complementary items for a polished look.',
                'complementary_items' => $data['complementary_items'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('Parse analysis error', ['error' => $e->getMessage(), 'item_id' => $item->id]);
            return $this->createFallbackAnalysis($item);
        }
    }

    /**
     * Download and save generated image
     */
    private function downloadAndSaveImage(string $imageUrl, int $userId): string
    {
        try {
            $imageContent = Http::timeout(60)->get($imageUrl)->body();

            if (empty($imageContent)) {
                throw new \Exception("Failed to download image from DALL-E");
            }

            $filename = 'outfit_' . $userId . '_' . time() . '_' . Str::random(8) . '.png';
            $relativePath = 'uploads/images/generated/' . $filename;
            $publicPath = public_path($relativePath);

            if (!file_exists(dirname($publicPath))) {
                mkdir(dirname($publicPath), 0755, true);
            }

            file_put_contents($publicPath, $imageContent);

            Log::info('Outfit image saved successfully', [
                'path' => $relativePath,
                'size' => strlen($imageContent) . ' bytes',
                'user_id' => $userId
            ]);

            return $relativePath;

        } catch (\Exception $e) {
            Log::error('Failed to save generated image', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Prepare item image for analysis
     */
    private function prepareImageForAnalysis(Item $item)
    {
        try {
            if (!$item->image) {
                return null;
            }

            $imagePath = str_replace(url('/'), '', $item->image);
            $imagePath = ltrim($imagePath, '/');

            // Try public path first
            if (file_exists(public_path($imagePath))) {
                $imageContent = file_get_contents(public_path($imagePath));
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, public_path($imagePath));
                finfo_close($finfo);
                $base64 = base64_encode($imageContent);
                return "data:{$mimeType};base64,{$base64}";
            }

            // Try storage disk
            if (Storage::disk('public')->exists($imagePath)) {
                $imageContent = Storage::disk('public')->get($imagePath);
                $mimeType = Storage::disk('public')->mimeType($imagePath);
                $base64 = base64_encode($imageContent);
                return "data:{$mimeType};base64,{$base64}";
            }

            // If it's already a URL
            if (filter_var($item->image, FILTER_VALIDATE_URL)) {
                return $item->image;
            }

            Log::warning('Image file not found', ['item_id' => $item->id, 'path' => $imagePath]);
            return null;

        } catch (\Exception $e) {
            Log::error('Image preparation error', [
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get gender style description
     */
    private function getGenderStyleDescription($gender)
    {
        switch (strtolower($gender)) {
            case 'male':
                return 'men\'s';
            case 'female':
                return 'women\'s';
            default:
                return 'unisex';
        }
    }

    /**
     * Create fallback analysis when AI fails
     */
    private function createFallbackAnalysis(Item $item)
    {
        return [
            'detected_type' => $item->clouth_type ?? 'Clothing',
            'detected_color' => $item->color ?? 'Neutral',
            'detected_material' => $item->material ?? 'Cotton blend',
            'detected_pattern' => $item->pattern ?? 'Plain',
            'enhanced_name' => $item->item_name ?? 'Stylish Fashion Item',
            'styling_tip' => 'This versatile piece pairs beautifully with neutral tones and complementary accessories for any occasion. Layer with classic staples to create a polished, put-together look.',
            'complementary_items' => []
        ];
    }

    /**
     * Generate buying info text
     */
    private function generateBuyingInfo($webSiteNames)
    {
        if (!empty($webSiteNames)) {
            $sites = array_slice($webSiteNames, 0, 3);
            $siteList = implode(', ', array_map(function($site) {
                return ucfirst(strtolower($site));
            }, $sites));
            return "Look for unique pieces to complement this item at {$siteList} and other high-end fashion retailers.";
        }

        return 'Look for unique pieces to complement this suit at high-end fashion retailers and streetwear brands.';
    }

    /**
     * Merge and format site links
     */
    private function mergeSiteLinks($aiLinks, $webSiteNames)
    {
        $mergedLinks = [];

        // Add provided website names
        if (!empty($webSiteNames)) {
            foreach ($webSiteNames as $siteName) {
                if (count($mergedLinks) >= 7) break;

                $url = filter_var($siteName, FILTER_VALIDATE_URL)
                    ? $siteName
                    : $this->createWebsiteUrl($siteName);

                if ($url && !in_array($url, $mergedLinks)) {
                    $mergedLinks[] = $url;
                }
            }
        }

        // Fill with fallback sites if needed
        if (count($mergedLinks) < 5) {
            $fallbackSites = $this->getFallbackSites();
            foreach ($fallbackSites as $site) {
                if (count($mergedLinks) >= 7) break;
                if (!in_array($site, $mergedLinks)) {
                    $mergedLinks[] = $site;
                }
            }
        }

        return array_values(array_slice(array_unique($mergedLinks), 0, 7));
    }

    /**
     * Create proper website URL from name
     */
    private function createWebsiteUrl($siteName)
    {
        $cleanName = strtolower(trim($siteName));
        $cleanName = preg_replace('/^(https?:\/\/)?(www\.)?/', '', $cleanName);

        return strpos($cleanName, '.') !== false
            ? 'https://' . $cleanName
            : 'https://www.' . $cleanName . '.com';
    }

    /**
     * Get fallback shopping sites
     */
    private function getFallbackSites()
    {
        return [
            'https://www.gucci.com',
            'https://www.prada.com',
            'https://www.ysl.com',
            'https://www.balenciaga.com',
            'https://www.asos.com',
            'https://www.louisvuitton.com',
            'https://www.versace.com'
        ];
    }
}
