<?php

namespace App\Http\Controllers\Api\Backend;

use App\Models\ApiHit;
use App\Models\ChatHistory;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\OpenAiChatService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class OpenAiChatController extends Controller
{
    use ApiResponse;

    protected OpenAiChatService $openAiChatService;

    public function __construct(OpenAiChatService $openAiChatService)
    {
        $this->openAiChatService = $openAiChatService;
    }

    public function openAiChat(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = auth('api')->user();

            if (!$user) {
                return $this->error([], 'User not authenticated', 401);
            }

            $validator = Validator::make($request->all(), [
                'prompt' => 'nullable|string|max:2000',
                'image' => 'nullable|mimetypes:image/*|max:10240',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 'Validation Error', 422);
            }

            $prompt = $request->input('prompt');
            $uploadedImagePath = null;

            // Get conversation history
            $conversationHistory = ChatHistory::getRecentContext($user->id, 10);

            // === CASE 1: Upload Image for Analysis ===
            if ($request->hasFile('image')) {

                $publicPath = public_path('uploads/images/chat');
                if (!File::exists($publicPath)) {
                    File::makeDirectory($publicPath, 0755, true);
                }

                $imageName = 'chat_' . time() . '_' . $user->id . '_' . Str::random(5) . '.' . $request->image->extension();
                $request->image->move($publicPath, $imageName);
                $uploadedImagePath = 'uploads/images/chat/' . $imageName;

                ChatHistory::create([
                    'user_id' => $user->id,
                    'prompt' => $prompt ?? 'Uploaded an image',
                    'image_path' => $uploadedImagePath,
                    'role' => 'user',
                ]);

                // Image analysis with user's style profile
                $chatResponse = $this->openAiChatService->getImageAnalysisResponse(
                    $user->id,
                    $prompt ?? 'Analyze this image and tell me if it matches my personal style. Give styling suggestions.',
                    public_path($uploadedImagePath),
                    $conversationHistory
                );

                if (!$chatResponse['success']) {
                    DB::rollBack();
                    return $this->error('Failed to analyze image', $chatResponse['response'] ?? '', 500);
                }

                $chatHistory = ChatHistory::create([
                    'user_id' => $user->id,
                    'response' => $chatResponse['response'],
                    'response_type' => $chatResponse['response_type'] ?? 'text',
                    'role' => 'assistant',
                ]);

                ApiHit::create(['user_id' => $user->id, 'success' => true]);
                DB::commit();

                return $this->success([
                    'type' => 'image_analysis',
                    'prompt' => $prompt,
                    'response' => $chatResponse['response'],
                    'image_url' => asset($uploadedImagePath),
                    'created_at' => $chatHistory->created_at->diffForHumans()
                ], 'Image analyzed successfully.', 200);
            }

            // === CASE 2: Text Chat (with advanced AI-powered detection) ===
            if ($prompt) {

                ChatHistory::create([
                    'user_id' => $user->id,
                    'prompt' => $prompt,
                    'role' => 'user',
                ]);

                // Advanced image generation intent detection
                $detectionResult = $this->detectImageGenerationIntent($prompt, $conversationHistory);

                if ($detectionResult['wants_image']) {
                    Log::info("Image generation detected", [
                        'confidence' => $detectionResult['confidence'],
                        'is_modification' => $detectionResult['is_modification'],
                        'prompt' => $prompt
                    ]);

                    // Generate image with enhanced prompt
                    $enhancedPrompt = $detectionResult['is_modification'] && $detectionResult['last_image']
                        ? $this->buildModificationPrompt($prompt, $detectionResult['last_image'])
                        : $prompt;

                    $imageResult = $this->openAiChatService->generateImage($user->id, $enhancedPrompt);

                    if ($imageResult['success']) {
                        // Generate dynamic response based on the action
                        $response = $this->generateDynamicImageResponse(
                            $prompt,
                            $detectionResult['is_modification'],
                            $imageResult['revised_prompt'] ?? $prompt
                        );

                        $chatHistory = ChatHistory::create([
                            'user_id' => $user->id,
                            'response' => $response,
                            'response_type' => 'image',
                            'image_path' => $imageResult['image_path'],
                            'image_description' => $imageResult['revised_prompt'] ?? $prompt,
                            'role' => 'assistant',
                        ]);

                        ApiHit::create(['user_id' => $user->id, 'success' => true]);
                        DB::commit();

                        return $this->success([
                            'type' => 'generated_image',
                            'prompt' => $prompt,
                            'response' => $response,
                            'image_url' => $imageResult['image_url'],
                            'revised_prompt' => $imageResult['revised_prompt'] ?? null,
                            'detection_confidence' => $detectionResult['confidence'],
                            'is_modification' => $detectionResult['is_modification'],
                            'created_at' => $chatHistory->created_at->diffForHumans()
                        ], 'Image generated successfully.', 200);
                    }
                }

                // Regular personalized chat
                $chatResponse = $this->openAiChatService->getChatResponse(
                    $user->id,
                    $prompt,
                    $conversationHistory
                );

                if (!$chatResponse['success']) {
                    DB::rollBack();
                    return $this->error('Failed to get response', $chatResponse['response'] ?? '', 500);
                }

                $chatHistory = ChatHistory::create([
                    'user_id' => $user->id,
                    'response' => $chatResponse['response'],
                    'response_type' => $chatResponse['response_type'] ?? 'text',
                    'role' => 'assistant',
                ]);

                ApiHit::create(['user_id' => $user->id, 'success' => true]);
                DB::commit();

                return $this->success([
                    'type' => 'text_chat',
                    'prompt' => $prompt,
                    'response' => $chatResponse['response'],
                    'created_at' => $chatHistory->created_at->diffForHumans()
                ], 'AI response generated successfully.', 200);
            }

            DB::rollBack();
            return $this->error('Please provide a prompt or image', 'Validation Error', 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('OpenAiChatController@openAiChat Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->error('An error occurred while processing your request.', $e->getMessage(), 500);
        }
    }

    /**
     * Generate dynamic response based on the type of image generation
     */
    protected function generateDynamicImageResponse(string $prompt, bool $isModification, string $revisedPrompt): string
    {
        $lowerPrompt = strtolower($prompt);

        if ($isModification) {
            // Modification responses
            if (preg_match('/(change|make|turn).*(red|blue|black|white|green|pink|yellow|purple|orange)/i', $prompt, $matches)) {
                $color = $matches[2] ?? 'the requested color';
                return "Perfect! I've recreated the outfit with a <strong>{$color}</strong> color scheme. Check out the updated look!";
            }

            if (preg_match('/(more|make it).*(formal|casual|elegant|modest|bold|trendy)/i', $prompt, $matches)) {
                $style = $matches[2] ?? 'the new style';
                return "Great choice! I've transformed the outfit to be more <strong>{$style}</strong>. Here's your updated look!";
            }

            if (preg_match('/different|another|new/i', $prompt)) {
                return "Here you go! I've created a fresh variation with your requested changes. What do you think?";
            }

            return "I've updated the outfit with your modifications! Take a look at the new design.";
        }

        // New generation responses based on content
        if (preg_match('/dress|gown|evening|formal wear/i', $prompt)) {
            return "I've designed an <strong>elegant outfit</strong> for you! This dress captures your personal style beautifully.";
        }

        if (preg_match('/casual|everyday|comfortable/i', $prompt)) {
            return "Here's a <strong>stylish casual outfit</strong> tailored to your taste! Perfect for everyday wear.";
        }

        if (preg_match('/party|celebration|event/i', $prompt)) {
            return "I've created a <strong>stunning party outfit</strong> for you! This will definitely turn heads at your event.";
        }

        if (preg_match('/traditional|cultural|ethnic/i', $prompt)) {
            return "I've generated a beautiful <strong>traditional outfit</strong> that honors cultural elegance while matching your style!";
        }

        if (preg_match('/winter|warm|coat|jacket/i', $prompt)) {
            return "Here's a <strong>cozy yet fashionable winter outfit</strong> designed just for you!";
        }

        if (preg_match('/summer|light|breezy/i', $prompt)) {
            return "I've created a fresh <strong>summer look</strong> that's both stylish and comfortable for warm weather!";
        }

        // Default responses with variety
        $defaultResponses = [
            "I've created a <strong>stunning outfit</strong> based on your personal style! What do you think?",
            "Here's your <strong>personalized fashion look</strong>! I've tailored it to match your unique style preferences.",
            "Check out this outfit I've designed for you! It perfectly captures your <em>fashion sense</em>.",
            "I've generated a <strong>beautiful look</strong> that aligns with your style profile. Hope you love it!",
            "Your <strong>customized outfit</strong> is ready! I've incorporated your style preferences into this design."
        ];

        return $defaultResponses[array_rand($defaultResponses)];
    }

    /**
     * Build modification prompt by referencing previous image
     */
    protected function buildModificationPrompt(string $userRequest, array $lastImage): string
    {
        $baseDescription = $lastImage['description'] ?? $lastImage['original_prompt'] ?? '';

        // Extract the modification intent
        return $baseDescription . '. ' . $userRequest;
    }

    /**
     * Enhanced detection with better modification tracking
     */
    protected function detectImageGenerationIntent(string $prompt, array $conversationHistory = []): array
    {
        $prompt = trim($prompt);
        $lowerPrompt = strtolower($prompt);
        $confidenceScore = 0;
        $isModification = false;
        $lastImage = null;

        // Check for last generated image
        $recentMessages = array_slice($conversationHistory, -5);
        foreach ($recentMessages as $msg) {
            if (isset($msg['role']) && $msg['role'] === 'assistant' &&
                isset($msg['content']) && preg_match('/generated|created.*image/i', $msg['content'])) {
                $isModification = true;
                break;
            }
        }

        // Get actual last image from database if modification detected
        if ($isModification) {
            $lastImage = ChatHistory::getLastGeneratedImage(auth('api')->id());
        }

        // CRITICAL: Modification patterns with HIGH priority
        $modificationPatterns = [
            '/^(change|modify|update|alter|adjust)\s+(the|it|that)\s+/i' => 95,
            '/^(change|make|turn)\s+.*(color|colour|style|fabric|pattern)/i' => 95,
            '/^(make|turn)\s+it\s+(red|blue|black|white|green|pink|yellow|purple|orange)/i' => 95,
            '/(different|another|new)\s+(color|colour|style|version)/i' => 90,
            '/more\s+(formal|casual|elegant|modest|bold)/i' => 88,
            '/with\s+(red|blue|black|white|green|pink|yellow|purple|orange)\s+/i' => 92,
            '/please\s+(change|regenerate|recreate)/i' => 93,
        ];

        // If modification patterns match AND there's a recent image
        if ($isModification && $lastImage) {
            foreach ($modificationPatterns as $pattern => $score) {
                if (preg_match($pattern, $prompt)) {
                    return [
                        'wants_image' => true,
                        'confidence' => $score,
                        'is_modification' => true,
                        'last_image' => $lastImage
                    ];
                }
            }
        }

        // Layer 1: Explicit commands - FIXED REGEX
        $explicitCommands = [
            '/^\/generate/i' => 100,
            '/^\/create/i' => 100,
            '/^\/image/i' => 100,
            '/^\/draw/i' => 100,
        ];

        foreach ($explicitCommands as $command => $score) {
            if (preg_match($command, $lowerPrompt)) {
                return ['wants_image' => true, 'confidence' => $score, 'is_modification' => false, 'last_image' => null];
            }
        }

        // Layer 2: Direct image generation requests
        $directPatterns = [
            '/^(generate|create|make|show|draw|design)\s+(an?\s+)?(image|picture|outfit|look)/i' => 92,
            '/^can\s+you\s+(generate|create|make|show)/i' => 88,
            '/^i\s+want\s+(to\s+see|an?)\s+/i' => 85,
        ];

        foreach ($directPatterns as $pattern => $score) {
            if (preg_match($pattern, $prompt)) {
                $confidenceScore = max($confidenceScore, $score);
            }
        }

        // Layer 3: Bengali support
        $bengaliPatterns = [
            '/(ছবি|ইমেজ)\s+(বানাও|তৈরি|দেখাও)/u' => 90,
        ];

        foreach ($bengaliPatterns as $pattern => $score) {
            if (preg_match($pattern, $prompt)) {
                $confidenceScore = max($confidenceScore, $score);
            }
        }

        // Negative patterns - reduce confidence
        $negativePatterns = [
            '/^(what|how|why|tell|explain|describe)\s+/i' => -25,
            '/(advice|suggestion|recommend|help|tips|guide)/i' => -20,
            '/^(can|could|would)\s+you\s+(explain|tell|describe)/i' => -25,
        ];

        foreach ($negativePatterns as $pattern => $penalty) {
            if (preg_match($pattern, $prompt)) {
                $confidenceScore = max(0, $confidenceScore + $penalty);
            }
        }

        $wantsImage = $confidenceScore >= 75;

        return [
            'wants_image' => $wantsImage,
            'confidence' => $confidenceScore,
            'is_modification' => false,
            'last_image' => null
        ];
    }

    public function openAiChatHistory(Request $request)
    {
        try {
            $user = auth('api')->user();
            $history = ChatHistory::where('user_id', $user->id)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($history->isEmpty()) {
                return $this->success([], 'No chat history found.', 200);
            }

            $formatted = $history->map(function ($item) {
                $message = $item->role === 'user' ? $item->prompt : $item->response;

                // If the message contains HTML formatting, keep it as is
                // Otherwise, apply basic formatting
                if ($item->role === 'assistant' && !preg_match('/<[^>]+>/', $message)) {
                    $message = $this->applyBasicFormatting($message);
                }

                return [
                    'id' => $item->id,
                    'role' => $item->role,
                    'message' => $message,
                    'response_type' => $item->response_type ?? 'text',
                    'image_url' => $item->image_path ? asset($item->image_path) : null,
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                    'time_ago' => $item->created_at->diffForHumans(),
                ];
            });

            return $this->success($formatted, 'Chat history retrieved successfully.', 200);
        } catch (\Exception $e) {
            Log::error('OpenAiChatController@openAiChatHistory Error: ' . $e->getMessage());
            return $this->error('Failed to retrieve chat history.', $e->getMessage(), 500);
        }
    }

    /**
     * Apply basic formatting to plain text responses
     */
    protected function applyBasicFormatting(string $text): string
    {
        // Convert line breaks to <br>
        $text = nl2br($text);

        // Convert **bold** to <strong>
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);

        // Convert *italic* to <em>
        $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text);

        return $text;
    }

    public function reuseImageAnalysis(Request $request, $historyId)
    {
        try {
            $user = auth('api')->user();
            $history = ChatHistory::where('user_id', $user->id)->findOrFail($historyId);

            if (!$history->image_description) {
                return $this->error('No image description available', 'Invalid request', 400);
            }

            $prompt = $request->input('prompt', 'Tell me more about this fashion item');
            $conversationHistory = ChatHistory::getRecentContext($user->id, 10);
            $fullPrompt = "Previous image description: \n" . $history->image_description . "\n\n" . $prompt;

            $chatResponse = $this->openAiChatService->getChatResponse(
                $user->id,
                $fullPrompt,
                $conversationHistory
            );

            if (!$chatResponse['success']) {
                return $this->error('AI request failed', $chatResponse['response'] ?? '', 500);
            }

            ChatHistory::create([
                'user_id' => $user->id,
                'response' => $chatResponse['response'],
                'response_type' => $chatResponse['response_type'] ?? 'text',
                'role' => 'assistant',
            ]);

            return $this->success([
                'prompt' => $prompt,
                'response' => $chatResponse['response'],
                'image_url' => $history->image_path ? asset($history->image_path) : null,
            ], 'AI response generated.', 200);
        } catch (\Exception $e) {
            Log::error('OpenAiChatController@reuseImageAnalysis Error: ' . $e->getMessage());
            return $this->error('Image reuse failed', $e->getMessage(), 500);
        }
    }
}
