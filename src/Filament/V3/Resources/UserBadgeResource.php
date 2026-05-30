<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V3\Resources;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Kurt\Modules\Forum\Filament\V3\Resources\UserBadgeResource\Pages;
use Kurt\Modules\Forum\Models\UserBadge;

class UserBadgeResource extends Resource
{
    protected static ?string $model = UserBadge::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
