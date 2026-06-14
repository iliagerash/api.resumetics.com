<?php

use App\Http\Controllers\ResendWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/resend', ResendWebhookController::class);
