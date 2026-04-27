<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollVote extends Model
{
    // Champs autorisés lors de la création d'un vote.
    protected $fillable = [
        'poll_id',
        'user_id',
        'poll_option_id',
    ];

    // Un vote appartient à un sondage.
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    // Un vote appartient à un utilisateur.
    // Cela permet de savoir qui a voté.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Un vote correspond à une option choisie.
    public function option(): BelongsTo
    {
        return $this->belongsTo(PollOption::class, 'poll_option_id');
    }
}