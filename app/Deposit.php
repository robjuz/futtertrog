<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Deposit.
 *
 * @property int $id
 * @property int $user_id
 * @property float $value
 * @property string|null $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Deposit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Deposit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Deposit query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Deposit whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Deposit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Deposit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Deposit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Deposit whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Deposit whereValue($value)
 * @mixin \Eloquent
 */
class Deposit extends Model
{
    protected $guarded = [];

    const STATUS_PROCESSING = 'processing';
    const STATUS_OK = 'ok';
}
