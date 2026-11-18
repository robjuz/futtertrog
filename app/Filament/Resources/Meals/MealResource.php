<?php

namespace App\Filament\Resources\Meals;

use App\Actions\Admin\{ImportMeals, Meals};
use App\Filament\Resources\{AdminResource, Fields, PanelAction};
use App\Filament\Resources\Meals\Pages\ManageMeals;
use App\Models\Meal;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\{DatePicker, DateTimePicker, Select, Textarea, TextInput, Toggle};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MealResource extends AdminResource
{
    protected static ?string $slug = 'offers';
    protected static ?string $model = Meal::class;
    protected static string $listAbility = 'create';
    protected static ?string $pluralModelLabel = 'Gerichte und Varianten';
    protected static ?string $modelLabel = 'Gericht';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['parent.parent']);
    }

    public static function fields(): array
    {
        return [
            TextInput::make('title')->label('Titel / Variantenname')->required()->maxLength(255),
            Textarea::make('description')->label('Beschreibung'), Fields::cents('price', 'Preis in Cent')->minValue(0),
            DatePicker::make('date')->label('Datum')->required(),
            DateTimePicker::make('can_be_ordered_until')->label('Bestellfrist')->after('date'),
            Select::make('provider')->label('Anbieter')->options(app('mealProviders')),
            Select::make('parent_id')->label('Variante von')->searchable()
                ->options(fn (): array => Meal::whereNull('parent_id')->latest('date')->limit(50)->pluck('title', 'id')->all())
                ->getSearchResultsUsing(fn (string $search): array => Meal::whereNull('parent_id')->where('title', 'like', "%{$search}%")->limit(50)->pluck('title', 'id')->all())
                ->getOptionLabelUsing(fn ($value): ?string => Meal::find($value)?->title),
        ];
    }

    public static function headerActions(): array
    {
        return [
            PanelAction::make('add')->label('Gericht / Variante anlegen')->authorize(fn (): bool => Gate::allows('create', Meal::class))
                ->schema([...static::fields(), Toggle::make('notify')->label('Benutzer benachrichtigen')->default(false)])
                ->action(fn (array $data, Action $action) => PanelAction::run($action, fn () => app(Meals::class)->create(auth()->user(), $data, $data['notify'] ?? false))),
            PanelAction::make('import')->label('Gerichte importieren')->authorize(fn (): bool => Gate::allows('create', Meal::class))
                ->schema([Select::make('provider')->label('Anbieter')->options(app('mealProviders'))->required(), DatePicker::make('date')->label('Datum')->required(), Toggle::make('notify')->label('Benutzer benachrichtigen')->default(false)])
                ->action(fn (array $data, Action $action) => PanelAction::run($action, fn () => app(ImportMeals::class)->execute(auth()->user(), $data['provider'], $data['date'], $data['notify'] ?? false))),
        ];
    }

    private static function authorizeWrite(string $ability, ?Meal $meal = null): void
    {
        Gate::forUser(User::withTrashed()->findOrFail(auth()->id()))->authorize($ability, $meal ?? Meal::class);
    }

    private static function validateParent(array $data, ?Meal $meal = null): void
    {
        if (!empty($data['parent_id'])) {
            $parent = Meal::whereNull('parent_id')->findOrFail($data['parent_id']);
            abort_if($meal && ($parent->is($meal) || $meal->variants()->exists()), 422, 'Eine Variante darf nicht sich selbst oder ein Gericht mit Varianten unterordnen.');
        }
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('date', 'desc')->paginationPageOptions([10, 25, 50])->columns([
            TextColumn::make('title')->label('Gericht / Variante')->searchable()->sortable(),
            TextColumn::make('parent.title')->label('Hauptgericht')->searchable(),
            TextColumn::make('description')->label('Beschreibung')->searchable()->limit(100),
            TextColumn::make('date')->label('Datum')->date('d.m.Y')->sortable(),
            TextColumn::make('price')->label('Preis')->formatStateUsing(fn ($state): string => (string) $state),
            TextColumn::make('provider')->label('Anbieter')->formatStateUsing(fn ($state): string => (string) $state),
        ])->filters([Fields::providerFilter(), Fields::dates(), TernaryFilter::make('parent_id')->label('Variante')->nullable()])->recordActions([
            PanelAction::make('change')->label('Bearbeiten')->authorize(fn (Meal $record): bool => Gate::allows('update', $record))
                ->fillForm(fn (Meal $record): array => array_merge($record->only(['description', 'date', 'can_be_ordered_until', 'parent_id']), ['title' => $record->getRawOriginal('title'), 'price' => (int) $record->price->getAmount(), 'provider' => $record->getRawOriginal('provider')]))
                ->schema(static::fields())->action(fn (array $data, Meal $record, Action $action) => PanelAction::run($action, function () use ($data, $record): void {
                    DB::transaction(function () use ($data, $record): void {
                        $meal = Meal::lockForUpdate()->findOrFail($record->id);
                        static::authorizeWrite('update', $meal);
                        static::validateParent($data, $meal);
                        $meal->update($data);
                    });
                })),
            PanelAction::make('remove')->label('Löschen')->color('danger')->authorize(fn (Meal $record): bool => Gate::allows('delete', $record))
                ->action(fn (Meal $record, Action $action) => PanelAction::run($action, function () use ($record): void {
                    DB::transaction(function () use ($record): void {
                        $meal = Meal::lockForUpdate()->findOrFail($record->id);
                        static::authorizeWrite('delete', $meal);
                        abort_if($meal->orderItems()->exists() || $meal->variants()->whereHas('orderItems')->exists(), 400, 'Das Gericht oder eine Variante wurde bereits bestellt und kann nicht gelöscht werden.');
                        foreach ($meal->variants()->get() as $variant) {
                            static::authorizeWrite('delete', $variant);
                            $variant->delete();
                        }
                        $meal->delete();
                    });
                })),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMeals::route('/')];
    }
}
