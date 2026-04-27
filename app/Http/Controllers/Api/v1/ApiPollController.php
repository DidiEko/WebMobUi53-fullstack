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
     * Affiche la liste des sondages de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $polls = $request->user()
            ->polls()
            ->orderBy('created_at', 'desc')
            ->get();

        return $polls;
    }

    /**
     * Crée un nouveau sondage avec ses options.
     */
    public function store(Request $request)
    {
        // On valide les données envoyées par Vue.
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

        // Transaction : soit le sondage ET les options sont créés, soit rien n'est créé.
        $poll = DB::transaction(function () use ($request, $validated) {
            $isDraft = $validated['is_draft'] ?? true;

            // Création du sondage principal.
            $poll = Poll::create([
                'user_id' => $request->user()->id,
                'title' => $validated['title'] ?? null,
                'question' => $validated['question'],

                // Token utilisé plus tard pour partager le sondage.
                'secret_token' => Str::random(40),

                'is_draft' => $isDraft,
                'allow_multiple_choices' => $validated['allow_multiple_choices'] ?? false,
                'allow_vote_change' => $validated['allow_vote_change'] ?? false,
                'results_public' => $validated['results_public'] ?? false,
                'duration' => $validated['duration'] ?? null,
                'started_at' => $isDraft ? null : now(),
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

        return response()->json($poll->load('options'), 201);
    }

    /**
     * Supprime un sondage.
     */
    public function destroy(Request $request, Poll $poll)
    {
        // Sécurité : seul le créateur du sondage peut le supprimer.
        if ($poll->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Action interdite.',
            ], 403);
        }

        $poll->delete();

        return response()->json([
            'message' => 'Sondage supprimé.',
        ]);
    }

    /**
     * Affiche un sondage grâce à son token secret.
     */
    public function show(string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])
            ->where('secret_token', $token)
            ->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        return $poll;
    }
}