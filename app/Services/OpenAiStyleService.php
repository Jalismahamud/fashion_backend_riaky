<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiStyleService
{
    private const GPT_MODEL = 'gpt-4o-mini';
    private const MAX_ANALYSIS_TOKENS = 500;
    private const TEMPERATURE = 0.7;
    private const REQUEST_TIMEOUT = 90;

    /**
     * Complementary item types mapping
     * Defines what items complement each clothing type
     */
    private const COMPLEMENTARY_TYPES = [
        // Tops need bottoms, shoes, and accessories
        'shirt' => ['pant', 'jeans', 'trousers', 'chinos', 'shoes', 'sneakers', 'boots', 'watch', 'belt', 'bag', 'tie'],
        'blouse' => ['pant', 'jeans', 'skirt', 'trousers', 'shoes', 'heels', 'sandals', 'bag', 'jewelry', 'scarf', 'necklace'],
        't-shirt' => ['pant', 'jeans', 'shorts', 'skirt', 'joggers', 'shoes', 'sneakers', 'bag', 'cap', 'hat', 'watch'],
        'top' => ['pant', 'jeans', 'skirt', 'shorts', 'leggings', 'shoes', 'sneakers', 'bag', 'accessories', 'belt'],
        'sweater' => ['pant', 'jeans', 'trousers', 'skirt', 'shoes', 'boots', 'scarf', 'bag', 'watch'],
        'hoodie' => ['pant', 'jeans', 'joggers', 'shorts', 'sneakers', 'cap', 'bag', 'watch'],
        'tank' => ['pant', 'jeans', 'shorts', 'skirt', 'shoes', 'sneakers', 'bag', 'jewelry'],
        'polo' => ['pant', 'jeans', 'trousers', 'shorts', 'shoes', 'sneakers', 'belt', 'watch', 'bag'],

        // Bottoms need tops, shoes, and accessories
        'pant' => ['shirt', 'blouse', 't-shirt', 'top', 'sweater', 'polo', 'shoes', 'sneakers', 'belt', 'bag', 'watch'],
        'jeans' => ['shirt', 'blouse', 't-shirt', 'top', 'jacket', 'hoodie', 'shoes', 'sneakers', 'belt', 'bag', 'watch'],
        'trousers' => ['shirt', 'blouse', 'blazer', 'sweater', 'shoes', 'belt', 'bag', 'watch', 'tie'],
        'chinos' => ['shirt', 't-shirt', 'polo', 'sweater', 'shoes', 'sneakers', 'belt', 'bag', 'watch'],
        'skirt' => ['blouse', 'top', 't-shirt', 'sweater', 'shoes', 'heels', 'sandals', 'bag', 'jewelry', 'belt'],
        'shorts' => ['shirt', 't-shirt', 'top', 'tank', 'polo', 'sneakers', 'sandals', 'cap', 'bag', 'watch'],
        'leggings' => ['top', 't-shirt', 'sweater', 'hoodie', 'sneakers', 'bag'],
        'joggers' => ['t-shirt', 'hoodie', 'sweater', 'sneakers', 'bag', 'cap'],

        // Dresses/Full outfits need only accessories
        'dress' => ['shoes', 'heels', 'sandals', 'flats', 'bag', 'jewelry', 'scarf', 'hat', 'necklace', 'earrings'],
        'suit' => ['shoes', 'belt', 'watch', 'bag', 'tie', 'socks'],
        'jumpsuit' => ['shoes', 'heels', 'sneakers', 'bag', 'jewelry', 'belt', 'hat'],
        'romper' => ['shoes', 'sandals', 'sneakers', 'bag', 'hat', 'jewelry'],

        // Outerwear needs complete outfit underneath
        'jacket' => ['shirt', 't-shirt', 'top', 'pant', 'jeans', 'shoes', 'bag', 'scarf'],
        'blazer' => ['shirt', 'blouse', 'pant', 'trousers', 'skirt', 'shoes', 'bag', 'watch', 'belt'],
        'coat' => ['shirt', 'sweater', 'pant', 'jeans', 'shoes', 'scarf', 'bag', 'boots'],
        'cardigan' => ['shirt', 't-shirt', 'top', 'pant', 'jeans', 'skirt', 'shoes', 'bag'],
        'vest' => ['shirt', 'blouse', 'pant', 'trousers', 'shoes', 'tie', 'watch'],

        // Footwear needs clothing
        'shoes' => ['shirt', 'pant', 'jeans', 'dress', 'suit', 'bag', 'belt', 'watch'],
        'sneakers' => ['t-shirt', 'jeans', 'shorts', 'hoodie', 'joggers', 'bag', 'cap', 'watch'],
        'boots' => ['pant', 'jeans', 'dress', 'skirt', 'jacket', 'coat', 'bag', 'scarf'],
        'heels' => ['dress', 'skirt', 'pant', 'trousers', 'blouse', 'bag', 'jewelry'],
        'sandals' => ['dress', 'shorts', 'skirt', 't-shirt', 'top', 'bag', 'hat'],
        'flats' => ['dress', 'skirt', 'pant', 'jeans', 'blouse', 'bag', 'jewelry'],

        // Accessories need main clothing items
        'bag' => ['shirt', 'pant', 'dress', 'shoes', 'jewelry', 'top', 'jeans'],
        'handbag' => ['shirt', 'pant', 'dress', 'shoes', 'jewelry', 'top', 'jeans'],
        'hat' => ['shirt', 't-shirt', 'pant', 'jeans', 'dress', 'shoes', 'bag'],
        'cap' => ['t-shirt', 'hoodie', 'jeans', 'shorts', 'sneakers', 'bag'],
        'scarf' => ['coat', 'jacket', 'sweater', 'pant', 'jeans', 'shoes', 'bag', 'boots'],
        'belt' => ['shirt', 'pant', 'jeans', 'trousers', 'skirt', 'shoes', 'bag'],
        'watch' => ['shirt', 'pant', 'jeans', 'suit', 'shoes', 'bag'],
        'jewelry' => ['dress', 'blouse', 'top', 'skirt', 'shoes', 'bag'],
        'necklace' => ['dress', 'blouse', 'top', 'shirt', 'shoes', 'bag'],
        'earrings' => ['dress', 'blouse', 'top', 'bag', 'shoes'],
        'tie' => ['shirt', 'suit', 'trousers', 'blazer', 'shoes', 'watch', 'belt'],
        'socks' => ['shoes', 'sneakers', 'boots', 'pant', 'jeans', 'shorts'],
        'casual' => ['pant', 'jeans', 'shorts', 'shoes', 'sneakers', 'bag', 'cap', 'watch'],
    ];

    /**
     * Main method to get item style suggestions
     * @param Item $item - The wardrobe item
     * @param array $preferences - User style preferences
     * @param array $webSiteNames - Shopping website names
     * @param string $userGender - User gender
     * @param string|null $imagePath - Optional image_path override
     */
    public function getItemStyleSuggestions(Item $item, $preferences, $webSiteNames = [], $userGender = 'unisex', $imagePath = null)
    {
        try {
            // Step 1: Analyze item to understand it better using AI (optional enhancement)
            // Use provided image_path or fall back to item's image_path
            $finalImagePath = $imagePath ?? $item->image_path;

            $analysisResult = $this->analyzeItemImageDeep($item, $userGender, $preferences, $finalImagePath);

            if (isset($analysisResult['error'])) {
                $analysisResult = $this->createFallbackAnalysis($item);
            }

            // Step 2: Find matching items from user's wardrobe
            $matchingItems = $this->findComplementaryItemsFromWardrobe($item, $analysisResult, $userGender);

            // Extract just the path from the full URL for image_path
            $imagePathOnly = $this->extractPathFromUrl($finalImagePath);

            // Step 3: Build response with matching items
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
                'image_path' => url($imagePathOnly), // Add base URL to the path
                'buying_info' => $this->generateBuyingInfo($webSiteNames),
                'site_links' => $this->mergeSiteLinks([], $webSiteNames),
                'created_at' => $item->created_at?->diffForHumans(),
                'styling_suggestion' => $analysisResult['styling_tip'] ?? 'This item can be styled in multiple ways for different occasions.',
                'generated_outfit_image' => $matchingItems, // Array of matching item objects with details
            ];

        } catch (\Exception $e) {
            Log::error('Item Styling Error', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['error' => 'Failed to generate outfit suggestions.'];
        }
    }

    /**
     * Extract just the path from a full URL or return as is if already a path
     */
    private function extractPathFromUrl($urlOrPath)
    {
        if (!$urlOrPath) {
            return null;
        }

        // If it's a full URL, extract the path
        if (filter_var($urlOrPath, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($urlOrPath);
            return ltrim($parsedUrl['path'] ?? '', '/');
        }

        // Already a path, just clean it
        return ltrim($urlOrPath, '/');
    }

    /**
     * Find complementary items from user's wardrobe based on item type
     */
    private function findComplementaryItemsFromWardrobe(Item $item, array $analysis, $userGender)
    {
        try {
            $itemType = strtolower($analysis['detected_type'] ?? $item->clouth_type ?? '');
            $userId = $item->user_id;

            Log::info('Finding complementary items from wardrobe', [
                'item_id' => $item->id,
                'item_type' => $itemType,
                'user_id' => $userId,
                'item_color' => $analysis['detected_color'] ?? 'unknown'
            ]);

            // Get complementary types needed for this item
            $complementaryTypes = $this->getComplementaryTypes($itemType);

            if (empty($complementaryTypes)) {
                Log::info('No complementary types defined for item type', ['item_type' => $itemType]);
                return [];
            }

            Log::info('Searching for complementary types', [
                'item_id' => $item->id,
                'complementary_types' => $complementaryTypes
            ]);

            // Query user's wardrobe for matching items
            $matchingItems = Item::where('user_id', $userId)
                ->where('id', '!=', $item->id) // Exclude the current item
                ->where(function($query) use ($complementaryTypes) {
                    foreach ($complementaryTypes as $type) {
                        $query->orWhere('clouth_type', 'LIKE', '%' . $type . '%');
                    }
                })
                ->whereNotNull('image_path') // Must have image_path
                ->where('image_path', '!=', '') // Image path must not be empty
                ->get();

            Log::info('Initial wardrobe query results', [
                'item_id' => $item->id,
                'total_found' => $matchingItems->count()
            ]);

            if ($matchingItems->isEmpty()) {
                Log::info('No matching items found in wardrobe', [
                    'item_id' => $item->id,
                    'user_id' => $userId,
                    'searched_types' => $complementaryTypes
                ]);
                return [];
            }

            // Filter by color compatibility and season
            $filteredItems = $this->filterByCompatibility($matchingItems, $item, $analysis);

            Log::info('After compatibility filtering', [
                'item_id' => $item->id,
                'filtered_count' => $filteredItems->count()
            ]);

            // Randomly shuffle and limit to 10 items for variety
            $selectedItems = $filteredItems->shuffle()->take(10);

            // Return detailed item information as objects with image_path with base URL
            $itemsWithDetails = $selectedItems->map(function($matchedItem) {
                // Extract path and add base URL
                $imagePath = $this->extractPathFromUrl($matchedItem->image_path);

                return [
                    'id' => $matchedItem->id,
                    'user_id' => $matchedItem->user_id,
                    'category_id' => $matchedItem->category_id,
                    'slug' => $matchedItem->slug,
                    'clouth_type' => $matchedItem->clouth_type ?? 'Not specified',
                    'material' => $matchedItem->material ?? 'Not specified',
                    'pattern' => $matchedItem->pattern ?? 'Not specified',
                    'color' => $matchedItem->color ?? 'Not specified',
                    'season' => $matchedItem->season ?? 'All seasons',
                    'item_name' => $matchedItem->item_name ?? 'Untitled Item',
                    'image_path' => url($imagePath), // Add base URL to the path
                    'buying_info' => $matchedItem->buying_info ?? null,
                    'site_link' => $matchedItem->site_link ?? null,
                    'created_at' => $matchedItem->created_at?->diffForHumans(),
                ];
            })->values()->toArray();

            Log::info('Complementary items found successfully', [
                'item_id' => $item->id,
                'final_count' => count($itemsWithDetails),
                'item_ids' => $selectedItems->pluck('id')->toArray()
            ]);

            return $itemsWithDetails;

        } catch (\Exception $e) {
            Log::error('Error finding complementary items from wardrobe', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get complementary clothing types for given item type
     */
    private function getComplementaryTypes($itemType)
    {
        $itemTypeLower = strtolower(trim($itemType));

        // Direct exact match first
        if (isset(self::COMPLEMENTARY_TYPES[$itemTypeLower])) {
            return self::COMPLEMENTARY_TYPES[$itemTypeLower];
        }

        // Partial match - check if item type contains any key
        foreach (self::COMPLEMENTARY_TYPES as $type => $complementary) {
            if (stripos($itemTypeLower, $type) !== false) {
                return $complementary;
            }
        }

        // Reverse match - check if any key is contained in item type
        $matches = [];
        foreach (self::COMPLEMENTARY_TYPES as $type => $complementary) {
            if (stripos($itemTypeLower, $type) !== false || stripos($type, $itemTypeLower) !== false) {
                $matches = array_merge($matches, $complementary);
            }
        }

        if (!empty($matches)) {
            return array_unique($matches);
        }

        // Default fallback based on category logic
        Log::warning('Using fallback complementary types', ['item_type' => $itemTypeLower]);

        // If nothing matches, return common items that go with most things
        return ['shirt', 't-shirt', 'pant', 'jeans', 'shoes', 'sneakers', 'bag', 'watch', 'belt'];
    }

    /**
     * Filter items by color compatibility and season
     */
    private function filterByCompatibility($items, Item $baseItem, array $analysis)
    {
        $baseColor = strtolower($analysis['detected_color'] ?? $baseItem->color ?? '');
        $baseSeason = strtolower($baseItem->season ?? '');

        return $items->filter(function($matchedItem) use ($baseColor, $baseSeason, $baseItem) {
            // Season compatibility check
            $itemSeason = strtolower($matchedItem->season ?? '');

            // Always include 'all seasons' items
            if ($itemSeason === 'all seasons' || $baseSeason === 'all seasons' || $itemSeason === 'all-season') {
                return true;
            }

            // If both have specific seasons, they should match
            if ($baseSeason && $itemSeason && $itemSeason !== $baseSeason) {
                return false;
            }

            // Color compatibility
            $itemColor = strtolower($matchedItem->color ?? '');

            // Define neutral/versatile colors that work with everything
            $neutralColors = [
                'black', 'white', 'gray', 'grey', 'beige', 'cream',
                'navy', 'brown', 'tan', 'nude', 'khaki', 'ivory'
            ];

            // If either color is neutral, they're compatible
            foreach ($neutralColors as $neutral) {
                if (stripos($baseColor, $neutral) !== false || stripos($itemColor, $neutral) !== false) {
                    return true;
                }
            }

            // If colors are specified and both are not neutral, accept anyway
            // (user's existing wardrobe suggests they chose these combinations)
            return true;
        });
    }

    /**
     * Deep analyze item image using OpenAI Vision to understand what it is
     * @param Item $item
     * @param string $userGender
     * @param array $preferences
     * @param string|null $imagePath - The image_path to analyze
     */
    private function analyzeItemImageDeep(Item $item, $userGender, $preferences, $imagePath = null)
    {
        try {
            $imageData = $this->prepareImageForAnalysis($imagePath ?? $item->image_path);

            if (!$imageData) {
                Log::warning('Image preparation failed, using fallback analysis', [
                    'item_id' => $item->id,
                    'image_path' => $imagePath
                ]);
                return $this->createFallbackAnalysis($item);
            }

            $prompt = $this->buildDeepAnalysisPrompt($item, $userGender, $preferences);

            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are an expert fashion stylist and image analyzer. Identify clothing items precisely and provide styling suggestions. Always return valid JSON.'
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
                Log::error('OpenAI Analysis API Error', [
                    'status' => $response->status(),
                    'item_id' => $item->id,
                    'response' => $response->json()
                ]);
                return $this->createFallbackAnalysis($item);
            }

            $result = $response->json();
            $aiResponse = $result['choices'][0]['message']['content'] ?? null;

            if (!$aiResponse) {
                Log::warning('No AI response content', ['item_id' => $item->id]);
                return $this->createFallbackAnalysis($item);
            }

            return $this->parseDeepAnalysisResponse($aiResponse, $item);

        } catch (\Exception $e) {
            Log::error('AI Deep Analysis Error', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->createFallbackAnalysis($item);
        }
    }

    /**
     * Build deep analysis prompt for AI
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
        $prompt .= "1. Identify the EXACT clothing type from the image (e.g., shirt, t-shirt, jeans, dress, shoes, jacket, bag, etc.)\n";
        $prompt .= "2. Detect the dominant color accurately from the image\n";
        $prompt .= "3. Identify material/fabric if visible (cotton, wool, silk, denim, leather, etc.)\n";
        $prompt .= "4. Identify pattern from image (plain, striped, floral, plaid, checkered, etc.)\n";
        $prompt .= "5. Create an attractive, marketable item name\n";
        $prompt .= "6. Provide detailed styling advice (50-70 words) with specific recommendations\n\n";

        $prompt .= "Return ONLY this exact JSON structure:\n";
        $prompt .= json_encode([
            'detected_type' => 'specific clothing type',
            'detected_color' => 'exact color name',
            'detected_material' => 'fabric/material',
            'detected_pattern' => 'pattern type',
            'enhanced_name' => 'attractive product name',
            'styling_tip' => 'detailed styling advice'
        ], JSON_PRETTY_PRINT);

        return $prompt;
    }

    /**
     * Parse AI analysis response
     */
    private function parseDeepAnalysisResponse($aiResponse, Item $item)
    {
        try {
            $data = json_decode($aiResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('JSON parse error in AI response', [
                    'item_id' => $item->id,
                    'error' => json_last_error_msg()
                ]);
                return $this->createFallbackAnalysis($item);
            }

            return [
                'detected_type' => $data['detected_type'] ?? $item->clouth_type ?? 'Clothing',
                'detected_color' => $data['detected_color'] ?? $item->color ?? 'Neutral',
                'detected_material' => $data['detected_material'] ?? $item->material ?? 'Cotton blend',
                'detected_pattern' => $data['detected_pattern'] ?? $item->pattern ?? 'Plain',
                'enhanced_name' => $data['enhanced_name'] ?? $item->item_name,
                'styling_tip' => $data['styling_tip'] ?? 'Style this versatile piece with complementary items from your wardrobe for a complete look.',
            ];

        } catch (\Exception $e) {
            Log::error('Parse analysis response error', [
                'error' => $e->getMessage(),
                'item_id' => $item->id
            ]);
            return $this->createFallbackAnalysis($item);
        }
    }

    /**
     * Prepare item image for AI analysis from image_path
     * @param string $imagePath - Path to the image (can be relative or full URL)
     */
    private function prepareImageForAnalysis($imagePath)
    {
        try {
            if (!$imagePath) {
                return null;
            }

            // If it's a full URL, extract the path portion
            if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                $parsedUrl = parse_url($imagePath);
                $imagePath = ltrim($parsedUrl['path'] ?? '', '/');
            } else {
                // Clean the path
                $imagePath = ltrim($imagePath, '/');
            }

            // Build full server path
            $fullPath = public_path($imagePath);

            Log::info('Preparing image for analysis', [
                'original_path' => $imagePath,
                'full_path' => $fullPath,
                'exists' => file_exists($fullPath)
            ]);

            // Check if file exists
            if (!file_exists($fullPath) || !is_file($fullPath)) {
                Log::warning('Image file not found', [
                    'path' => $imagePath,
                    'full_path' => $fullPath
                ]);
                return null;
            }

            // Read file and convert to base64
            $imageContent = file_get_contents($fullPath);

            if ($imageContent === false) {
                Log::error('Failed to read image file', ['full_path' => $fullPath]);
                return null;
            }

            // Get MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fullPath);
            finfo_close($finfo);

            // Encode to base64
            $base64 = base64_encode($imageContent);

            Log::info('Image prepared successfully', [
                'mime_type' => $mimeType,
                'size' => strlen($imageContent) . ' bytes'
            ]);

            return "data:{$mimeType};base64,{$base64}";

        } catch (\Exception $e) {
            Log::error('Image preparation error', [
                'image_path' => $imagePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Create fallback analysis when AI fails or is unavailable
     */
    private function createFallbackAnalysis(Item $item)
    {
        return [
            'detected_type' => $item->clouth_type ?? 'Clothing',
            'detected_color' => $item->color ?? 'Neutral',
            'detected_material' => $item->material ?? 'Cotton blend',
            'detected_pattern' => $item->pattern ?? 'Plain',
            'enhanced_name' => $item->item_name ?? 'Stylish Fashion Item',
            'styling_tip' => 'This versatile piece pairs beautifully with complementary items from your wardrobe. Mix and match to create different looks for various occasions.',
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
                $siteName = parse_url($site, PHP_URL_HOST) ?? $site;
                $siteName = str_replace('www.', '', $siteName);
                return ucfirst(strtolower($siteName));
            }, $sites));
            return "Look for similar pieces to complete your outfit at {$siteList} and other fashion retailers.";
        }

        return 'Look for similar pieces at fashion retailers to expand your wardrobe collection.';
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
