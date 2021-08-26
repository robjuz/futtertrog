<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Deposit.
 *
 * @property int $id
 * @property int $user_id
 * @property int $value
 * @property string|null $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $status
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit query()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereValue($value)
 * @mixin \Eloquent
 * @noinspection PhpFullyQualifiedNameUsageInspection
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class Deposit extends Model
{
    protected $guarded = [];

    const STATUS_PROCESSING = 'processing';
    const STATUS_OK = 'ok';

    /**
     * Set the value in cent.
     *
     * @param  int  $value
     * @return void
     */
    public function setValueAttribute($value)
    {
        $this->attributes['value'] = is_float($value) ? intval(100 * $value) : $value;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
