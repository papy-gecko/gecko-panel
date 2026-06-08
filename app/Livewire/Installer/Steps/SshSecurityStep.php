<?php

namespace App\Livewire\Installer\Steps;

use App\Enums\TablerIcon;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;

class SshSecurityStep
{
    public static function make(): Step
    {
        return Step::make('ssh_security')
            ->label(trans('installer.ssh_security.title'))
            ->icon(TablerIcon::Lock)
            ->schema([
                Toggle::make('ssh_security.enabled')
                    ->label(trans('installer.ssh_security.fields.enabled'))
                    ->hintIcon(TablerIcon::QuestionMark, trans('installer.ssh_security.fields.enabled_help'))
                    ->live()
                    ->default(true),
                TextInput::make('ssh_security.port')
                    ->label(trans('installer.ssh_security.fields.port'))
                    ->hintIcon(TablerIcon::QuestionMark, trans('installer.ssh_security.fields.port_help'))
                    ->numeric()
                    ->minValue(1024)
                    ->maxValue(65535)
                    ->default(random_int(20000, 29999))
                    ->required(fn (Get $get) => $get('ssh_security.enabled'))
                    ->visible(fn (Get $get) => $get('ssh_security.enabled'))
                    ->disabled(fn (Get $get) => filled($get('ssh_security.result.private_key'))),
                Textarea::make('ssh_security.result.private_key')
                    ->label(trans('installer.ssh_security.fields.private_key'))
                    ->hintIcon(TablerIcon::QuestionMark, trans('installer.ssh_security.fields.private_key_help'))
                    ->rows(8)
                    ->readOnly()
                    ->extraInputAttributes(['style' => 'font-family: ui-monospace, monospace; font-size: 0.75rem'])
                    ->visible(fn (Get $get) => filled($get('ssh_security.result.private_key'))),
                Textarea::make('ssh_security.result.bat')
                    ->label(trans('installer.ssh_security.fields.bat'))
                    ->hintIcon(TablerIcon::QuestionMark, trans('installer.ssh_security.fields.bat_help'))
                    ->rows(4)
                    ->readOnly()
                    ->extraInputAttributes(['style' => 'font-family: ui-monospace, monospace; font-size: 0.75rem'])
                    ->visible(fn (Get $get) => filled($get('ssh_security.result.bat'))),
            ]);
            // Pas de afterValidation() ici : Filament n'appelle ce hook que
            // lors du passage à l'étape SUIVANTE (voir Wizard::nextStep()) —
            // jamais sur le bouton "Terminer" de la dernière étape, qui
            // soumet directement le formulaire. hardenSsh() est donc déclenché
            // depuis PanelInstaller::submit() (voir performSshHardening()),
            // qui s'exécute bien à ce moment-là.
    }
}
