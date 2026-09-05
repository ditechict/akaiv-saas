<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Documents';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Document metadata')->schema([
                TextInput::make('friendly_name')->required()->maxLength(500),
                TextInput::make('original_filename')->required()->maxLength(500),
                TextInput::make('folio_number')->maxLength(200),
                Textarea::make('description')->columnSpanFull(),
                Select::make('status')->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'archived' => 'Archived',
                    'quarantined' => 'Quarantined',
                ])->required(),
                DatePicker::make('retention_date'),
            ])->columns(2),
            Section::make('File')->schema([
                FileUpload::make('storage_path')
                    ->label('Document file')
                    ->disk('s3')
                    ->directory('documents')
                    ->visibility('private')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'text/plain',
                        'text/csv',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->maxSize(262144)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('friendly_name')->searchable()->sortable()->wrap(),
                TextColumn::make('original_filename')->label('Filename')->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('mime_type')->label('Type')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('size_bytes')->label('Size')->numeric()->sortable(),
                TextColumn::make('retention_date')->date()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'uploading' => 'Uploading',
                    'quarantined' => 'Quarantined',
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'archived' => 'Archived',
                    'deleted' => 'Deleted',
                ]),
                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Document $record): string => URL::temporarySignedRoute(
                        'documents.preview',
                        now()->addMinutes(10),
                        ['document' => $record->uuid],
                    ))
                    ->openUrlInNewTab()
                    ->visible(fn (Document $record): bool => $record->virus_scanned && ! $record->virus_found && $record->status !== 'deleted'),
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}