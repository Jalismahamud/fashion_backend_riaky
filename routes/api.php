<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ContactUsController;
use App\Http\Controllers\Api\SocialProfileController;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\Backend\ApiItemController;
use App\Http\Controllers\Api\Auth\UserProfileController;
use App\Http\Controllers\Api\Backend\RemoveBgController;
use App\Http\Controllers\Api\Backend\ApiReviewController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\Backend\OpenAiChatController;
use App\Http\Controllers\Api\Auth\AuthenticationController;
use App\Http\Controllers\Api\Backend\ApiCategoryController;
use App\Http\Controllers\Api\Backend\OpenAiStyleController;
use App\Http\Controllers\Api\Backend\ApiStyleQuizController;
use App\Http\Controllers\Api\Backend\ApiSubscriptionController;
use App\Http\Controllers\Api\Backend\OpenAiImageAnalyzeController;
use App\Http\Controllers\Web\Backend\Settings\DynamicPageController;
use App\Http\Controllers\Api\Backend\OpenAiWeatherSuggestionController;


Route::get('privacy-policy', [DynamicPageController::class, 'privacyPolicy']);
Route::get('about-us', [DynamicPageController::class, 'aboutUs']);
Route::post('/contact-us', [ContactUsController::class, 'contactUs']);
Route::get('/social-profiles', [SocialProfileController::class, 'getSocialProfiles']);
Route::get('/subscription/plans', [ApiSubscriptionController::class, 'index']);


Route::post('google-authentication', [SocialAuthController::class, 'googleAuthentication']);
Route::post('apple-authentication', [SocialAuthController::class, 'appleAuthentication']);

Route::get('/categories', [ApiCategoryController::class, 'index']);
Route::get('/home/reviews', [ApiReviewController::class, 'getHomePageReviews']);

Route::post('/remove-bg', [RemoveBgController::class, 'remove']);

Route::group(['middleware' => 'guest:api',], function () {
    Route::post('/login', [AuthenticationController::class, 'login']);
    Route::post('/register', [AuthenticationController::class, 'register']);
    Route::post('/register-otp-verify', [AuthenticationController::class, 'registrationVerifyOtp']);
    Route::post('/forgot-password', [ResetPasswordController::class, 'forgotPassword']);
    Route::post('/resend-otp', [ResetPasswordController::class, 'resendOtp']);
    Route::post('/verify-otp', [ResetPasswordController::class, 'VerifyOTP']);
    Route::post('/reset-password', [ResetPasswordController::class, 'ResetPassword']);
});



Route::group(['middleware' => ['auth:api']], function () {

    Route::get('/profile', [UserProfileController::class, 'profile']);
    Route::post('/update-profile', [UserProfileController::class, 'updateProfile']);
    Route::post('/update-avatar', [UserProfileController::class, 'updateAvatar']);
    Route::post('/update-password', [UserProfileController::class, 'updatePassword']);

    Route::delete('/delete-profile', [UserProfileController::class, 'deleteProfile']);
    Route::post('/logout', [AuthenticationController::class, 'logout']);


    Route::get('style-quiz/questions', [ApiStyleQuizController::class, 'questions']);
    Route::post('style-quiz/answers', [ApiStyleQuizController::class, 'submitAnswers']);
    Route::get('style-quiz/profile', [ApiStyleQuizController::class, 'profile']);


    Route::post('/open-ai/chat', [OpenAiChatController::class, 'openAiChat']);
    // Route::post('/open-ai/chat', [OpenAiChatController::class, 'openAiChat'])->middleware(['checkImageUpload', 'checkChatting']);
    Route::get('/open-ai/chat/history', [OpenAiChatController::class, 'openAiChatHistory']);
    Route::post('/chat/reuse-image/{history}', [OpenAiChatController::class, 'reuseImageAnalysis']);

    Route::post('openai/image-analyze', [OpenAiImageAnalyzeController::class, 'analyzeImage']);

    Route::get('items', [ApiItemController::class, 'index']);
    Route::get('item/details/{slug}', [ApiItemController::class, 'show']);
    // Route::post('item/store', [ApiItemController::class, 'store'])->middleware(['checkItemUpload']);
    Route::post('item/store', [ApiItemController::class, 'store']);
    Route::post('/item/update/{slug}', [ApiItemController::class, 'update']);
    Route::delete('/item/delete/{slug}', [ApiItemController::class, 'destroy']);


    Route::get('/reviews', [ApiReviewController::class, 'getReviews']);
    Route::post('/review/store', [ApiReviewController::class, 'store']);
    Route::delete('/review/delete/{id}', [ApiReviewController::class, 'destroy']);

    Route::get('/my-item-list', [ApiCategoryController::class, 'myList']);
    Route::get('/my-item-list-details/{slug}', [ApiCategoryController::class, 'myListDetails']);


    // Route::post('/style-image', [OpenAiStyleController::class, 'styleImage']);

    // Route::post('/style-item', [OpenAiStyleController::class, 'styleItem']);



    Route::post('/style-item', [OpenAiStyleController::class, 'styleItem']);


    // Generate image from text prompt only
    Route::post('/generate-image', [OpenAiStyleController::class, 'generateFromPrompt']);

    // Create variation of existing image (PNG only)
    Route::post('/image-variation', [OpenAiStyleController::class, 'createVariation']);

    Route::get('/weather-based-suggestion', [OpenAiWeatherSuggestionController::class, 'weatherBasedSuggestion']);


    //subscription manage

    Route::post('/subscription/create-intent', [ApiSubscriptionController::class, 'createIntent']);
    Route::post('/subscription/purchase', [ApiSubscriptionController::class, 'purchaseSubscription']);
    Route::post('/subscription/cancel', [ApiSubscriptionController::class, 'cancelSubscription']);
    Route::post('/subscription/update', [ApiSubscriptionController::class, 'updateSubscription']);
    Route::get('/subscription/status', [ApiSubscriptionController::class, 'status']);
});
