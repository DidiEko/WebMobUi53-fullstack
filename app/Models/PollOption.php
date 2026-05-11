<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollOption extends Model
{
    /**
     * ⭐️ Liste des champs que Laravel peut remplir automatiquement.
     *
     * Ici, une option appartient à un sondage grâce à poll_id,
     * et contient un texte affiché à l'utilisateur grâce à label.
     */
    protected $fillable = [
        'poll_id',
        'label',
    ];

    /**
     * Get the poll that owns the option.
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    /**
     * Get the votes for this option.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }
}