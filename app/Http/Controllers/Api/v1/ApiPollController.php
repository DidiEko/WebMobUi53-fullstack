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
     * Display a listing of the authenticated user's polls.
     */
    public function index(Request $request)
    {
        $polls = $request->user()->polls()->orderBy('created_at', 'desc')->get();

        return $polls;
    }

        /**
     * ⭐️ Crée un nouveau sondage avec ses options.
     *
     * Cette méthode sera appelée par le frontend Vue
     * quand l'utilisateur voudra créer un sondage.
     */
    public function store(Request $request)
    {
        /**
         * Validation des données reçues depuis Vue.
         *
         * Laravel vérifie ici que :
         * - la question est obligatoire
         * - il y a au minimum deux options
         * - chaque option possède un label
         */
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

        /**
         * Transaction :
         * si la création du sondage ou des options échoue,
         * Laravel annule tout.
         *
         * Cela évite d'avoir un sondage créé sans ses options.
         */
        $poll = DB::transaction(function () use ($request, $validated) {
            // Par défaut, un nouveau sondage est créé comme brouillon.
            $isDraft = $validated['is_draft'] ?? true;

            // Création du sondage principal.
            $poll = Poll::create([
                'user_id' => $request->user()->id,
                'title' => $validated['title'] ?? null,
                'question' => $validated['question'],

                // Token secret utilisé plus tard dans le lien de partage.
                'secret_token' => Str::random(40),

                'is_draft' => $isDraft,
                'allow_multiple_choices' => $validated['allow_multiple_choices'] ?? false,
                'allow_vote_change' => $validated['allow_vote_change'] ?? false,
                'results_public' => $validated['results_public'] ?? false,
                'duration' => $validated['duration'] ?? null,

                // Si le sondage n'est pas un brouillon, il démarre directement.
                'started_at' => $isDraft ? null : now(),

                // Si une durée est donnée, on calcule automatiquement la date de fin.
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
            $poll->load('options'),
            201
        );
    }

    /**
     * Display the specified poll by its secret token.
     */
    public function show(string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        return $poll;
    }
}
