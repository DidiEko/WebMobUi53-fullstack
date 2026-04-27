<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiPollController extends Controller
{
    /**
     * Retourne tous les sondages créés par l'utilisateur connecté.
     *
     * Cette méthode est utilisée pour le dashboard.
     */
    public function index(Request $request)
    {
        return $request->user()
            ->polls()
            // Ajoute le nombre d'options et le nombre de votes sans devoir tout calculer à la main.
            ->withCount(['options', 'votes'])
            // Charge aussi les options du sondage pour les afficher côté frontend.
            ->with('options')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Crée un nouveau sondage avec ses options.
     */
    public function store(Request $request)
    {
        // Validation des données envoyées par le frontend.
        // Laravel renvoie automatiquement une erreur 422 si une règle n'est pas respectée.
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'question' => ['required', 'string', 'max:255'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.label' => ['required', 'string', 'max:255'],
            'is_draft' => ['boolean'],
            'allow_multiple_choices' => ['boolean'],
            'allow_vote_change' => ['boolean'],
            'results_public' => ['boolean'],
            'duration' => ['nullable', 'integer', 'min:60'],
        ]);

        // Transaction = soit tout est créé, soit rien n'est créé.
        // C'est important car un sondage sans options serait incohérent.
        $poll = DB::transaction(function () use ($request, $validated) {
            // Par défaut, un sondage est créé comme brouillon.
            $isDraft = $validated['is_draft'] ?? true;

            // Création du sondage principal.
            $poll = Poll::create([
                'user_id' => $request->user()->id,
                'title' => $validated['title'] ?? null,
                'question' => $validated['question'],

                // Token secret utilisé dans le lien de partage.
                // Exemple : /polls/vote/ce-token-secret
                'secret_token' => Str::random(40),

                'is_draft' => $isDraft,
                'allow_multiple_choices' => $validated['allow_multiple_choices'] ?? false,
                'allow_vote_change' => $validated['allow_vote_change'] ?? false,
                'results_public' => $validated['results_public'] ?? false,
                'duration' => $validated['duration'] ?? null,

                // Si le sondage n'est pas un brouillon, il démarre immédiatement.
                'started_at' => $isDraft ? null : now(),

                // Si une durée est définie, on calcule la date de fin.
                'ends_at' => (!$isDraft && !empty($validated['duration']))
                    ? now()->addSeconds($validated['duration'])
                    : null,
            ]);

            // Création des options liées au sondage.
            foreach ($validated['options'] as $option) {
                $poll->options()->create([
                    'label' => $option['label'],
                ]);
            }

            return $poll;
        });

        // Réponse JSON envoyée au frontend.
        return response()->json(
            $poll->load('options')->loadCount(['options', 'votes']),
            201
        );
    }

    /**
     * Affiche un sondage à partir de son token secret.
     *
     * Cette méthode servira pour la page publique de vote.
     */
    public function showByToken(string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            // Pour chaque option, on ajoute le nombre de votes associés.
            $query->withCount('votes');
        }])
            ->where('secret_token', $token)
            ->first();

        if (!$poll) {
            return response()->json(['message' => 'Sondage introuvable.'], 404);
        }

        return $poll;
    }

    /**
     * Met à jour un sondage existant.
     *
     * Dans cette première version, on autorise la modification uniquement
     * si le sondage est encore en brouillon.
     */
    public function update(Request $request, Poll $poll)
    {
        // Sécurité : seul le créateur du sondage peut le modifier.
        if ($poll->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Action interdite.'], 403);
        }

        // Règle métier : on évite de modifier un sondage déjà lancé.
        // Cela simplifie la logique des votes.
        if (!$poll->is_draft) {
            return response()->json([
                'message' => 'Un sondage lancé ne peut plus être modifié.',
            ], 422);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'question' => ['required', 'string', 'max:255'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.label' => ['required', 'string', 'max:255'],
            'allow_multiple_choices' => ['boolean'],
            'allow_vote_change' => ['boolean'],
            'results_public' => ['boolean'],
            'duration' => ['nullable', 'integer', 'min:60'],
        ]);

        DB::transaction(function () use ($poll, $validated) {
            // Mise à jour des informations principales du sondage.
            $poll->update([
                'title' => $validated['title'] ?? null,
                'question' => $validated['question'],
                'allow_multiple_choices' => $validated['allow_multiple_choices'] ?? false,
                'allow_vote_change' => $validated['allow_vote_change'] ?? false,
                'results_public' => $validated['results_public'] ?? false,
                'duration' => $validated['duration'] ?? null,
            ]);

            // Version simple : on supprime les anciennes options puis on recrée les nouvelles.
            // Comme le sondage est encore en brouillon, il n'y a pas encore de votes à préserver.
            $poll->options()->delete();

            foreach ($validated['options'] as $option) {
                $poll->options()->create([
                    'label' => $option['label'],
                ]);
            }
        });

        return $poll->refresh()->load('options')->loadCount(['options', 'votes']);
    }

    /**
     * Supprime un sondage.
     */
    public function destroy(Request $request, Poll $poll)
    {
        // Sécurité : seul le créateur peut supprimer son sondage.
        if ($poll->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Action interdite.'], 403);
        }

        $poll->delete();

        return response()->json([
            'message' => 'Sondage supprimé.',
        ]);
    }

    /**
     * Lance un sondage qui était en brouillon.
     */
    public function start(Request $request, Poll $poll)
    {
        // Sécurité : seul le créateur peut lancer son sondage.
        if ($poll->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Action interdite.'], 403);
        }

        if (!$poll->is_draft) {
            return response()->json([
                'message' => 'Ce sondage est déjà lancé.',
            ], 422);
        }

        $poll->update([
            'is_draft' => false,
            'started_at' => now(),

            // Si une durée existe, la date de fin est calculée au moment du lancement.
            'ends_at' => $poll->duration ? now()->addSeconds($poll->duration) : null,
        ]);

        return $poll->load('options')->loadCount(['options', 'votes']);
    }
}