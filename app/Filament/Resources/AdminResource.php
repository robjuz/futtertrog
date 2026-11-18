<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Resources\Resource;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

abstract class AdminResource extends Resource
{
    protected static string $listAbility = 'viewAny';

    public static function getViewAnyAuthorizationResponse(): Response
    {
        $actor = User::withTrashed()->find(auth()->id());
        if (!$actor || !$actor->can('access-administration')) {
            return Response::deny();
        }

        return Gate::forUser($actor)->inspect(static::$listAbility, static::getModel());
    }

    public static function getEloquentQuery(): Builder
    {
        static::getViewAnyAuthorizationResponse()->authorize();

        return parent::getEloquentQuery();
    }
}
