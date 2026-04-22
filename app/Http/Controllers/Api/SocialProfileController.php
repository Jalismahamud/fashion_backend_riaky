<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\SocialLink;
use App\Http\Controllers\Controller;

class SocialProfileController extends Controller
{
    //get social profile
    public function getSocialProfiles()
    {
        try {
            $socialLinks = SocialLink::latest('id')->get();

            return response()->json([
                'success' => true,
                'message' => 'Social profiles fetched successfully!',
                'data'    => $socialLinks
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => []
            ], 500);
        }
    }
}
