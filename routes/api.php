<?php

use App\Http\Controllers\Api\v1\ApiFooController;
use App\Http\Controllers\Api\v1\ApiPollController;
use Illuminate\Support\Facades\Route;

// Route publique : permet d'accéder à un sondage via son token secret.
// Elle sera utilisée plus tard pour la page de vote.
Route::get('/v1/polls/token/{token}', [ApiPollController::class, 'showByToken']);

// Toutes les routes dans ce groupe nécessitent un utilisateur connecté.
// auth:sanctum vérifie que la personne est authentifiée.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/v1/foo', [ApiFooController::class, 'show']);
    Route::post('/v1/foo', [ApiFooController::class, 'store']);

    // Liste des sondages de l'utilisateur connecté.
    Route::get('/v1/polls', [ApiPollController::class, 'index']);

    // Création d'un sondage.
    Route::post('/v1/polls', [ApiPollController::class, 'store']);

    // Modification d'un sondage existant.
    Route::put('/v1/polls/{poll}', [ApiPollController::class, 'update']);

    // Suppression d'un sondage.
    Route::delete('/v1/polls/{poll}', [ApiPollController::class, 'destroy']);

    // Lancement d'un sondage brouillon.
    Route::post('/v1/polls/{poll}/start', [ApiPollController::class, 'start']);
});