<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V3\Resources;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Kurt\Modules\Forum\Filament\V3\Resources\PostResource\Pages;
use Kurt\Modules\Forum\Models\Post;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $recordTitleAttribute = 'body';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('thread_id')
                            ->relationship('thread', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Thread'),
                        Textarea::make('body')
                            ->required()
                            ->rows(8)
                            ->columnSpanFull(),
                        Toggle::make('is_root')
                            ->label('Is root (original post)'),
                        TextInput::make('score')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),

                Section::make('Attachments')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('attachments')
                            ->collection('attachments')
                            ->multiple()
                            ->visibility('public'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('body')
                    ->limit(60)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('thread.title')
                    ->label('Thread')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('Author')
                    ->toggleable(),
                IconColumn::make('is_root')
                    ->label('Root')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reported_count')
                    ->label('Reports')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('reported_count', 'desc')
            ->filters([
                TernaryFilter::make('is_root')
                    ->label('Root posts'),
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
