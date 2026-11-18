<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredentialChallenge extends Model
{
    use HasFactory;

    public const SET_PASSWORD = 'set-password';
    public const LEGACY_PASSWORD = 'legacy-password';
    public const RESET_PASSWORD = 'reset-password';
    public const LINK_IDENTITY = 'link-identity';

    protected $guarded = [];

    protected $hidden = ['token_hash', 'pending_subject'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'consumed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
