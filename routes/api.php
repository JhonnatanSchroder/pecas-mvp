<?php

use App\Http\Controllers\IntegrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('integrations')->middleware('integration.company')->group(function (): void {
    Route::post('whatsapp/inbound', [IntegrationController::class, 'inbound']);
    Route::post('whatsapp/human-outbound', [IntegrationController::class, 'storeHumanWhatsappMessage']);
    Route::get('conversations/{conversation}/context', [IntegrationController::class, 'context']);
    Route::post('conversations/{conversation}/messages', [IntegrationController::class, 'storeMessage']);
    Route::post('conversations/{conversation}/handoff', [IntegrationController::class, 'handoff']);
    Route::post('conversations/{conversation}/summary', [IntegrationController::class, 'summary']);
    Route::get('parts/search', [IntegrationController::class, 'searchParts']);
});
