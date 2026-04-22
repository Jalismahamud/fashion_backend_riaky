<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Backend\CouponController;
use App\Http\Controllers\Web\Backend\ReviewController;
use App\Http\Controllers\Web\Backend\CategoryController;
use App\Http\Controllers\Web\Backend\UserListController;
use App\Http\Controllers\Web\Backend\DashboardController;
use App\Http\Controllers\Web\Backend\PlanManageController;
use App\Http\Controllers\Web\Backend\WebSiteNameController;
use App\Http\Controllers\Web\Backend\CMS\AuthPageController;
use App\Http\Controllers\Web\Backend\Settings\ProfileController;
use App\Http\Controllers\Web\Backend\Settings\SettingController;
use App\Http\Controllers\Web\Backend\StyleQuizQuestionController;
use App\Http\Controllers\Web\Backend\Settings\SocialLinkController;
use App\Http\Controllers\Web\Backend\Settings\DynamicPageController;
use App\Http\Controllers\Web\Backend\Settings\MailSettingController;

Route::middleware(['auth:web', 'admin'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/contact-us', [DashboardController::class, 'showContact'])->name('show.constact.us');
});

Route::get('/user-list', [UserListController::class, 'index'])->name('admin.user.index');
Route::delete('/user-list/delete/{id}', [UserListController::class, 'destroy'])->name('admin.user.destroy');


Route::prefix('quiz')->middleware(['auth:web', 'admin'])->group(function () {
    Route::get('/', [StyleQuizQuestionController::class, 'index'])->name('admin.quiz.index');
    Route::get('/create', [StyleQuizQuestionController::class, 'create'])->name('admin.quiz.create');
    Route::post('/store', [StyleQuizQuestionController::class, 'store'])->name('admin.quiz.store');
    Route::get('/edit/{id}', [StyleQuizQuestionController::class, 'edit'])->name('admin.quiz.edit');
    Route::put('/update/{id}', [StyleQuizQuestionController::class, 'update'])->name('admin.quiz.update');
    Route::delete('/destroy/{id}', [StyleQuizQuestionController::class, 'destroy'])->name('admin.quiz.destroy');
    Route::post('/status/{id}', [StyleQuizQuestionController::class, 'status'])->name('admin.quiz.status');
});

/**
 * Manage socila link
 */
Route::prefix('setting/social-link')->middleware(['auth:web', 'admin'])->group(function () {
    Route::get('/', [SocialLinkController::class, 'index'])->name('social.link.index');
    Route::post('/store', [SocialLinkController::class, 'store'])->name('social.link.store');
    Route::post('/update/{id}', [SocialLinkController::class, 'update'])->name('social.link.update');
    Route::delete('/delete/{id}', [SocialLinkController::class, 'destroy'])->name('social.link.delete');
});


Route::controller(CategoryController::class)->prefix('category')->name('admin.category.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/store', 'store')->name('store');
    Route::put('/update/{id}', 'update')->name('update');
    Route::delete('/destroy/{id}', 'destroy')->name('destroy');
});


Route::controller(WebSiteNameController::class)->prefix('website')->name('admin.website.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/store', 'store')->name('store');
    Route::put('/update/{id}', 'update')->name('update');
    Route::delete('/destroy/{id}', 'destroy')->name('destroy');
});



//! Route for Review Management
Route::controller(ReviewController::class)->prefix('review')->name('admin.review.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/approve/{id}', 'approveReview')->name('approve');
    Route::post('/reject/{id}', 'cancelledReview')->name('reject');
    Route::get('/show/{id}', 'show')->name('show');
});


//! Route for Profile Settings
Route::controller(ProfileController::class)->group(function () {
    Route::get('setting/profile', 'index')->name('setting.profile.index');
    Route::put('setting/profile/update', 'UpdateProfile')->name('setting.profile.update');
    Route::put('setting/profile/update/Password', 'UpdatePassword')->name('setting.profile.update.Password');
    Route::post('setting/profile/update/Picture', 'UpdateProfilePicture')->name('update.profile.picture');
});




//! Route for Mail Settings
Route::controller(MailSettingController::class)->group(function () {
    Route::get('setting/mail', 'index')->name('setting.mail.index');
    Route::patch('setting/mail', 'update')->name('setting.mail.update');
});




//! Route for Stripe Settings
Route::controller(SettingController::class)->group(function () {
    Route::get('setting/general', 'index')->name('setting.general.index');
    Route::patch('setting/general', 'update')->name('setting.general.update');
});

//Auth Page CMS
Route::controller(AuthPageController::class)->prefix('cms')->name('cms.')->group(function () {
    Route::get('page/auth/section/bg', 'index')->name('page.auth.section.bg.index');
    Route::patch('page/auth/section/bg', 'update')->name('page.auth.section.bg.update');
});

// CMS Routes
Route::prefix('cms')->name('admin.cms.')->group(function () {});

Route::controller(DynamicPageController::class)->group(function () {
    Route::get('/dynamic-page', 'index')->name('admin.dynamic_page.index');
    Route::get('/dynamic-page/create', 'create')->name('admin.dynamic_page.create');
    Route::post('/dynamic-page/store', 'store')->name('admin.dynamic_page.store');
    Route::get('/dynamic-page/edit/{id}', 'edit')->name('admin.dynamic_page.edit');
    Route::put('/dynamic-page/update/{id}', 'update')->name('admin.dynamic_page.update');
    Route::post('/dynamic-page/status/{id}', 'status')->name('admin.dynamic_page.status');
    Route::delete('/dynamic-page/destroy/{id}', 'destroy')->name('admin.dynamic_page.destroy');
});




//subscription management
Route::group(['prefix' => 'plan','middleware' => ['auth:web', 'admin']], function () {
    Route::get('/', [PlanManageController::class, 'index'])->name('admin.plan.index');
    Route::post('/store', [PlanManageController::class, 'store'])->name('admin.plan.store');
    Route::get('/edit/{id}', [PlanManageController::class, 'edit'])->name('admin.plan.edit');
    Route::post('/update/{id}', [PlanManageController::class, 'update'])->name('admin.plan.update');
    Route::delete('/delete/{id}', [PlanManageController::class, 'destroy'])->name('admin.plan.destroy');
});

