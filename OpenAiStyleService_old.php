<?php

// namespace App\Services;

use App\Models\Item;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiStyleServiceOld
{

    public function getItemStyleSuggestions(Item $item, $preferences, $webSiteNames = [])
    {
        try {

            $aiSuggestions = $this->getAiStyleSuggestions($item, $preferences, $webSiteNames);

            if (isset($aiSuggestions['error'])) {
                return $aiSuggestions;
            }

            return [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'category_id' => $item->category_id,
                'clouth_type' => $item->clouth_type ?? 'Clothing',
                'material' => $item->material ?? 'Not specified',
                'pattern' => $item->pattern ?? 'Plain',
                'color' => $item->color ?? 'Not specified',
                'season' => $item->season ?? 'All seasons',
                'item_name' => $aiSuggestions['item_name'] ?? $item->item_name,
                'slug' => $item->slug,
                'image' => $item->image ? url($item->image) : null,
                'buying_info' => $aiSuggestions['buying_info'] ?? $item->buying_info,
                'site_links' => $aiSuggestions['site_links'] ?? [],
                'created_at' => $item->created_at ? $item->created_at->diffForHumans() : null,
                'styling_suggestion' => $aiSuggestions['styling_suggestion'] ?? 'This item can be styled in multiple ways for different occasions.'
            ];
        } catch (\Exception $e) {

            Log::error('Item Styling Error', [
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ]);
            return ['error' => 'Failed to get styling suggestions.'];
        }
    }

    private function getAiStyleSuggestions(Item $item, $preferences, $webSiteNames = [])
    {
        try {
            $prompt = $this->buildSimpleStylePrompt($item, $preferences, $webSiteNames);

            $response = Http::withToken(config('services.openai.api_key'))
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a fashion stylist. Provide concise styling suggestions and shopping recommendations. Always return valid JSON format with short, practical advice.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => 500,
                    'temperature' => 0.7,
                    'response_format' => ['type' => 'json_object']
                ]);

            if (!$response->successful()) {
                Log::error('AI Style Suggestion Error', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return $this->createFallbackSuggestion($item, $webSiteNames);
            }

            $result = $response->json();
            $aiResponse = $result['choices'][0]['message']['content'];

            return $this->parseSimpleSuggestions($aiResponse, $item, $webSiteNames);
        } catch (\Exception $e) {
            Log::error('AI Style Processing Error', [
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ]);
            return $this->createFallbackSuggestion($item, $webSiteNames);
        }
    }

    private function buildSimpleStylePrompt(Item $item, $preferences, $webSiteNames = [])
    {
        $prompt = "ITEM DETAILS:\n";
        $prompt .= "Name: " . ($item->item_name ?? 'Clothing Item') . "\n";
        $prompt .= "Type: " . ($item->clouth_type ?? 'Clothing') . "\n";
        $prompt .= "Color: " . ($item->color ?? 'Not specified') . "\n";
        $prompt .= "Material: " . ($item->material ?? 'Not specified') . "\n";
        $prompt .= "Pattern: " . ($item->pattern ?? 'Plain') . "\n";
        $prompt .= "Season: " . ($item->season ?? 'All seasons') . "\n";

        $prompt .= "\nUSER STYLE: " . ($preferences['type'] ?? 'Mixed Style') . "\n";

        if (!empty($preferences['details'])) {
            $prompt .= "Style Details: " . $preferences['details'] . "\n";
        }

        if (!empty($webSiteNames)) {
            $prompt .= "\nAVAILABLE SHOPPING WEBSITES:\n";
            foreach ($webSiteNames as $siteName) {
                $prompt .= "- " . $siteName . "\n";
            }
        }

        $prompt .= "\nTASK: Provide styling suggestion (50-60 words), suggest where to buy similar items (buying_info), and provide 3-5 shopping website links from the available websites list (site_links). Make item name more attractive if needed.\n\n";

        $prompt .= "JSON FORMAT:\n";
        $prompt .= '{
                "styling_suggestion": "50-60 word styling tip",
                "item_name": "More attractive name if needed, or keep original",
                "buying_info": "Where to find similar items (stores/websites)",
                "site_links": [
                    "https://example-shopping-site1.com",
                    "https://example-shopping-site2.com",
                    "https://example-shopping-site3.com"
                ]
            }';

        return $prompt;
    }

    private function parseSimpleSuggestions($aiResponse, Item $item, $webSiteNames = [])
    {
        try {
            $suggestions = json_decode($aiResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON Parse Error', [
                    'error' => json_last_error_msg()
                ]);
                return $this->createFallbackSuggestion($item, $webSiteNames);
            }

            $aiLinks = $suggestions['site_links'] ?? [];


            $finalSiteLinks = $this->mergeSiteLinks($aiLinks, $webSiteNames);

            return [
                'styling_suggestion' => $suggestions['styling_suggestion'] ?? 'Style this versatile piece with complementary colors and accessories for different occasions.',
                'item_name' => $suggestions['item_name'] ?? $item->item_name,
                'buying_info' => $suggestions['buying_info'] ?? 'Available at popular fashion retailers and online stores.',
                'site_links' => $finalSiteLinks
            ];
        } catch (\Exception $e) {
            Log::error('Suggestion Parsing Error', [
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ]);
            return $this->createFallbackSuggestion($item, $webSiteNames);
        }
    }

    private function mergeSiteLinks($aiLinks, $webSiteNames)
    {
        $mergedLinks = [];

        if (is_array($aiLinks)) {
            foreach ($aiLinks as $link) {
                if (filter_var($link, FILTER_VALIDATE_URL)) {
                    $mergedLinks[] = $link;
                }
            }
        }

        if (!empty($webSiteNames)) {
            foreach ($webSiteNames as $siteName) {

                if (filter_var($siteName, FILTER_VALIDATE_URL)) {
                    $mergedLinks[] = $siteName;
                } else {
                    $url = $this->createWebsiteUrl($siteName);
                    if ($url) {
                        $mergedLinks[] = $url;
                    }
                }
            }
        }


        $mergedLinks = array_unique($mergedLinks);
        $mergedLinks = array_values($mergedLinks);

        if (count($mergedLinks) < 5) {
            $fallbackSites = $this->getFallbackSites();
            foreach ($fallbackSites as $site) {
                if (count($mergedLinks) >= 7) break;
                if (!in_array($site, $mergedLinks)) {
                    $mergedLinks[] = $site;
                }
            }
        }

        return array_slice($mergedLinks, 0, 7);
    }

    private function createWebsiteUrl($siteName)
    {
        $cleanName = strtolower(trim($siteName));
        $cleanName = str_replace(['www.', 'https://', 'http://'], '', $cleanName);

        if (strpos($cleanName, '.') !== false) {
            return 'https://' . $cleanName;
        }
        return 'https://www.' . $cleanName . '.com';
    }

    private function getFallbackSites()
    {
        return [
            'https://www.daraz.com.bd',
            'https://www.chique.com',
            'https://www.bagdoomdigital.com',
            'https://www.pickaboo.com',
            'https://www.ajkerdeal.com',
            'https://www.othoba.com',
            'https://www.rokomari.com'
        ];
    }

    private function createFallbackSuggestion(Item $item, $webSiteNames = [])
    {

        $siteLinks = [];

        if (!empty($webSiteNames)) {
            foreach ($webSiteNames as $siteName) {
                if (filter_var($siteName, FILTER_VALIDATE_URL)) {
                    $siteLinks[] = $siteName;
                } else {
                    $url = $this->createWebsiteUrl($siteName);
                    if ($url) {
                        $siteLinks[] = $url;
                    }
                }
            }
        }

        if (count($siteLinks) < 5) {
            $fallbackSites = $this->getFallbackSites();
            foreach ($fallbackSites as $site) {
                if (count($siteLinks) >= 7) break;
                if (!in_array($site, $siteLinks)) {
                    $siteLinks[] = $site;
                }
            }
        }

        $buyingInfo = 'Available at ';
        if (!empty($webSiteNames)) {
            $siteNames = array_slice($webSiteNames, 0, 3);
            $buyingInfo .= implode(', ', $siteNames) . ' and other popular fashion retailers.';
        } else {
            $buyingInfo .= 'Daraz, Chique, AjkerDeal, and other popular Bangladeshi fashion retailers.';
        }

        return [
            'styling_suggestion' => 'This versatile piece can be styled casually or formally depending on the occasion. Pair with complementary colors and textures to create different looks. Layer strategically for seasonal transitions and accessorize thoughtfully to enhance your personal style.',
            'item_name' => $item->item_name,
            'buying_info' => $buyingInfo,
            'site_links' => array_slice($siteLinks, 0, 7)
        ];
    }


    private function validateSiteLinks($siteLinks)
    {
        if (!is_array($siteLinks)) {
            return $this->getFallbackSites();
        }

        $validLinks = array_filter($siteLinks, function ($link) {
            return filter_var($link, FILTER_VALIDATE_URL);
        });

        if (empty($validLinks)) {
            return $this->getFallbackSites();
        }

        $validLinks = array_values($validLinks);

        if (count($validLinks) < 5) {
            $fallbackSites = $this->getFallbackSites();
            foreach ($fallbackSites as $site) {
                if (count($validLinks) >= 7) break;
                if (!in_array($site, $validLinks)) {
                    $validLinks[] = $site;
                }
            }
        }

        return array_slice($validLinks, 0, 7);
    }
}
