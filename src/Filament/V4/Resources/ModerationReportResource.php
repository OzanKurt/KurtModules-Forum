<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Filament\V4\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Forum\Enums\ReportState;
use Kurt\Modules\Forum\Filament\V4\Resources\ModerationReportResource\Pages;
use Kurt\Modules\Forum\Models\ModerationReport;

class ModerationReportResource extends Resource
{
    protected static ?string $model = ModerationReport::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $recordTitleAttribute = 'reason';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('reason')
                            ->required()
                            ->maxLength(255),
                        Select::make('state')
                            ->options(ReportState::class)
                            ->required(),
                        Textarea::make('notes')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reason')
                    ->searchable()
                    ->limit(40)
                    ->wrap(),
                TextColumn::make('state')
                    ->badge()
                    ->color(fn (ReportState $state): string => match ($state) {
                        ReportState::Pending => 'warning',
                        ReportState::Resolved => 'success',
                        ReportState::Dismissed => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('post.body')
                    ->label('Post')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('reporter.name')
                    ->label('Reporter')
                    ->toggleable(),
                TextColumn::make('handler.name')
                    ->label('Handled by')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('state')
                    ->options(ReportState::class)
                    ->default(ReportState::Pending->value),
            ])
            ->recordActions([
                Action::make('resolve')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ModerationReport $record): bool => $record->state === ReportState::Pending)
                    ->action(fn (ModerationReport $record) => $record->resolve(static::moderator())),
                Action::make('dismiss')
                    ->icon(Heroicon::XMark)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (ModerationReport $record): bool => $record->state === ReportState::Pending)
                    ->action(fn (ModerationReport $record) => $record->dismiss(static::moderator())),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('resolve')
                        ->label('Resolve selected')
                        ->icon(Heroicon::Check)
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => static::handleEach($records, 'resolve')),
                    BulkAction::make('dismiss')
                        ->label('Dismiss selected')
                        ->icon(Heroicon::XMark)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => static::handleEach($records, 'dismiss')),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function moderator(): Model
    {
        $user = Filament::auth()->user();

        if (! $user instanceof Model) {
            throw new \RuntimeException('A moderating user must be authenticated to resolve or dismiss reports.');
        }

        return $user;
    }

    /**
     * @param  Collection<int, Model>  $records
     */
    protected static function handleEach(Collection $records, string $verb): void
    {
        $moderator = static::moderator();

        foreach ($records as $record) {
            if (! $record instanceof ModerationReport) {
                continue;
            }

            $verb === 'resolve'
                ? $record->resolve($moderator)
                : $record->dismiss($moderator);
        }
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModerationReports::route('/'),
            'edit' => Pages\EditModerationReport::route('/{record}/edit'),
        ];
    }
}
