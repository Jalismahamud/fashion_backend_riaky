<?php

namespace App\Http\Controllers\Api\Backend;

use Exception;
use App\Models\ApiHit;
use GuzzleHttp\Client;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Services\OpenAiImageAnalyzeService;

class OpenAiImageAnalyzeController extends Controller
{
    use ApiResponse;

    protected $imageAnalyzeService;

    public function __construct(OpenAiImageAnalyzeService $imageAnalyzeService)
    {
        $this->imageAnalyzeService = $imageAnalyzeService;
    }


    // public function analyzeImage(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'image' => 'required|mimes:jpeg,png,jpg,gif,webp|max:10240',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->error([], $validator->errors()->first(), 422);
    //     }

    //     DB::beginTransaction();

    //     try {

    //         $imageFile = $request->file('image');
    //         $bgRemovedContents = $this->removeBackground($imageFile);


    //         $tempPath = tempnam(sys_get_temp_dir(), 'rmbg_') . '.png';
    //         file_put_contents($tempPath, $bgRemovedContents);


    //         $result = $this->imageAnalyzeService->analyze($tempPath);


    //         if (isset($result['error'])) {
    //             ApiHit::create([
    //                 'user_id' => auth('api')->id(),
    //                 'success' => false,
    //             ]);
    //             DB::rollBack();
    //             return $this->error([], $result['error'], 400);
    //         }


    //         $filename = 'no-bg-' . time() . '.png';
    //         $uploadPath = public_path('uploads/removebg/' . $filename);
    //         if (!file_exists(public_path('uploads/removebg'))) {
    //             mkdir(public_path('uploads/removebg'), 0777, true);
    //         }
    //         file_put_contents($uploadPath, $bgRemovedContents);


    //         ApiHit::create([
    //             'user_id' => auth('api')->id(),
    //             'success' => true,
    //         ]);


    //         $result['removed_image_url'] = asset('uploads/removebg/' . $filename);

    //         DB::commit();

    //         return $this->success($result, 'Image analyzed successfully.', 200);
    //     } catch (Exception $e) {

    //         DB::rollBack();
    //         Log::error('Analyze Image Error: ' . $e->getMessage());
    //         return $this->error([], 'Something went wrong while analyzing the image.', 500);
    //     } finally {

    //         if (isset($tempPath) && file_exists($tempPath)) {
    //             @unlink($tempPath);
    //         }
    //     }
    // }

    public function analyzeImage(Request $request)
    {
        // ✅ Validate image input
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        DB::beginTransaction();

        try {
            // ✅ Step 1: Get uploaded image and remove background
            $imageFile = $request->file('image');
            $bgRemovedContents = $this->removeBackground($imageFile);

            // ✅ Step 2: Create a temporary file for AI analysis
            $tempPath = tempnam(sys_get_temp_dir(), 'rmbg_') . '.png';
            file_put_contents($tempPath, $bgRemovedContents);

            // ✅ Step 3: Run image analysis service
            $result = $this->imageAnalyzeService->analyze($tempPath);

            // ✅ Step 4: Handle API error response
            if (isset($result['error'])) {
                ApiHit::create([
                    'user_id' => auth('api')->id(),
                    'success' => false,
                ]);
                DB::rollBack();
                return $this->error([], $result['error'], 400);
            }

            // ✅ Step 5: Generate descriptive and unique filename
            $originalName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $cleanName = Str::slug($originalName); // Make safe for URL and file system
            $uniqueId = Str::random(8); // Add short unique string
            $timestamp = time();
            $filename = "{$cleanName}-no-bg-{$uniqueId}-{$timestamp}.png";

            // ✅ Step 6: Save final image
            $uploadDir = public_path('uploads/removebg');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $uploadPath = $uploadDir . '/' . $filename;
            file_put_contents($uploadPath, $bgRemovedContents);

            // ✅ Step 7: Record successful API hit
            ApiHit::create([
                'user_id' => auth('api')->id(),
                'success' => true,
            ]);

            // ✅ Step 8: Add image URL to result
            $result['removed_image_url'] = asset('uploads/removebg/' . $filename);

            DB::commit();

            return $this->success($result, 'Image analyzed successfully.', 200);
        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Analyze Image Error: ' . $e->getMessage());
            return $this->error([], 'Something went wrong while analyzing the image.', 500);
        } finally {

            if (isset($tempPath) && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }


    private function removeBackground($image)
    {
        $apiKey = config('services.rembg.key');
        if (empty($apiKey)) {
            throw new Exception('API key is missing.');
        }

        $client = new Client();

        try {
            $response = $client->post('https://api.rembg.com/rmbg', [
                'headers' => [
                    'x-api-key' => $apiKey,
                ],
                'multipart' => [
                    [
                        'name'     => 'image',
                        'contents' => fopen($image->getPathname(), 'r'),
                        'filename' => $image->getClientOriginalName(),
                    ],
                    [
                        'name'     => 'format',
                        'contents' => 'png',
                    ],
                    [
                        'name'     => 'expand',
                        'contents' => 'true',
                    ],
                ],
            ]);

            return $response->getBody()->getContents();
        } catch (Exception $e) {
            throw new Exception('Failed to remove background: ' . $e->getMessage());
        }
    }


    // public function remove(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'image' => 'required|mimes:jpeg,png,jpg,gif,webp|max:10240',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->error([], $validator->errors()->first(), 422);
    //     }

    //     try {
    //         $imageFile = $request->file('image');
    //         $bgRemovedContents = $this->removeBackground($imageFile);

    //         $filename = 'no-bg-' . time() . '.png';
    //         $uploadPath = public_path('uploads/removebg/' . $filename);

    //         if (!file_exists(public_path('uploads/removebg'))) {
    //             mkdir(public_path('uploads/removebg'), 0777, true);
    //         }

    //         file_put_contents($uploadPath, $bgRemovedContents);

    //         return $this->success([
    //             'imageUrl' => asset('uploads/' . $filename),
    //         ], 'Background removed successfully.', 200);
    //     } catch (Exception $e) {
    //         Log::error('Background removal error: ' . $e->getMessage());
    //         return $this->error([], 'Failed to remove background: ' . $e->getMessage(), 500);
    //     }
    // }
    public function remove(Request $request)
    {
        // ✅ Validate uploaded image
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        try {
            // ✅ Step 1: Process uploaded image
            $imageFile = $request->file('image');
            $bgRemovedContents = $this->removeBackground($imageFile);

            // ✅ Step 2: Generate descriptive and unique filename
            $originalName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $cleanName = Str::slug($originalName); // make safe and URL-friendly
            $uniqueId = Str::random(8); // random short unique string
            $timestamp = time();
            $filename = "{$cleanName}-no-bg-{$uniqueId}-{$timestamp}.png";

            // ✅ Step 3: Ensure directory exists
            $uploadDir = public_path('uploads/removebg');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // ✅ Step 4: Save the background-removed image
            $uploadPath = $uploadDir . '/' . $filename;
            file_put_contents($uploadPath, $bgRemovedContents);

            // ✅ Step 5: Return success with proper URL
            return $this->success([
                'imageUrl' => asset('uploads/removebg/' . $filename),
            ], 'Background removed successfully.', 200);
        } catch (Exception $e) {
            // ✅ Handle unexpected errors
            Log::error('Background removal error: ' . $e->getMessage());
            return $this->error([], 'Failed to remove background: ' . $e->getMessage(), 500);
        }
    }
}
