<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    // Liste des champs que Laravel a le droit de remplir automatiquement
    // via Poll::create([...]) ou $poll->update([...]).
    protected $fillable = [
        'user_id',
        'title',
        'question',
        'secret_token',
        'is_draft',
        'allow_multiple_choices',
        'allow_vote_change',
        'results_public',
        'duration',
        'started_at',
        'ends_at',
    ];

    // Conversion automatique des champs venant de la base de données.
    // Exemple : is_draft devient un vrai booléen PHP.
    protected $casts = [
        'is_draft' => 'boolean',
        'allow_multiple_choices' => 'boolean',
        'allow_vote_change' => 'boolean',
        'results_public' => 'boolean',
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // Un sondage appartient à un utilisateur.
    // C'est le créateur du sondage.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Un sondage possède plusieurs options de réponse.
    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class);
    }

    // Un sondage possède plusieurs votes.
    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    // Méthode pratique pour savoir si le sondage est terminé.
    // Si ends_at est null, cela veut dire qu'il n'a pas de date de fin.
    public function isExpired(): bool
    {
        return $this->ends_at !== null && now()->greaterThan($this->ends_at);
    }
}