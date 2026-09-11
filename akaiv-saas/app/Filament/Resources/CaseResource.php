<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CaseResource\Pages;
use App\Filament\Resources\CaseResource\RelationManagers\DocumentsRelationManager;
use App\Models\CaseFile;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CaseResource extends Resource
{
    protected static ?string $model = CaseFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Documents';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Case identity')->schema([
                TextInput::make('case_number')->maxLength(150)->unique(ignoreRecord: true),
                TextInput::make('suit_number')->maxLength(150)->unique(ignoreRecord: true),
                TextInput::make('title')->required()->maxLength(500)->columnSpanFull(),
                Select::make('workspace_id')->label('Workspace')
                    ->relationship('workspace', 'name')->searchable()->preload(),
                TextInput::make('court_name')->maxLength(255),
                TextInput::make('bench_judge_name')->maxLength(255),
                TextInput::make('jurisdiction')->maxLength(150),
                Select::make('status')->options([
                    'open' => 'Open',
                    'closed' => 'Closed',
                    'archived' => 'Archived',
                ])->default('open')->required(),
            ])->columns(2),
            Section::make('Parties')->schema([
                Repeater::make('parties')
                    ->schema([
                        Select::make('role')->options([
                            'Claimant' => 'Claimant',
                            'Defendant' => 'Defendant',
                            'Petitioner' => 'Petitioner',
                            'Respondent' => 'Respondent',
                            'Witness' => 'Witness',
                        ])->required(),
                        TextInput::make('name')->required()->maxLength(500),
                    ])->columns(2)->columnSpanFull(),
            ]),
            Section::make('Dates & notes')->schema([
                DatePicker::make('date_filed'),
                DatePicker::make('date_judgment'),
                Textarea::make('notes')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('case_number')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->sortable()->wrap(),
                TextColumn::make('court_name')->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('date_filed')->date()->sortable(),
                TextColumn::make('documents_count')->counts('documents')->label('Documents')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'open' => 'Open',
                    'closed' => 'Closed',
                    'archived' => 'Archived',
                ]),
                SelectFilter::make('workspace')->relationship('workspace', 'name'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCases::route('/'),
            'create' => Pages\CreateCase::route('/create'),
            'edit' => Pages\EditCase::route('/{record}/edit'),
        ];
    }
}
