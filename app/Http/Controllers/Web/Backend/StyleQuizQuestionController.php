<?php

namespace App\Http\Controllers\Web\Backend;

use Exception;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\StyleQuizOption;
use Yajra\DataTables\DataTables;
use App\Models\StyleQuizQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class StyleQuizQuestionController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */

    protected $maxlimit = 10;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $maxQuestions = 10;
            $currentCount = StyleQuizQuestion::count();
            // If max_check param is present, return only maxReached status
            if ($request->has('max_check')) {
                return response()->json([
                    'maxReached' => $currentCount >= $maxQuestions
                ]);
            }
            $data = StyleQuizQuestion::with('options')->latest('id');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('options', function ($item) {
                    $html = '<button class="btn btn-sm btn-outline-primary toggle-options">Show Options</button>';
                    $html .= '<div class="options-list" style="display:none; margin-top:5px;"><ul style="padding-left:20px;">';
                    foreach ($item->options as $option) {
                        $html .= '<li>' . e($option->option_text) . '</li>';
                    }
                    $html .= '</ul></div>';
                    return $html;
                })
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group">
                          <a href="' . route('admin.quiz.edit', ['id' => $data->id]) . '" class="btn btn-primary text-white" title="Edit">
                          <i class="bi bi-pencil"></i>
                          </a>
                          <a href="#" onclick="showDeleteConfirm(' . $data->id . ')" class="btn btn-danger text-white" title="Delete">
                          <i class="bi bi-trash"></i>
                          </a>
                        </div>';
                })
                ->rawColumns(['options', 'status', 'action'])
                ->make(true);
        }

        return view('backend.layouts.quiz.index');
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.layouts.quiz.create');
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Role check
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to create quiz questions.'
            ], 403);
        }

        // Check max limit
        $maxQuestions = 10;
        $currentCount = StyleQuizQuestion::count();
        if ($currentCount >= $maxQuestions) {
            return response()->json([
                'status' => 'error',
                'message' => 'Maximum number of quiz questions reached.'
            ], 403);
        }

        $validate = $request->validate([
            'question_text' => 'required|string|max:255',
            'status'        => 'nullable|in:0,1',
            'options'       => 'required|array|min:1',
            'options.*'     => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $question = StyleQuizQuestion::create([
                'question_text' => $request->question_text,
                'status'        => $request->status ?? 0,
            ]);

            foreach ($request->options as $optionText) {
                StyleQuizOption::create([
                    'question_id' => $question->id,
                    'option_text' => $optionText,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Quiz Question and options created successfully',
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data = StyleQuizQuestion::with('options')->find($id);

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quiz Question not found.'
            ], 404);
        }

        return view('backend.layouts.quiz.edit', compact('data'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        // Role check
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to create quiz questions.'
            ], 403);
        }

        $validate = $request->validate([
            'question_text' => 'required|string|max:255',
            'options'       => 'required|array|min:1',
            'options.*'     => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Update question
            $question = StyleQuizQuestion::find($id);

            if (!$question) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Quiz Question not found.'
                ], 404);
            }

            $question->update([
                'question_text' => $request->question_text,
                'status'        => $request->status ?? 0,
            ]);

            StyleQuizOption::where('question_id', $question->id)->delete();

            foreach ($request->options as $optionText) {
                StyleQuizOption::create([
                    'question_id' => $question->id,
                    'option_text' => $optionText,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Quiz Question updated successfully',
                'data' => [
                    'question' => $question,
                    'options' => $question->options
                ]
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $data = StyleQuizQuestion::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz Question not found.',
            ], 404);
        }

        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quiz Question deleted successfully!',
        ], 200);
    }

}
