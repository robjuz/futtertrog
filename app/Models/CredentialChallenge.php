<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredentialChallenge extends Model
{
    use HasFactory;

    public const SetPassword = 'set-password';
    public const LegacyPassword = 'legacy-password';
    public const ResetPassword = 'reset-password';
    public const LinkIdentity = 'link-identity';

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
