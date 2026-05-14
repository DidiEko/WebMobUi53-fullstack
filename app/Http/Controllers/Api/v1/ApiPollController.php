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
     * Modifie un sondage existant.
     */
    public function update(Request $request, Poll $poll)
    {
        // Sécurité : seul le créateur du sondage peut le modifier.
        if ($poll->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Action interdite.',
            ], 403);
        }

        // On valide les données envoyées par Vue.
        // On ajoute ici les paramètres du sondage pour pouvoir les modifier.
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'question' => ['required', 'string', 'max:255'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.label' => ['required', 'string', 'max:255'],
            'allow_multiple_choices' => ['boolean'],
            'results_public' => ['boolean'],
            'duration' => ['nullable', 'integer', 'min:60'],
        ]);

        // Si le sondage est déjà lancé,
        // on empêche uniquement la modification du nombre d'options.
        // Cela évite de casser les votes déjà existants.
        if (
            !$poll->is_draft &&
            count($validated['options']) !== $poll->options()->count()
        ) {
            return response()->json([
                'message' => 'Les options ne peuvent plus être modifiées après le démarrage du sondage.',
            ], 422);
        }

        // Transaction : on modifie le sondage et ses options ensemble.
        DB::transaction(function () use ($poll, $validated) {

            // Mise à jour du sondage principal et de ses paramètres.
            $poll->update([
                'title' => $validated['title'] ?? null,
                'question' => $validated['question'],
                'allow_multiple_choices' => $validated['allow_multiple_choices'] ?? false,
                'results_public' => $validated['results_public'] ?? false,
                'duration' => $validated['duration'] ?? null,
            ]);

            // Si le sondage est encore en brouillon,
            // on peut recréer librement les options.
            if ($poll->is_draft) {

                $poll->options()->delete();

                foreach ($validated['options'] as $option) {
                    $poll->options()->create([
                        'label' => $option['label'],
                    ]);
                }
            }
        });

        return response()->json(
            $poll->refresh()->load('options')
        );
    }

    /** ⭐️
     * Démarre un sondage encore en brouillon.
     *
     * Cela signifie :
     * - le sondage n'est plus un brouillon
     * - la date de début est enregistrée
     * - la date de fin est calculée si une durée existe
     */
    public function start(Request $request, Poll $poll)
    {
        // Sécurité : seul le créateur du sondage peut le démarrer.
        if ($poll->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Action interdite.',
            ], 403);
        }

        // On évite de démarrer deux fois le même sondage.
        if (!$poll->is_draft) {
            return response()->json([
                'message' => 'Ce sondage est déjà démarré.',
            ], 422);
        }

        $poll->update([
            'is_draft' => false,
            'started_at' => now(),

            // Si une durée existe, on calcule automatiquement la date de fin.
            'ends_at' => $poll->duration
                ? now()->addSeconds($poll->duration)
                : null,
        ]);

        return response()->json($poll->refresh()->load('options'));
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
    public function show(Request $request, string $token)
    {
        $poll = Poll::with([
            'options' => function ($query) {
                $query->withCount('votes');
            },
            'user',
        ])
            ->where('secret_token', $token)
            ->first();

        if (!$poll) {
            return response()->json([
                'message' => 'Poll not found.',
            ], 404);
        }

        // On ajoute une information utile pour le frontend :
        // savoir si l'utilisateur connecté est le propriétaire du sondage.
        $poll->is_owner =
            $request->user() &&
            $request->user()->id === $poll->user_id;

        return $poll;
    }

    /**
     * Permet de voter sur un sondage public via son token.
     *
     * Cette version gère :
     * - le choix unique
     * - le choix multiple
     * - la vérification que les options appartiennent au bon sondage
     * - l'interdiction de voter plusieurs fois sur un sondage
     */
    public function vote(Request $request, string $token)
    {
        // On récupère le sondage via son token secret.
        $poll = Poll::where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Sondage introuvable.'], 404);
        }

        // On empêche de voter sur un sondage encore en brouillon.
        if ($poll->is_draft) {
            return response()->json(['message' => 'Ce sondage n’est pas encore actif.'], 422);
        }

        // Si le sondage a une date de fin et que cette date est dépassée,
        // on empêche aussi le vote.
        if ($poll->ends_at && now()->greaterThan($poll->ends_at)) {
            return response()->json(['message' => 'Ce sondage est terminé.'], 422);
        }

        // Pour ce projet, on impose un utilisateur connecté pour voter.
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Vous devez être connecté pour voter.',
            ], 401);
        }

        // Même en choix unique ou multiple, l'utilisateur ne peut soumettre qu'une seule fois.
        $alreadyVoted = $poll->votes()
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyVoted) {
            return response()->json([
                'message' => 'Vous avez déjà voté pour ce sondage.',
            ], 422);
        }

        // Cas 1 : sondage à choix multiple.
        if ($poll->allow_multiple_choices) {
            $validated = $request->validate([
                'poll_option_ids' => ['required', 'array', 'min:1'],
                'poll_option_ids.*' => ['required', 'integer', 'exists:poll_options,id'],
            ]);

            // On vérifie que toutes les options envoyées appartiennent bien au sondage actuel.
            $validOptionIds = $poll->options()
                ->whereIn('id', $validated['poll_option_ids'])
                ->pluck('id')
                ->toArray();

            if (count($validOptionIds) !== count($validated['poll_option_ids'])) {
                return response()->json([
                    'message' => 'Une ou plusieurs options ne correspondent pas à ce sondage.',
                ], 422);
            }

            foreach ($validOptionIds as $optionId) {
                $poll->votes()->create([
                    'poll_id' => $poll->id,
                    'user_id' => $user->id,
                    'poll_option_id' => $optionId,
                ]);
            }

            return response()->json([
                'message' => 'Vote enregistré.',
            ]);
        }

        // Cas 2 : sondage à choix unique.
        $validated = $request->validate([
            'poll_option_id' => ['required', 'exists:poll_options,id'],
        ]);

        // Sécurité importante :
        // on vérifie que l'option choisie appartient bien au sondage actuel.
        $optionBelongsToPoll = $poll->options()
            ->where('id', $validated['poll_option_id'])
            ->exists();

        if (!$optionBelongsToPoll) {
            return response()->json([
                'message' => 'Cette option ne correspond pas à ce sondage.',
            ], 422);
        }

        // Création du vote unique.
        $poll->votes()->create([
            'poll_id' => $poll->id,
            'user_id' => $user->id,
            'poll_option_id' => $validated['poll_option_id'],
        ]);

        return response()->json([
            'message' => 'Vote enregistré.',
        ]);
    }
}
