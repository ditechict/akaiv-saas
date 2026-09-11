<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrganizationsRelationManager extends RelationManager
{
    protected static string $relationship = 'organizations';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('role')->options([
                'owner' => 'Owner',
                'billing_admin' => 'Billing admin',
                'workspace_manager' => 'Workspace manager',
                'member_write' => 'Member (write)',
                'member_read' => 'Member (read)',
                'auditor' => 'Auditor',
            ])->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('slug')->toggleable(),
                TextColumn::make('pivot.role')->label('Role')->badge(),
                TextColumn::make('pivot.joined_at')->label('Joined')->dateTime(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('role')->options([
                            'owner' => 'Owner',
                            'billing_admin' => 'Billing admin',
                            'workspace_manager' => 'Workspace manager',
                            'member_write' => 'Member (write)',
                            'member_read' => 'Member (read)',
                            'auditor' => 'Auditor',
                        ])->required(),
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DetachAction::make(),
            ])
            ->bulkActions([]);
    }
}
