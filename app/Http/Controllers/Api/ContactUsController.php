<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\ContactUs;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ContactUsController extends Controller
{
    use ApiResponse;

    //create constact
    public function contactUs(Request $request)
    {
        // dd($request->all());
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'nullable|string|max:100',
                'email'     => 'nullable|email|max:100',
                'phone'     => 'nullable|string|max:50',
                'subject'   => 'nullable|string|max:255',
                'message'   => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors(), 'Validation failed.', 422);
            }

            // Upsert logic (by email or phone)
            $contact = ContactUs::create(
                [
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'full_name' => $request->full_name,
                    'subject'   => $request->subject,
                    'message'   => $request->message,
                ]
            );

            return $this->success($contact, 'Contact message saved successfully.', 200);
        } catch (Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }
}
