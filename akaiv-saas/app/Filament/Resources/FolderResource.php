<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FolderResource\Pages;
use App\Models\Folder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FolderResource extends Resource
{
    protected static ?string $model = Folder::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Documents';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Folder')->schema([
                TextInput::make('name')->required()->maxLength(255),
                Select::make('parent_folder_id')
                    ->label('Parent folder')
                    ->relationship('parent', 'name', fn ($query) => $query)
                    ->searchable()
                    ->preload(),
                Select::make('workspace_id')
                    ->label('Workspace')
                    ->relationship('workspace', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('case_id')
                    ->label('Case')
                    ->relationship('case', 'title')
                    ->searchable()
                    ->preload(),
                Toggle::make('is_system')->label('System folder'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->wrap(),
                TextColumn::make('parent.name')->label('Parent')->toggleable(),
                TextColumn::make('workspace.name')->label('Workspace')->toggleable(),
                TextColumn::make('case.title')->label('Case')->toggleable(),
                TextColumn::make('depth')->numeric()->sortable(),
                TextColumn::make('documents_count')->counts('documents')->label('Documents')->sortable(),
                IconColumn::make('is_system')->label('System')->boolean()->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('workspace')->relationship('workspace', 'name'),
                SelectFilter::make('case')->relationship('case', 'title'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFolders::route('/'),
            'create' => Pages\CreateFolder::route('/create'),
            'edit' => Pages\EditFolder::route('/{record}/edit'),
        ];
    }
}
