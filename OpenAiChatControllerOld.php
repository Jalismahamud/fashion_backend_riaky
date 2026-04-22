<?php

// namespace App\Http\Controllers\Api\Backend;

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

class OpenAiChatControllerOld extends Controller
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
                'image'  => 'nullable|mimetypes:image/*|max:10240',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 'Validation Error', 422);
            }

            $prompt = $request->input('prompt');
            $imagePath = null;
            $imageDescription = null;

            if ($request->hasFile('image')) {
                $publicPath = public_path('images/chat');
                if (!File::exists($publicPath)) {
                    File::makeDirectory($publicPath, 0755, true);
                }

                $imageName = 'chat_' . time() . '_' . $user->id . '_' . Str::random(5) . '.' . $request->image->extension();
                $request->image->move($publicPath, $imageName);

                $imagePath = 'images/chat/' . $imageName;
            }

            $chatResponse = $imagePath
                ? $this->openAiChatService->getImageAnalysisResponse($user->id, $prompt ?? 'Describe this image in detail.', public_path($imagePath))
                : $this->openAiChatService->getChatResponse($user->id, $prompt);

            if (!$chatResponse['success']) {
                $error = $chatResponse['error'] ?? 'Unknown error';
                Log::error('OpenAI API failed: ' . $error);

                DB::rollBack();
                return $this->error('Failed to retrieve a valid response.', $chatResponse['response'] ?? '', 500);
            }

            $imageDescription = $imagePath ? $chatResponse['response'] : null;

            $chatHistory = ChatHistory::create([
                'user_id' => $user->id,
                'prompt' => $prompt,
                'response' => $chatResponse['raw'] ?? $chatResponse['response'],
                'response_type' => $chatResponse['response_type'] ?? 'text',
                'image_path' => $imagePath,
                'image_description' => $imageDescription,
            ]);

            ApiHit::create([
                'user_id' => $user->id,
                'success' => true,
            ]);

            DB::commit();

            return $this->success([
                'prompt' => $prompt,
                'response' => $chatResponse['response'],
                'response_type' => $chatResponse['response_type'] ?? 'text',
                'table_data' => $chatResponse['table_data'] ?? null,
                'list_data' => $chatResponse['list_data'] ?? null,
                'image_url' => $imagePath ? asset($imagePath) : null,
                'image_description' => $imageDescription,
                'created_at' => $chatHistory->created_at->diffForHumans()
            ], 'AI response generated successfully.', 200);
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error('OpenAiChatController@openAiChat Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->error('An error occurred while processing your request.', $e->getMessage(), 500);
        }
    }


    public function openAiChatHistory(Request $request)
    {
        try {
            $user = auth('api')->user();
            $history = ChatHistory::where('user_id', $user->id)->get();

            dd($history);

            if ($history->isEmpty()) {
                return $this->success([], 'No chat history found.', 200);
            }

            $history = $history->flatMap(function ($item) {
                return [
                    [
                        'id' => (int)$item->id + rand(1000, 9999),
                        'sender' => 'user',
                        'sender_message' => $item->prompt,
                        'image_url' => $item->image_path ? asset($item->image_path) : null,
                    ],
                    [
                        'id' => $item->id,
                        'sender' => 'ai',
                        'sender_message' => $item->response,
                        'sender_created_at' => $item->created_at->format('Y-m-d H:i:s'),
                    ]
                ];
            });


            return $this->success($history, 'Chat history retrieved successfully.', 200);
        } catch (\Exception $e) {

            Log::error('OpenAiChatController@openAiChatHistory Error: ' . $e->getMessage());
            return $this->error('Failed to retrieve chat history.', $e->getMessage(), 500);
        }
    }

    public function reuseImageAnalysis(Request $request, $historyId)
    {
        try {
            $user = auth('api')->user();
            $history = ChatHistory::where('user_id', $user->id)
                ->findOrFail($historyId);

            if (!$history->image_description) {
                return $this->error('No image description available', 'Invalid request', 400);
            }

            $prompt = $request->input('prompt', 'Tell me more about this fashion item');


            $fullPrompt = "Previous image description: \n" . $history->image_description . "\n\n" . $prompt;

            $chatResponse = $this->openAiChatService->getChatResponse(
                $user->id,
                $fullPrompt
            );

            if (!$chatResponse['success']) {
                return $this->error('AI request failed', $chatResponse['response'] ?? '', 500);
            }

            $newHistory = ChatHistory::create([
                'user_id' => $user->id,
                'prompt' => $prompt,
                'response' => $chatResponse['raw'] ?? $chatResponse['response'],
                'response_type' => $chatResponse['response_type'] ?? 'text',
                'image_path' => $history->image_path,
                'image_description' => $history->image_description,
            ]);

            return $this->success([
                'prompt' => $prompt,
                'response' => $chatResponse['response'],
                'previous_image_description' => $history->image_description,
                'image_url' => $history->image_path ? asset($history->image_path) : null,
                'created_at' => $newHistory->created_at->diffForHumans()
            ], 'AI response generated from image description.', 200);
        } catch (\Exception $e) {

            Log::error('OpenAiChatController@reuseImageAnalysis Error: ' . $e->getMessage());
            return $this->error('Image reuse failed', $e->getMessage(), 500);
        }
    }
}
