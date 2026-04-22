<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatHistory extends Model
{
    protected $fillable = [
        'user_id',
        'prompt',
        'response',
        'response_type',
        'image_path',
        'image_description',
        'role',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get recent conversation context for AI
     * This builds proper message history for OpenAI API
     */
    public static function getRecentContext(int $userId, int $limit = 10): array
    {
        $messages = self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit * 2) // Get more to ensure we have enough after filtering
            ->get()
            ->reverse()
            ->values();

        $context = [];

        foreach ($messages as $msg) {
            if ($msg->role === 'user') {
                $content = $msg->prompt ?? 'Message';

                // If user uploaded an image for analysis, mention it
                if ($msg->image_path && !empty($msg->image_path)) {
                    // Check if this is an uploaded image (not generated)
                    if (strpos($msg->image_path, 'chat/') !== false) {
                        $content .= ' [User uploaded an image for analysis]';
                    }
                }

                $context[] = [
                    'role' => 'user',
                    'content' => $content
                ];
            } else {
                $content = $msg->response;

                // If this was a generated image, format the context properly
                if ($msg->response_type === 'image' && $msg->image_path) {

                    // Find the previous user message to get original prompt
                    $previousUserMessage = self::where('user_id', $userId)
                        ->where('id', '<', $msg->id)
                        ->where('role', 'user')
                        ->orderBy('id', 'desc')
                        ->first();

                    $originalPrompt = $previousUserMessage ? $previousUserMessage->prompt : 'an outfit';

                    $content = "I generated an image based on your request: '{$originalPrompt}'. ";

                    if (!empty($msg->image_description)) {
                        $content .= "The image shows: " . $msg->image_description . ". ";
                    }

                    $content .= "If you want to modify this image (change colors, styles, patterns, etc.), just let me know what changes you'd like!";
                }

                $context[] = [
                    'role' => 'assistant',
                    'content' => $content
                ];
            }
        }

        // Limit to the specified number of message pairs
        if (count($context) > $limit * 2) {
            $context = array_slice($context, -($limit * 2));
        }

        return $context;
    }

    /**
     * Get user's style profile context for personalized responses
     */
    public static function getUserStyleContext(int $userId): string
    {
        $styleProfile = \App\Helper\Helper::getStyleProfile($userId);

        $answers = \App\Models\StyleQuizAnswer::where('user_id', $userId)
            ->with(['question', 'option'])
            ->get();

        if ($answers->isEmpty()) {
            return "**USER'S STYLE PROFILE:**\nNo style profile available yet. Provide general fashion advice.\n";
        }

        $context = "**USER'S PERSONAL STYLE PROFILE:**\n\n";
        $context .= "✨ Style Type: {$styleProfile['type']}\n";
        $context .= "📝 Style Description: {$styleProfile['details']}\n";
        $context .= "🏷️ Style Keywords: {$styleProfile['keywords']}\n\n";
        $context .= "**Detailed Quiz Answers:**\n";

        foreach ($answers as $answer) {
            $question = $answer->question->question_text ?? 'Question';
            $answerText = $answer->option
                ? $answer->option->option_text
                : ($answer->text_answer ?? 'No answer');

            $context .= "Q: {$question}\n";
            $context .= "A: {$answerText}\n\n";
        }

        $context .= "\n**CRITICAL PERSONALIZATION RULES:**\n";
        $context .= "1. ALWAYS tailor recommendations to match: {$styleProfile['type']}\n";
        $context .= "2. Reference their quiz answers naturally in your advice\n";
        $context .= "3. Use their style keywords when describing outfits: {$styleProfile['keywords']}\n";
        $context .= "4. Respect any modest fashion preferences mentioned\n";
        $context .= "5. Consider their favorite colors, fabrics, and patterns\n";
        $context .= "6. Make every suggestion feel personalized to THEM\n";

        return $context;
    }

    /**
     * Get the last generated image for modifications
     */
    public static function getLastGeneratedImage(int $userId): ?array
    {
        $lastImage = self::where('user_id', $userId)
            ->where('role', 'assistant')
            ->where('response_type', 'image')
            ->whereNotNull('image_path')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastImage) {
            return null;
        }

        // Find the original user prompt
        $originalPrompt = self::where('user_id', $userId)
            ->where('id', '<', $lastImage->id)
            ->where('role', 'user')
            ->orderBy('id', 'desc')
            ->first();

        return [
            'image_path' => $lastImage->image_path,
            'image_url' => asset($lastImage->image_path),
            'description' => $lastImage->image_description ?? '',
            'original_prompt' => $originalPrompt ? $originalPrompt->prompt : '',
            'created_at' => $lastImage->created_at,
        ];
    }

    /**
     * Check if user has recent conversation
     */
    public static function hasRecentConversation(int $userId, int $minutes = 30): bool
    {
        return self::where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->exists();
    }

    /**
     * Get conversation statistics
     */
    public static function getConversationStats(int $userId): array
    {
        $total = self::where('user_id', $userId)->count();
        $images_analyzed = self::where('user_id', $userId)
            ->where('role', 'user')
            ->whereNotNull('image_path')
            ->where('image_path', 'like', '%chat/%')
            ->count();
        $images_generated = self::where('user_id', $userId)
            ->where('role', 'assistant')
            ->where('response_type', 'image')
            ->count();

        return [
            'total_messages' => $total,
            'images_analyzed' => $images_analyzed,
            'images_generated' => $images_generated,
            'last_activity' => self::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->value('created_at'),
        ];
    }
}
