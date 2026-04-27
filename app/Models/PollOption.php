<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollOption extends Model
{
    // Champs remplissables automatiquement.
    protected $fillable = [
        'poll_id',
        'label',
    ];

    // Une option appartient à un seul sondage.
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    // Une option peut recevoir plusieurs votes.
    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }
}