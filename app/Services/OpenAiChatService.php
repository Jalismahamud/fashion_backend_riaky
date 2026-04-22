<?php

namespace App\Services;

use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OpenAiChatService
{
    use ApiResponse;

    protected string $apiKey;
    protected string $chatEndpoint;
    protected string $imageEndpoint;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->chatEndpoint = 'https://api.openai.com/v1/chat/completions';
        $this->imageEndpoint = 'https://api.openai.com/v1/images/generations';
    }

    public function getChatResponse(int $userId, string $prompt, array $conversationHistory = []): array
    {
        return $this->processRequest($userId, $prompt, null, $conversationHistory);
    }

    public function getImageAnalysisResponse(int $userId, string $prompt, string $imageFullPath, array $conversationHistory = []): array
    {
        return $this->processRequest($userId, $prompt, $imageFullPath, $conversationHistory);
    }

    public function generateImage(int $userId, string $prompt): array
    {
        try {
            Log::info('Generating image for user', [
                'user_id' => $userId,
                'prompt' => $prompt
            ]);

            $enhancedPrompt = $this->enhanceImagePrompt($prompt);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(120)
            ->post($this->imageEndpoint, [
                'model' => 'dall-e-3',
                'prompt' => $enhancedPrompt,
                'n' => 1,
                'size' => '1024x1024',
                'quality' => 'standard',
                'response_format' => 'url'
            ]);

            if (!$response->successful()) {
                $error = $response->json()['error']['message'] ?? $response->body();
                Log::error('DALL-E API Error', ['error' => $error]);
                throw new \Exception("DALL-E Error: " . $error);
            }

            $data = $response->json();

            if (!isset($data['data'][0]['url'])) {
                throw new \Exception("No image URL returned from DALL-E");
            }

            $imageUrl = $data['data'][0]['url'];
            $revisedPrompt = $data['data'][0]['revised_prompt'] ?? $prompt;

            Log::info('Image generated successfully', [
                'original_prompt' => $prompt,
                'revised_prompt' => $revisedPrompt
            ]);

            $localPath = $this->downloadAndSaveImage($imageUrl, $userId);

            return [
                'success' => true,
                'image_path' => $localPath,
                'image_url' => asset($localPath),
                'openai_url' => $imageUrl,
                'revised_prompt' => $revisedPrompt,
            ];
        } catch (\Exception $e) {
            Log::error('Image generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'prompt' => $prompt
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Enhance image prompt for better DALL-E results
     */
    protected function enhanceImagePrompt(string $prompt): string
    {
        // Clean the prompt
        $prompt = trim($prompt);

        // Remove command prefixes if any
        $prompt = preg_replace('/^(generate|create|make|show|draw|design)\s+(an?\s+)?(image|picture|outfit|look)\s+(of|with)?\s*/i', '', $prompt);

        // If prompt is very short, add context
        if (str_word_count($prompt) < 5) {
            return "A high-quality, detailed fashion photography of " . $prompt . ", professional lighting, modern style, clean background, full outfit view, realistic";
        }

        // If it's a modification request
        if (preg_match('/(change|make|turn|with)\s+(to\s+)?(black|red|blue|white|green|pink|yellow|purple|orange|brown|grey|gray)/i', $prompt, $matches)) {
            $color = $matches[count($matches) - 1];
            return "Fashion outfit photography: " . $prompt . ", professional styling, full body view, clean background, high quality, modern " . $color . " color scheme";
        }

        // Add quality enhancements if not already present
        $qualityTerms = ['professional', 'high quality', 'detailed', 'realistic'];
        $hasQuality = false;
        foreach ($qualityTerms as $term) {
            if (stripos($prompt, $term) !== false) {
                $hasQuality = true;
                break;
            }
        }

        if (!$hasQuality) {
            return $prompt . ", professional fashion photography, high quality, detailed, realistic";
        }

        return $prompt;
    }

    protected function downloadAndSaveImage(string $imageUrl, int $userId): string
    {
        try {
            $imageContent = Http::timeout(60)->get($imageUrl)->body();

            if (empty($imageContent)) {
                throw new \Exception("Failed to download image from URL");
            }

            $filename = 'generated_' . $userId . '_' . time() . '_' . Str::random(8) . '.png';
            $relativePath = 'uploads/images/generated/' . $filename;
            $publicPath = public_path($relativePath);

            if (!file_exists(dirname($publicPath))) {
                mkdir(dirname($publicPath), 0755, true);
            }

            file_put_contents($publicPath, $imageContent);

            Log::info('Image saved locally', ['path' => $relativePath]);

            return $relativePath;
        } catch (\Exception $e) {
            Log::error('Failed to download and save image', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function processRequest(int $userId, string $prompt, ?string $imageFullPath = null, array $conversationHistory = []): array
    {
        try {
            $styleContext = $this->getUserStyleContext($userId);
            $messages = $this->buildMessages($prompt, $conversationHistory, $imageFullPath, $styleContext);
            $model = $imageFullPath ? 'gpt-4o' : 'gpt-4o-mini';

            $response = $this->callOpenAi($messages, $model);
            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            Log::error('OpenAiChatService error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 'AI request failed');
        }
    }

    protected function getUserStyleContext(int $userId): string
    {
        return Cache::remember("user_style_context_{$userId}", 3600, function () use ($userId) {
            return \App\Models\ChatHistory::getUserStyleContext($userId);
        });
    }

    protected function buildMessages(string $prompt, array $conversationHistory = [], ?string $imageFullPath = null, string $styleContext = ''): array
    {
        $systemPrompt = <<<SYSTEM
You are CLOUTH — an advanced AI fashion stylist with comprehensive fashion expertise and personalized styling capabilities.

**YOUR CORE CAPABILITIES**:
1. ✅ Analyze uploaded fashion images with expert precision - describe EVERY visible detail
2. ✅ Provide personalized outfit recommendations based on user's style profile
3. ✅ Give detailed fashion advice tailored to individual preferences
4. ✅ Discuss colors, fabrics, patterns, and style combinations
5. ✅ Recommend outfit ideas for any occasion, season, or setting
6. ✅ Reference fashion history, trends, and authoritative sources
7. ✅ Offer garment care and maintenance guidance

**CONVERSATION MEMORY**:
- You remember ALL previous messages in this conversation
- When users reference "that outfit", "the image", or "it", you know which one they mean
- You maintain context across multiple exchanges
- You track generated images and can discuss modifications

**PERSONALIZATION PROTOCOL**:
- ALWAYS reference the user's personal style profile when available
- Tailor every suggestion to match their style type and preferences
- Use their quiz answers to inform your recommendations
- Respect their modest fashion preferences if applicable
- Incorporate their favorite colors, fabrics, and keywords

**IMAGE ANALYSIS PROTOCOL** (CRITICAL):
When analyzing uploaded images:
1. Describe EVERY clothing item visible (tops, bottoms, shoes, accessories)
2. Specify exact colors and color combinations
3. Identify fabric types and textures (cotton, silk, denim, wool, etc.)
4. Describe patterns (solid, striped, floral, geometric, etc.)
5. Note the fit and silhouette (fitted, loose, oversized, tailored)
6. Mention any logos, brands, or distinctive features
7. Assess the overall style (casual, formal, sporty, elegant, etc.)
8. Evaluate how it matches the user's personal style profile
9. Suggest improvements, complementary pieces, or alternatives
10. Provide specific styling tips and outfit combinations

**IMPORTANT**: Always be detailed and specific in your descriptions. Never say "I cannot see the image" - if an image is provided, analyze it thoroughly.

**FORMATTING GUIDELINES** (CRITICAL):
- Use markdown formatting for ALL responses
- Use **bold** for emphasis on key terms, headings, and important details
- Use *italic* for subtle emphasis or fashion terminology
- Use numbered lists (1., 2., 3.) for sequential steps or rankings
- Use bullet points (-) for non-sequential items
- Use ### for main section headings
- Use proper line breaks between sections
- Format your responses to be visually organized and easy to read

**CONVERSATION STYLE**:
- Warm, friendly, and conversational like a personal stylist
- Natural flowing dialogue, not robotic responses
- Enthusiastic about fashion without being overwhelming
- Ask clarifying questions when needed
- Show genuine interest in helping users look their best
- Handle casual greetings naturally (hi, hello, how are you)

**RESPONSE QUALITY STANDARDS**:
- Detailed and actionable advice
- Specific brand/fabric/cut recommendations when relevant
- Multiple outfit options when applicable
- Seasonal and occasion-appropriate suggestions
- Budget-conscious alternatives when helpful
- Cultural sensitivity and inclusivity

**EXAMPLE FORMATTED RESPONSE**:

### Modern Hijab Styles

Here are some **contemporary hijab options** that blend *modesty with modern fashion*:

**1. Chiffon Hijab**
- **Fabric**: Lightweight, flowy chiffon
- **Best For**: Formal events and elegant occasions
- **Colors**: Pastels, jewel tones, and vibrant hues
- **Styling Tip**: Perfect for creating soft, draped looks

**2. Silk Hijab**
- **Fabric**: Luxurious silk with natural sheen
- **Best For**: Special occasions and professional settings
- **Colors**: Rich jewel tones, classic neutrals
- **Styling Tip**: Holds shape beautifully without pins

Remember: You're an expert fashion advisor providing thoughtful, personalized, WELL-FORMATTED guidance!
SYSTEM;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add style context if available
        if (!empty($styleContext)) {
            $messages[] = ['role' => 'system', 'content' => $styleContext];
        }

        // Add conversation history with proper structure
        foreach ($conversationHistory as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
        }

        // Add current prompt
        if ($imageFullPath) {
            $imageData = $this->processImage($imageFullPath);
            $messages[] = [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    ['type' => 'image_url', 'image_url' => ['url' => $imageData['url'], 'detail' => 'high']],
                ],
            ];
        } else {
            $messages[] = ['role' => 'user', 'content' => $prompt];
        }

        return $messages;
    }

    protected function processImage(string $imageFullPath): array
    {
        if (!file_exists($imageFullPath)) {
            throw new \Exception("Image file not found: $imageFullPath");
        }

        $mimeType = mime_content_type($imageFullPath);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($mimeType, $allowedTypes)) {
            throw new \Exception("Unsupported image type: $mimeType");
        }

        $imageContent = base64_encode(file_get_contents($imageFullPath));

        return [
            'url' => "data:$mimeType;base64,$imageContent"
        ];
    }

    protected function callOpenAi(array $messages, string $model): array
    {
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 2000,
        ];

        Log::info('Calling OpenAI API', [
            'model' => $model,
            'message_count' => count($messages)
        ]);

        $response = Http::retry(3, 1000)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(120)
            ->post($this->chatEndpoint, $payload);

        if (!$response->successful()) {
            $error = $response->json()['error']['message'] ?? $response->body();
            Log::error('OpenAI API Error', [
                'model' => $model,
                'status' => $response->status(),
                'error' => $error
            ]);
            throw new \Exception("OpenAI API Error ($model): " . $error);
        }

        return $response->json();
    }

    protected function handleApiResponse(array $response): array
    {
        if (empty($response['choices'][0]['message']['content'])) {
            Log::error('OpenAI API empty content: ' . json_encode($response));
            return $this->errorResponse('No content in response', 'Invalid AI response structure');
        }

        $content = $response['choices'][0]['message']['content'];

        // Convert markdown to HTML
        $formattedContent = $this->convertMarkdownToHtml($content);

        return [
            'success' => true,
            'response' => $formattedContent,
            'response_type' => 'text',
            'raw' => $content,
            'formatted' => $formattedContent,
        ];
    }

    /**
     * Convert markdown formatting to HTML
     */
    protected function convertMarkdownToHtml(string $text): string
    {
        // Trim whitespace
        $text = trim($text);

        // Convert ### headers to <h3>
        $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);

        // Convert ## headers to <h2>
        $text = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $text);

        // Convert # headers to <h1>
        $text = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $text);

        // Convert **bold** to <strong>
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);

        // Convert *italic* to <em>
        $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text);

        // Convert numbered lists
        $text = preg_replace_callback('/^(\d+)\.\s+(.+)$/m', function($matches) {
            static $inList = false;
            $number = $matches[1];
            $content = $matches[2];

            if ($number == 1) {
                $inList = true;
                return '<ol><li>' . $content . '</li>';
            } else {
                return '<li>' . $content . '</li>';
            }
        }, $text);

        // Close numbered lists
        $text = preg_replace('/<\/li>\n(?!<li)/', '</li></ol>', $text);

        // Convert bullet points (-, *, +)
        $text = preg_replace_callback('/^[\-\*\+]\s+(.+)$/m', function($matches) {
            static $inList = false;
            $content = $matches[1];

            // Check if this is the start of a new list
            $prevLine = '';
            if (!$inList) {
                $inList = true;
                return '<ul><li>' . $content . '</li>';
            } else {
                return '<li>' . $content . '</li>';
            }
        }, $text);

        // Close bullet lists
        $text = preg_replace('/<\/li>\n(?!<li)/', '</li></ul>', $text);

        // Convert line breaks to <br> (but not after headings or list items)
        $text = preg_replace('/(?<!>)\n(?!<[\/]?(h|li|ul|ol))/', '<br>', $text);

        // Clean up extra <br> tags around block elements
        $text = preg_replace('/<br>\s*(<\/?(?:h[1-6]|ul|ol|li)>)/', '$1', $text);
        $text = preg_replace('/(<\/?(?:h[1-6]|ul|ol|li)>)\s*<br>/', '$1', $text);

        // Remove multiple consecutive <br> tags
        $text = preg_replace('/(<br\s*\/?>\s*){3,}/', '<br><br>', $text);

        return $text;
    }

    protected function errorResponse(string $error, string $message): array
    {
        return [
            'success' => false,
            'response' => $message,
            'error' => $error,
            'formatted' => $message,
        ];
    }
}
