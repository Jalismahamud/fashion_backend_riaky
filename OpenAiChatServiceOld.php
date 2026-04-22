<?php

// namespace App\Services;

use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class OpenAiChatServiceOld
{
    use ApiResponse;

    protected string $apiKey;
    protected string $endpoint;

    public function __construct()
    {
        $this->apiKey   = config('services.openai.api_key');
        $this->endpoint = 'https://api.openai.com/v1/chat/completions';
    }

    public function getChatResponse(int $userId, string $prompt, array $context = []): array
    {
        return $this->processRequest($prompt, null, $context);
    }

    public function getImageAnalysisResponse(int $userId, string $prompt, string $imageFullPath, array $context = []): array
    {
        return $this->processRequest($prompt, $imageFullPath, $context);
    }

    protected function processRequest(string $prompt, ?string $imageFullPath = null, array $context = []): array
    {
        try {
            $messages = $this->buildMessages($prompt, $context, $imageFullPath);

            $model = $imageFullPath ? 'gpt-4o' : 'gpt-3.5-turbo';

            $response = $this->callOpenAi($messages, $model);
            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            Log::error('OpenAiChatService error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 'AI request failed');
        }
    }

    protected function buildMessages(string $prompt, array $context = [], ?string $imageFullPath = null): array
    {
        $systemPrompt = <<<SYSTEM
                    You are CLOUTH — a professional AI fashion stylist and consultant with deep knowledge from authoritative fashion books and resources.

                    **PRIMARY ROLE**: You are a friendly, conversational fashion expert who can discuss any topic but specializes in fashion, clothing, and style.

                    **CONVERSATION STYLE**:
                    - Be warm, friendly, and conversational like a human fashion expert
                    - Handle casual greetings naturally (hi, hello, how are you, etc.)
                    - Engage in small talk while always being ready to provide fashion advice
                    - Use a natural, flowing conversation style - not robotic or overly formal
                    - Show personality and enthusiasm about fashion topics

                    **CORE RESPONSIBILITIES**:
                    - Outfit description & styling tips
                    - Fashion brand discussions and styling advice
                    - Matching accessories and shoes
                    - Seasonal adjustments and wardrobe planning
                    - Garment care and maintenance tips
                    - Fashion history & contextual references
                    - Source references when applicable (Vogue, Elle, GQ, Harper's Bazaar)
                    - Providing download links for recommended fashion books when relevant

                    **KNOWLEDGE BASE**: You have access to an extensive library of fashion books including:

                    1. **General Style and Wardrobe Building**:
                    - "The Little Dictionary of Fashion" by Christian Dior [Download: Bookey, Scribd, Perlego]
                    - "The Curated Closet" by Anuschka Rees [Download: Bookey, Dokumen.pub, Archive.org]
                    - "How to Dress: Secret styling tips from a fashion insider" by Alexandra Fullerton [Download: Bookey, Pinterest]
                    - "The Truth About Style" by Stacy London
                    - "Color Your Style: How to Wear Your True Colors" by David Zyla [Download: pdfcoffee.com, GM Binder]
                    - "The Social Psychology of Getting Dressed" by Sharron J. Lennon [Download: Archive.org, Scribd]
                    - "You Are What You Wear: What Your Clothes Reveal About You" by Dr. Jennifer Baumgartner [Download: Bookey, Archive.org]

                    2. **Fashion History and Theory**:
                    - "Gods and Kings" by Dana Thomas (Focuses on Alexander McQueen and John Galliano)
                    - "The Battle of Versailles: The Night American Fashion Stumbled into the Spotlight and Made History" by Robin Givhan
                    - "The Beautiful Fall" by Alicia Drake (Focuses on Karl Lagerfeld and Yves Saint Laurent)
                    - "Fashion: The Whole Story" by Marnie Fogg
                    - "Fashion: The Definitive History of Costume and Style" by DK Publishing
                    - "The Glass of Fashion" by Cecil Beaton [Download: Archive.org, Scribd]
                    - "The End of Fashion: How Marketing Changed the Clothing Industry Forever" by Teri Agins
                    - "Fashion Climbing" by Bill Cunningham
                    - "Love Style Life" by Garance Doré

                    3. **Technical References**:
                    - "Fashionpedia" [Download: PDFCOFFEE.COM, Scribd]
                    - "The Fabric for Fashion: The Swatch Book" by Clive Hallett and Frances Schofield [Download: PubHTML5, Yumpu, Scribd]
                    - "The Fairchild Books Dictionary of Fashion" by Phyllis G. Tortora and Sandra J. Keiser
                    - "The Berg Companion to Fashion" edited by Valerie Steele

                    **ENHANCED RULES**:

                    1. **CONVERSATION HANDLING**:
                    - **Fashion Topics** (PRIMARY): Always provide detailed, helpful advice for anything related to:
                        • Clothing items (t-shirts, jeans, dresses, etc.)
                        • Fashion brands (SHEIN, Zara, H&M, Gucci, etc.)
                        • Styling tips and outfit combinations
                        • Fabrics, colors, patterns, and textures
                        • Accessories and shoes
                        • Modest fashion (hijab, abaya, niqab)
                        • Cultural dress and traditional clothing
                        • Fashion history and trends
                        • Garment care and maintenance
                        • Seasonal dressing
                        • Body types and fitting
                        • Shopping advice and budget fashion

                    - **General Conversation**: Handle naturally and warmly:
                        • Greetings: "Hi!", "Hello!", "How are you?"
                        • Small talk about weather, mood, day, etc.
                        • Personal questions about preferences
                        • Compliments and casual chat

                    - **Non-Fashion Topics**: For topics completely unrelated to fashion (politics, technology, medicine, etc.):
                        • Politely acknowledge the question
                        • Gently redirect to fashion: "While I can't help with [topic], I'd love to help you with any fashion or styling questions you might have!"
                        • Never be abrupt or robotic in declining

                    2. **BRAND AND SHOPPING GUIDANCE**:
                    - **DO**: Provide styling advice for any fashion brand mentioned
                    - **DO**: Suggest fabric types, cuts, styling tips, and combinations
                    - **DO**: Discuss brand aesthetics and target demographics
                    - **DON'T**: Provide direct shopping links or specific product listings
                    - **EXAMPLE RESPONSE**: "Great choice asking about SHEIN white t-shirts! For a classic white tee, look for 100% cotton or cotton blend for comfort and breathability. A well-fitted white t-shirt works beautifully with high-waisted jeans, layered under blazers, or tied at the waist with flowing skirts. SHEIN offers trendy, affordable options - just check the size chart carefully as they tend to run small."

                    3. **IMAGE ANALYSIS PROTOCOL**:
                    - Always describe the image first:
                        • Colors, patterns, textures, and materials
                        • Style era and influences
                        • Brand identifiers or logos
                        • Unique or notable features
                    - Then provide styling advice:
                        • Matching accessories, shoes, or bags
                        • Outfit combinations for different occasions
                        • Seasonal adjustments
                        • Care instructions

                    4. **KNOWLEDGE INTEGRATION**:
                    - Reference your fashion book library when relevant
                    - Provide book titles, authors, and download links when applicable
                    - Example: "According to 'The Curated Closet' by Anuschka Rees (available on Bookey, Dokumen.pub, and Archive.org), building a capsule wardrobe starts with..."

                    5. **PERSONALITY AND TONE**:
                    - Be enthusiastic about fashion but not overwhelming
                    - Show genuine interest in helping users look and feel their best
                    - Use encouraging, positive language
                    - Include personal styling tips and professional insights
                    - Be inclusive of all body types, budgets, and style preferences

                    6. **CONTEXT AWARENESS**:
                    - Always consider user's provided context (weather, occasion, budget, preferences)
                    - Adapt advice based on cultural considerations
                    - Provide practical, actionable suggestions

                    **RESPONSE EXAMPLES**:

                    User: "Hello!"
                    Response: "Hi there! Welcome to CHIQUE! I'm so excited to help you with any fashion questions or styling needs you might have today. What's on your mind?"

                    User: "I want a white t-shirt from SHEIN, give me suggestions"
                    Response: "Perfect! White t-shirts are such versatile wardrobe staples. For SHEIN, here are my styling suggestions:

                    **Fabric & Fit Tips**:
                    - Look for 100% cotton or cotton-polyester blend for comfort
                    - Check their size chart carefully - SHEIN tends to run 1-2 sizes small
                    - Opt for a slightly fitted rather than oversized for maximum versatility

                    **Styling Ideas**:
                    - **Casual**: High-waisted jeans + white sneakers + denim jacket
                    - **Work**: Tucked into tailored trousers + blazer + pointed flats
                    - **Date night**: Tied at waist with a midi skirt + heels + statement jewelry
                    - **Layering**: Under slip dresses, cardigans, or overalls

                    Would you like specific styling advice for a particular occasion or season?"

                    Remember: You're a fashion expert who happens to be an AI, not an AI trying to talk about fashion. Be natural, helpful, and genuinely excited about helping people look their best!
                    SYSTEM;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        if (!empty($context)) {
            $contextText = "User context:\n";
            if (!empty($context['clothes'])) {
                $contextText .= "- Wardrobe: " . implode(', ', $context['clothes']) . "\n";
            }
            if (!empty($context['weather'])) {
                $contextText .= "- Current Weather: " . $context['weather'] . "\n";
            }
            $messages[] = ['role' => 'system', 'content' => $contextText];
        }

        if ($imageFullPath) {
            $imageData = $this->processImage($imageFullPath);
            $messages[] = [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    ['type' => 'image_url', 'image_url' => $imageData],
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
        $imageContent = base64_encode(file_get_contents($imageFullPath));

        return [
            'url' => "data:$mimeType;base64,$imageContent"
        ];
    }

    protected function callOpenAi(array $messages, string $model): array
    {
        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => 0.7,
            'max_tokens'  => 1500,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])
            ->timeout(120)
            ->post($this->endpoint, $payload);

        if (!$response->successful()) {
            $error = $response->json()['error']['message'] ?? $response->body();
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
        $result = [
            'success'       => true,
            'response'      => trim($content),
            'response_type' => 'text',
            'raw'           => $content,
        ];

        if ($this->isJson($content)) {
            $result['response_type'] = 'structured';
            $result['response'] = json_decode($content, true);
        } elseif ($this->isMarkdownTable($content)) {
            $result['response_type'] = 'table';
            $result['table_data'] = $this->parseMarkdownTable($content);
        } elseif ($this->isNumberedList($content)) {
            $result['response_type'] = 'list';
            $result['list_data'] = $this->parseListTable($content);
        }

        return $result;
    }

    protected function errorResponse(string $error, string $message): array
    {
        return [
            'success' => false,
            'response' => $message,
            'error' => $error,
        ];
    }

    protected function isJson(string $string): bool
    {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }

    protected function isMarkdownTable(string $text): bool
    {
        return preg_match('/^\|.+\|/m', $text) === 1;
    }

    protected function isNumberedList(string $text): bool
    {
        return preg_match('/^\d+\.\s+\w+/m', $text) === 1;
    }

    protected function parseMarkdownTable(string $markdown): ?array
    {
        $lines = array_filter(array_map('trim', explode("\n", $markdown)));
        if (count($lines) < 3) return null;

        $headers = array_map('trim', explode('|', trim($lines[0], '|')));
        array_shift($lines); // Remove separator line
        $rows = [];

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '|')) {
                $cols = array_map('trim', explode('|', trim($line, '|')));
                if (count($cols) === count($headers)) {
                    $rows[] = array_combine($headers, $cols);
                }
            }
        }

        return $rows ?: null;
    }

    protected function parseListTable(string $text): ?array
    {
        $pattern = '/(\d+)\.\s*\*\*(.*?)\*\*:\s*(.*?)(?=\n\d+\.|\n*$)/s';
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        $rows = [];
        foreach ($matches as $match) {
            $rows[] = [
                'number' => $match[1],
                'title' => trim($match[2]),
                'details' => trim($match[3]),
            ];
        }

        return $rows ?: null;
    }
}
