<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    /**
     *  ⭐️ Liste des champs que Laravel a le droit de remplir automatiquement.
     *
     * Sans cette propriété, Laravel bloque les créations avec Poll::create([...])
     * pour des raisons de sécurité.
     */
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

    /**
     *  ⭐️ Conversion automatique des valeurs venant de la base de données.
     *
     * Exemple :
     * - is_draft sera transformé en vrai booléen true/false
     * - started_at et ends_at seront transformés en objets date Carbon
     */
    protected $casts = [
        'is_draft' => 'boolean',
        'allow_multiple_choices' => 'boolean',
        'allow_vote_change' => 'boolean',
        'results_public' => 'boolean',
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Get the user that owns the poll.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the options for the poll.
     */
    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class);
    }

    /**
     * Get the votes for the poll.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }
}