<?php

namespace App\Http\Controllers\Api\Backend;

use Exception;
use App\Models\StyleQuizQuestion;
use App\Models\StyleQuizAnswer;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class ApiStyleQuizController extends Controller
{
    use ApiResponse;


    public function questions()
    {
        try {
            $questions = StyleQuizQuestion::with('options')
                ->get();

            if ($questions->isEmpty()) {
                return $this->success([], 'No quiz questions found.', 200);
            }

            return $this->success($questions, 'Quiz questions retrieved successfully.', 200);
        } catch (Exception $e) {
            Log::error('Quiz Questions Error: ' . $e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }


    public function submitAnswers(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'answers' => 'required|array',
                'answers.*.question_id' => 'required|exists:style_quiz_questions,id',
                'answers.*.option_id' => 'nullable|exists:style_quiz_options,id',
                'answers.*.text_answer' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 422);
            }

            $user = auth('api')->user();

            if (count($request->answers) !== 10) {
                return $this->error([], 'You must answer exactly 10 questions to submit the quiz.', 422);
            }

            DB::beginTransaction();
            foreach ($request->answers as $answer) {
                StyleQuizAnswer::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'question_id' => $answer['question_id'],
                    ],
                    [
                        'option_id' => $answer['option_id'] ?? null,
                        'text_answer' => $answer['text_answer'] ?? null,
                    ]
                );
            }
            DB::commit();

            $data = StyleQuizAnswer::where('user_id', $user->id)
                ->with(['question', 'option'])
                ->get();

            return $this->success($data, 'Answers saved successfully.', 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Quiz Answers Error: ' . $e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }


    public function profile(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->error([], 'User not found.', 404);
            }

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar
            ];

            $styleProfile = \App\Helper\Helper::getStyleProfile($user->id);

            $response = [
                'user' => $userData,
                'style_profile' => $styleProfile,
            ];

            return $this->success($response, 'Style profile, quiz questions, and answers retrieved successfully.', 200);
        } catch (Exception $e) {
            Log::error('Quiz Profile Error: ' . $e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }
}
