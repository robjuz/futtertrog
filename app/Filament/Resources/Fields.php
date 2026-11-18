<?php

namespace App\Filament\Resources;

use App\Models\Meal;
use App\Models\Order;
use App\Rules\MealInOrderGroup;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Fields
{
    public static function user(string $name = 'user_id', string $label = 'Benutzer', bool $withTrashed = false): Select
    {
        $query = fn (): Builder => User::query()->when($withTrashed, fn (Builder $query): Builder => $query->withTrashed());

        return Select::make($name)->label($label)->searchable()
            ->options(fn (): array => $query()->orderBy('name')->limit(50)->pluck('name', 'id')->all())
            ->getSearchResultsUsing(fn (string $search): array => $query()->where(fn (Builder $q): Builder => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))->orderBy('name')->limit(50)->pluck('name', 'id')->all())
            ->getOptionLabelUsing(fn ($value): ?string => $query()->find($value)?->name)
            ->getOptionLabelsUsing(fn (array $values): array => $query()->whereKey($values)->pluck('name', 'id')->all())
            ->exists('users', 'id');
    }

    public static function meal(?Order $order = null): Select
    {
        $query = fn (): Builder => ($order ? MealInOrderGroup::meals($order) : Meal::query())
            ->doesntHave('variants')->with('parent.parent')->latest('date');
        $labels = fn (Builder $query): array => $query->limit(50)->get()->mapWithKeys(fn (Meal $meal): array => [$meal->id => $meal->date->format('d.m.Y').' · '.$meal->title])->all();

        return Select::make('meal_id')->label('Gericht / Variante')->searchable()->required()
            ->options(fn (): array => $labels($query()))
            ->getSearchResultsUsing(fn (string $search): array => $labels($query()->where(fn (Builder $q): Builder => $q->where('title', 'like', "%{$search}%")->orWhereHas('parent', fn (Builder $q): Builder => $q->where('title', 'like', "%{$search}%")))))
            ->getOptionLabelUsing(function ($value) use ($query): ?string {
                $meal = $query()->find($value);

                return $meal ? $meal->date->format('d.m.Y').' · '.$meal->title : null;
            });
    }

    public static function cents(string $name = 'value', string $label = 'Betrag in Cent'): TextInput
    {
        return TextInput::make($name)->label($label)->helperText('Ganzzahl in Cent, z. B. 1234 für 12,34 €.')
            ->required()->numeric()->rules(['integer'])->step(1)->dehydrateStateUsing(fn ($state): int => (int) $state);
    }

    public static function operationKey(): Hidden
    {
        return Hidden::make('operation_key')->default(fn (): string => (string) Str::uuid())->required()->rules(['uuid']);
    }

    public static function providerFilter(?string $relationship = null): SelectFilter
    {
        return SelectFilter::make('provider')->label('Anbieter')->options(['internal' => 'Intern', ...app('mealProviders')])
            ->query(function (Builder $query, array $data) use ($relationship): Builder {
                if (empty($data['value'])) {
                    return $query;
                }
                $apply = fn (Builder $q): Builder => $data['value'] === 'internal'
                    ? $q->where(fn (Builder $q): Builder => $q->whereNull('provider')->orWhere('provider', 'Internal'))
                    : $q->where('provider', $data['value']);

                return $relationship ? $query->whereHas($relationship, $apply) : $apply($query);
            });
    }

    public static function dates(string $column = 'date', ?string $relationship = null): Filter
    {
        return Filter::make('dates')->label('Zeitraum')->schema([
            DatePicker::make('from')->label('Von'), DatePicker::make('until')->label('Bis')->afterOrEqual('from'),
        ])->query(function (Builder $query, array $data) use ($column, $relationship): Builder {
            $apply = fn (Builder $q): Builder => $q
                ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate($column, '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate($column, '<=', $date));

            return $relationship && (!empty($data['from']) || !empty($data['until']))
                ? $query->whereHas($relationship, $apply) : ($relationship ? $query : $apply($query));
        });
    }
}
