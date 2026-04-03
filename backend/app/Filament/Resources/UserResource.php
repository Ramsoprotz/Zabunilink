<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return 'User Management';
    }

    public static function form(Schema $schema): Schema
    {
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('User Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('business_name')
                            ->maxLength(255),
                        Forms\Components\Select::make('role')
                            ->options([
                                'user' => 'User',
                                'support' => 'Support Agent',
                                'admin' => 'Admin',
                            ])
                            ->default('user')
                            ->required()
                            ->visible($isAdmin),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('business_name')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'support' => 'warning',
                        'user' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'support' => 'Support',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'user' => 'User',
                        'support' => 'Support Agent',
                        'admin' => 'Admin',
                    ]),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset User Password')
                    ->modalDescription(fn (User $record) => "Generate a new temporary password for {$record->name} and display it. The user should change it after logging in.")
                    ->action(function (User $record) {
                        $tempPassword = Str::random(10);
                        $record->update(['password' => Hash::make($tempPassword)]);
                        $record->tokens()->delete();

                        Notification::make()
                            ->title("Password reset for {$record->name}")
                            ->body("Temporary password: **{$tempPassword}** — Share this securely with the user.")
                            ->success()
                            ->persistent()
                            ->send();
                    })
                    ->hidden(fn (User $record) => in_array($record->role, ['admin', 'support']) && auth()->user()?->isSupport()),
            ])
            ->bulkActions(
                auth()->user()?->isAdmin()
                    ? [\Filament\Actions\BulkActionGroup::make([\Filament\Actions\DeleteBulkAction::make()])]
                    : []
            )
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function ($query) {
                // Support agents can only see regular users
                if (auth()->user()?->isSupport()) {
                    $query->where('role', 'user');
                }
            });
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubscriptionsRelationManager::class,
            RelationManagers\TenderApplicationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
