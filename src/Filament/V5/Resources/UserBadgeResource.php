<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V5\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Kurt\Modules\Forum\Filament\V5\Resources\UserBadgeResource\Pages;
use Kurt\Modules\Forum\Models\UserBadge;

class UserBadgeResource extends Resource
{
    protected static ?string $model = UserBadge::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->disabled()
                            ->label('User'),
                        Select::make('badge_id')
                            ->relationship('badge', 'name')
                            ->disabled()
                            ->label('Badge'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('badge.name')
                    ->label('Badge')
                    ->sortable(),
                TextColumn::make('badge.rarity')
                    ->label('Rarity')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('awarded_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('awarded_at', 'desc')
            ->filters([
                SelectFilter::make('badge_id')
                    ->relationship('badge', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Badge'),
            ])
            ->actions([
                ViewAction::make(),
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
            'index' => Pages\ListUserBadges::route('/'),
            'view' => Pages\ViewUserBadge::route('/{record}'),
        ];
    }
}
