<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Enter full name'),

                TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->placeholder('user@example.com'),

                TextInput::make('password_hash')
                    ->label('Password')
                    ->password()
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context): bool => $context === 'create')
                    ->minLength(8)
                    ->placeholder('Minimum 8 characters')
                    ->helperText('Leave empty to keep current password (Edit only)'),

                Select::make('role')
                    ->label('Role')
                    ->required()
                    ->options([
                        'superadmin' => 'Super Admin',
                        'admin' => 'Admin',
                        'operator' => 'Operator',
                        'validator' => 'Validator',
                    ])
                    ->native(false)
                    ->placeholder('Select a role'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Only active users can access the panel')
                    ->inline(false),
            ]);
    }
}
