<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V3\Resources;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Kurt\Modules\Forum\Filament\V3\Resources\ThreadResource\Pages;
use Kurt\Modules\Forum\Models\Thread;

class ThreadResource extends Resource
{
    protected static ?string $model = Thread::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull(),
                        Select::make('board_id')
                            ->relationship('board', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Board'),
                        TextInput::make('score')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),

                Section::make('Moderation')
                    ->schema([
                        Toggle::make('is_pinned')
                            ->label('Pinned'),
                        Toggle::make('is_locked')
                            ->label('Locked'),
                        Toggle::make('is_hidden')
                            ->label('Hidden'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('board.name')
                    ->label('Board')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('Author')
                    ->toggleable(),
                IconColumn::make('is_pinned')
                    ->label('Pinned')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_locked')
                    ->label('Locked')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_hidden')
                    ->label('Hidden')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('reply_count')
                    ->label('Replies')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_post_at')
                    ->label('Last post')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('board_id')
                    ->relationship('board', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Board'),
                TernaryFilter::make('is_pinned')
                    ->label('Pinned'),
                TernaryFilter::make('is_locked')
                    ->label('Locked'),
                TernaryFilter::make('is_hidden')
                    ->label('Hidden'),
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
     * @return array<class-string, mixed>
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListThreads::route('/'),
            'create' => Pages\CreateThread::route('/create'),
            'edit' => Pages\EditThread::route('/{record}/edit'),
        ];
    }
}
