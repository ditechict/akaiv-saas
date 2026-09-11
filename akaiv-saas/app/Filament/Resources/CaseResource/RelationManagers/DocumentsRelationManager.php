<?php

namespace App\Filament\Resources\CaseResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $recordTitleAttribute = 'friendly_name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('friendly_name')->label('Document')->searchable()->wrap(),
                TextColumn::make('status')->badge(),
                TextColumn::make('mime_type')->label('Type')->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([])
            ->actions([
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record): string => URL::temporarySignedRoute(
                        'documents.preview',
                        now()->addMinutes(10),
                        ['document' => $record->uuid],
                    ))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }
}
