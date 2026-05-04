<?php

use App\Http\Controllers\Api\v1\ApiPostController;
use App\Http\Controllers\Api\v1\ApiFooController;
use App\Http\Controllers\Api\v1\ApiPollController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('v1/posts', ApiPostController::class)
    ->middlewareFor(['index', 'show'], ['auth:sanctum', 'abilities:posts:read'])
    ->middlewareFor(['store'], ['auth:sanctum', 'abilities:posts:create'])
    ->middlewareFor(['update'], ['auth:sanctum', 'abilities:posts:update'])
    ->middlewareFor(['destroy'], ['auth:sanctum', 'abilities:posts:delete']);

// Route publique : permet d'afficher un sondage grâce à son token secret.
// Elle servira plus tard pour la page de vote.
Route::get('/v1/polls/{token}', [ApiPollController::class, 'show']);

// Routes protégées : l'utilisateur doit être connecté.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/v1/foo', [ApiFooController::class, 'show']);
    Route::post('/v1/foo', [ApiFooController::class, 'store']);

    // Liste des sondages de l'utilisateur connecté.
    Route::get('/v1/polls', [ApiPollController::class, 'index']);

    // Création d'un nouveau sondage.
    Route::post('/v1/polls', [ApiPollController::class, 'store']);

    // Modification d'un sondage existant.
    // Pour rester simple, on autorise seulement la modification d'un brouillon.
    Route::put('/v1/polls/{poll}', [ApiPollController::class, 'update']);

    // Démarrage d'un sondage brouillon.
    // Cela permet de passer is_draft de true à false.
    Route::post('/v1/polls/{poll}/start', [ApiPollController::class, 'start']);

    // ⭐️Suppression d'un sondage.
    Route::delete('/v1/polls/{poll}', [ApiPollController::class, 'destroy']);

    // ⭐️Permet à un utilisateur de voter sur un sondage via son token.
    Route::post('/v1/polls/{token}/vote', [ApiPollController::class, 'vote']);
});
