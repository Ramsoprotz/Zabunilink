<?php

namespace App\Filament\Pages;

use App\Models\PartnerApiUsage;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ApiPartners extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-key';
    protected static ?string                $navigationLabel = 'API Partners';
    protected static string|UnitEnum|null   $navigationGroup = 'Settings';
    protected static ?int                   $navigationSort  = 16;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->where('role', 'partner'))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('last_used_at')
                    ->label('Last API Call')
                    ->state(fn (User $record) => $record->tokens()->max('last_used_at'))
                    ->dateTime()
                    ->placeholder('Never'),
                TextColumn::make('requests_30d')
                    ->label('Requests (30 days)')
                    ->state(fn (User $record) => PartnerApiUsage::where('user_id', $record->id)
                        ->where('date', '>=', now()->subDays(30)->toDateString())
                        ->sum('requests')),
                TextColumn::make('created_at')
                    ->label('Partner Since')
                    ->date(),
            ])
            ->recordActions([
                Action::make('regenerate')
                    ->label('Regenerate Token')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalDescription('The current token stops working immediately. The partner must switch to the new token.')
                    ->action(function (User $record) {
                        $record->tokens()->delete();
                        $token = $record->createToken('partner-api', ['tenders:read'])->plainTextToken;
                        $this->showTokenNotification($record, $token);
                    }),

                Action::make('revoke')
                    ->label('Revoke Access')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('All tokens are deleted — API access stops immediately. The partner record is kept.')
                    ->action(function (User $record) {
                        $record->tokens()->delete();
                        Notification::make()
                            ->title("API access revoked for {$record->name}.")
                            ->success()
                            ->send();
                    }),

                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->tokens()->delete();
                        $record->delete();
                        Notification::make()
                            ->title('Partner deleted.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No API partners yet')
            ->emptyStateDescription('Create a partner to give an external system read-only access to tender data.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('New Partner')
                ->icon('heroicon-o-plus')
                ->form([
                    TextInput::make('name')
                        ->label('Partner / Company Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('Contact Email')
                        ->email()
                        ->required()
                        ->unique('users', 'email'),
                ])
                ->action(function (array $data) {
                    $user = User::create([
                        'name'     => $data['name'],
                        'email'    => $data['email'],
                        'role'     => 'partner',
                        'password' => bcrypt(Str::random(40)),
                    ]);
                    $user->forceFill(['email_verified_at' => now()])->save();

                    $token = $user->createToken('partner-api', ['tenders:read'])->plainTextToken;
                    $this->showTokenNotification($user, $token);
                }),
        ];
    }

    protected function showTokenNotification(User $user, string $token): void
    {
        Notification::make()
            ->title("API token for {$user->name}")
            ->body("Copy it now — it is shown only once:\n\n{$token}")
            ->persistent()
            ->success()
            ->send();
    }
}
