<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V3\Resources;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Kurt\Modules\Forum\Enums\BadgeRarity;
use Kurt\Modules\Forum\Filament\V3\Resources\BadgeResource\Pages;
use Kurt\Modules\Forum\Models\Badge;

class BadgeResource extends Resource
{
    protected static ?string $model = Badge::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $recordTitleAttribute = 'name';

    /** @var array<int, string> */
    protected static array $locales = ['en', 'tr'];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Tabs::make('translations')
                            ->tabs(array_map(
                                fn (string $locale): Tab => Tab::make(strtoupper($locale))
                                    ->schema([
                                        TextInput::make("name.{$locale}")
                                            ->label('Name')
                                            ->required($locale === 'en')
                                            ->maxLength(255),
                                        Textarea::make("description.{$locale}")
                                            ->label('Description')
                                            ->rows(3),
                                    ]),
                                static::$locales,
                            ))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make()
                    ->schema([
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255),
                        Select::make('rarity')
                            ->options(BadgeRarity::class)
                            ->default(BadgeRarity::Common)
                            ->required(),
                        TextInput::make('icon')
                            ->maxLength(255)
                            ->helperText('Heroicon name, e.g. heroicon-o-star'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->toggleable(),
                TextColumn::make('rarity')
                    ->badge()
                    ->color(fn (BadgeRarity $state): string => match ($state) {
                        BadgeRarity::Common => 'gray',
                        BadgeRarity::Uncommon => 'success',
                        BadgeRarity::Rare => 'info',
                        BadgeRarity::Legendary => 'warning',
                    })
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('user_badges_count')
                    ->counts('userBadges')
                    ->label('Awarded'),
            ])
            ->filters([
                SelectFilter::make('rarity')
                    ->options(BadgeRarity::class),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBadges::route('/'),
            'create' => Pages\CreateBadge::route('/create'),
            'edit' => Pages\EditBadge::route('/{record}/edit'),
        ];
    }
}
