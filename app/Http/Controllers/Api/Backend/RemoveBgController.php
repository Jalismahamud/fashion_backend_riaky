<?php

namespace App\Http\Controllers\Api\Backend;

use GuzzleHttp\Client;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class RemoveBgController extends Controller
{
    use ApiResponse;

    public function remove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:5120', // max 5MB
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $apiKey = config('services.rembg.key'); // use rembg key
        if (empty($apiKey)) {
            return $this->error([], 'API key is missing.', 500);
        }

        $image = $request->file('image');
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
                        'contents' => 'png', // you can change to 'webp'
                    ],
                    [
                        'name'     => 'expand',
                        'contents' => 'true',
                    ],
                ],
            ]);

            $filename = 'no-bg-' . time() . '.png';
            $uploadPath = public_path('uploads/removebg/' . $filename);

            if (!file_exists(public_path('uploads/removebg'))) {
                mkdir(public_path('uploads/removebg'), 0777, true);
            }

            file_put_contents($uploadPath, $response->getBody());

            $imageUrl = asset('uploads/removebg/' . $filename);

            return $this->success([
                'imageUrl' => $imageUrl
            ], 'Background removed successfully.', 200);

        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }
}
