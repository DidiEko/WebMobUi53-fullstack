<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollVote extends Model
{

    /**
     *  ⭐️ Liste des champs que Laravel peut remplir automatiquement.
     *
     * Chaque vote est lié :
     * - à un sondage avec poll_id
     * - à un utilisateur avec user_id
     * - à une option choisie avec poll_option_id
     */
    protected $fillable = [
        'poll_id',
        'user_id',
        'poll_option_id',
    ];

    /**
     * Get the poll that owns the vote.
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    /**
     * Get the user that cast the vote.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the option chosen.
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(PollOption::class, 'poll_option_id');
    }
}
