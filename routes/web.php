<?php
use App\Http\Controllers\{CourseController, PaymentController, RegistrationController};
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

$slug = '(?!(admin|livewire|storage)$)[a-z0-9-]+';   // keep /admin etc. free

Route::get('/', [CourseController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [CourseController::class, 'sitemap']);
Route::post('/webhooks/slickpay', [PaymentController::class, 'webhook'])->withoutMiddleware([ValidateCsrfToken::class])->name('slickpay.webhook');
Route::get('/pay/{registration}/return', [PaymentController::class, 'back'])->name('slickpay.return');
Route::get('/pay/{registration}/ccp', [RegistrationController::class, 'ccp'])->name('ccp');
Route::get('/r/{registration}', [RegistrationController::class, 'thanks'])->name('thanks');
Route::get('/r/{registration}/calendar.ics', [RegistrationController::class, 'ics'])->name('ics');
Route::get('/{course:slug}', [CourseController::class, 'show'])->where('course', $slug)->name('course');
Route::post('/{course:slug}/register', [RegistrationController::class, 'store'])->where('course', $slug)->middleware('throttle:10,1')->name('register');
