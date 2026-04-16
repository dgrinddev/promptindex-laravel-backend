<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PromptController;
use App\Http\Controllers\ImageController;
use App\Http\Middleware\CleanupExpiredTestUserPrompts;
use App\Http\Resources\AuthUserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Routes that require authentication
Route::middleware(['auth:sanctum'])->group(function () {
    // used for getAuthUser() in frontend
    Route::get('/user', function (Request $request) {
        return new AuthUserResource($request->user());
    });

    // Routes that are used in 'app' context only (contexts in `type ApplicationSurface` in `src/types/ui/application.types.ts` in the frontend-repository)
    Route::prefix('app')->group(function () {
        // User settings
        Route::get('/user/edit-profile', [SettingsController::class, 'editProfile'])->name('user.edit-profile');
        Route::delete('/user/delete-account', [SettingsController::class, 'deleteAccount'])->name('user.delete-account');

        // Prompts
        Route::get('/prompts/my-prompts', [PromptController::class, 'myPrompts'])->name('prompts.my-prompts');
        Route::post('/prompts', [PromptController::class, 'store'])->name('prompts.store');
        Route::get('/prompts/{prompt}/edit', [PromptController::class, 'edit'])->name('prompts.edit');
        Route::put('/prompts/{prompt}', [PromptController::class, 'update'])->name('prompts.update');
        Route::delete('/prompts/{prompt}', [PromptController::class, 'destroy'])->name('prompts.destroy');

        // Images
        Route::post('/images', [ImageController::class, 'store'])->name('images.store');
        Route::delete('/images/{image}', [ImageController::class, 'destroy'])->name('images.destroy');
    });
});

// Routes that are used in 'guest' context only (contexts in `type ApplicationSurface` in `src/types/ui/application.types.ts` in the frontend-repository)
Route::prefix('guest')->group(function () {
    Route::get('/prompts', [PromptController::class, 'index'])->name('prompts.index');

    /*
    // Nested group under /api/guest
    Route::prefix('docs')->group(function () {
        // ...
    });
    */
});

// Routes that are used in both 'app' and 'guest' contexts (contexts in `type ApplicationSurface` in `src/types/ui/application.types.ts` in the frontend-repository)
Route::get('/prompts/{prompt}', [PromptController::class, 'show'])->name('prompts.show');
Route::get('/categories', [CategoryController::class, 'index'])
    ->middleware(CleanupExpiredTestUserPrompts::class)
    ->name('categories.index');