<?php

namespace App\Livewire\Installer;

use App\Enums\TablerIcon;
use App\Livewire\Installer\Steps\DatabaseStep;
use App\Livewire\Installer\Steps\EnvironmentStep;
use App\Livewire\Installer\Steps\RequirementsStep;
use App\Livewire\Installer\Steps\SshSecurityStep;
use App\Models\User;
use App\Services\Helpers\LanguageService;
use App\Services\Users\UserCreationService;
use App\Traits\CheckMigrationsTrait;
use App\Traits\EnvironmentWriterTrait;
use App\Traits\Filament\CanCustomizeSteps;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Symfony\Component\Process\Process;

/**
 * @property Schema $form
 */
class PanelInstaller extends SimplePage implements HasForms
{
    use CanCustomizeSteps;
    use CheckMigrationsTrait;
    use EnvironmentWriterTrait;
    use InteractsWithForms;

    /** @var array<string, mixed> */
    public array $data = [];

    protected string $view = 'filament.pages.installer';

    public function getTitle(): string
    {
        return trans('installer.title');
    }

    public function getMaxContentWidth(): Width|string
    {
        return Width::ScreenTwoExtraLarge;
    }

    public static function isInstalled(): bool
    {
        return config('app.installed');
    }

    public function mount(): void
    {
        abort_if(self::isInstalled(), 404);

        $this->form->fill();
    }

    /** @return Component[] */
    protected function getFormSchema(): array
    {
        return [
            Grid::make()
                ->schema([
                    $this->getLanguageComponent(),
                ]),
            Wizard::make($this->getSteps())
                ->persistStepInQueryString()
                ->nextAction(fn (Action $action) => $action->keyBindings('enter'))
                ->submitAction(new HtmlString(Blade::render(<<<'BLADE'
                    <x-filament::button
                        type="submit"
                        size="sm"
                        wire:loading.attr="disabled"
                    >
                        {{ trans('installer.finish') }}
                        <x-filament::loading-indicator wire:loading class="h-4 w-4" />
                    </x-filament::button>
                BLADE))),
        ];
    }

    /**
     * @return Step[]
     *
     * @throws Exception
     */
    protected function getDefaultSteps(): array
    {
        return [
            // Cache, queue, sessions and egg selection are deliberately left
            // out of the wizard: install.sh already wrote sane working
            // defaults (file cache, sync queue, file sessions) into .env, and
            // asking users to pick these (or worse, run extra SSH commands
            // for a queue worker service) defeats the point of a "click and
            // configure from the browser" installer. Power users can still
            // tweak these later from .env or the panel's settings.
            RequirementsStep::make(),
            EnvironmentStep::make($this),
            DatabaseStep::make($this),
            SshSecurityStep::make($this),
        ];
    }

    protected function getLanguageComponent(): Component
    {
        return Select::make('language')
            ->hiddenLabel()
            ->prefix(trans('profile.language'))
            ->prefixIcon(TablerIcon::Flag)
            ->required()
            ->live()
            ->default('en')
            ->selectablePlaceholder(false)
            ->options(fn (LanguageService $languageService) => $languageService->getAvailableLanguages())
            ->afterStateUpdated(fn ($state, Application $app) => $app->setLocale($state ?? config('app.locale')))
            ->columnStart(4);
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    public function submit(UserCreationService $userCreationService): void
    {
        try {
            // Run migrations
            $this->runMigrations();

            // Create admin user & login
            $user = $this->createAdminUser($userCreationService);
            auth()->guard()->login($user, true);

            // Cache/queue/session steps were removed from the wizard (see
            // getDefaultSteps): write the same safe defaults install.sh
            // already put in .env, written again here at the very end to
            // avoid "page expired" errors if SESSION_DRIVER changes.
            //
            // APP_INSTALLED is written LAST, only once migrations and the
            // admin account both succeeded: writing it first (as upstream
            // does) means a slow migration that gets killed by a reverse
            // proxy timeout (e.g. Cloudflare's 524) leaves the panel marked
            // as installed with no schema and no admin user — locked out of
            // both the installer (404) and the panel itself.
            $this->writeToEnvironment([
                'CACHE_STORE' => 'file',
                'QUEUE_CONNECTION' => 'sync',
                'SESSION_DRIVER' => 'file',
                'APP_INSTALLED' => 'true',
            ]);

            // Redirect to the panel's home (domain root) rather than straight
            // into /admin: the user is now logged in, and the app panel's
            // own navigation gets them to the admin panel if they need it.
            $this->redirect(url('/'));
        } catch (Halt) {
        }
    }

    public function writeToEnv(string $key): void
    {
        try {
            $variables = array_get($this->data, $key);
            $variables = array_filter($variables); // Filter array to remove NULL values
            $this->writeToEnvironment($variables);
        } catch (Exception $exception) {
            report($exception);

            Notification::make()
                ->title(trans('installer.exceptions.write_env'))
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            throw new Halt(trans('installer.exceptions.write_env'));
        }
    }

    public function runMigrations(): void
    {
        try {
            Artisan::call('migrate', [
                '--force' => true,
                '--seed' => true,
            ]);
        } catch (Exception $exception) {
            report($exception);

            Notification::make()
                ->title(trans('installer.database.exceptions.migration'))
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            throw new Halt(trans('installer.exceptions.migration'));
        }

        if (!$this->hasCompletedMigrations()) {
            Notification::make()
                ->title(trans('installer.database.exceptions.migration'))
                ->danger()
                ->persistent()
                ->send();

            throw new Halt(trans('installer.database.exceptions.migration'));
        }
    }

    public function createAdminUser(UserCreationService $userCreationService): User
    {
        try {
            $userData = array_get($this->data, 'user');
            $userData['root_admin'] = true;

            return $userCreationService->handle($userData);
        } catch (Exception $exception) {
            report($exception);

            Notification::make()
                ->title(trans('installer.exceptions.create_user'))
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            throw new Halt(trans('installer.exceptions.create_user'));
        }
    }

    /**
     * Sécurise l'accès SSH du serveur : génère une paire de clés dédiée,
     * bascule sshd sur un port personnalisé et coupe l'authentification par
     * mot de passe. Délègue à bin/ssh-setup.sh (lancé via sudo, voir
     * /etc/sudoers.d/gecko-ssh-setup) car ces opérations nécessitent root —
     * www-data ne peut exécuter QUE ce script précis, rien d'autre.
     *
     * @return array{port: int, private_key: string, bat: string}
     *
     * @throws Exception
     */
    public function hardenSsh(int $port): array
    {
        $sshUser = config('app.ssh_harden_user');
        $outDir = storage_path('app/ssh-setup');
        $script = base_path('bin/ssh-setup.sh');

        $process = new Process(['sudo', $script, $sshUser, (string) $port, $outDir]);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new Exception(trim($process->getErrorOutput()) ?: trim($process->getOutput()));
        }

        $keyPath = trim($process->getOutput());
        $privateKey = file_get_contents($keyPath);

        if ($privateKey === false) {
            throw new Exception("Clé générée introuvable : {$keyPath}");
        }

        // La clé privée ne doit pas rester sur le disque du serveur une fois
        // affichée à l'utilisateur : il doit la sauvegarder lui-même.
        @unlink($keyPath);
        @unlink($keyPath . '.pub');

        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost();
        $keyFileName = 'gecko_id_ed25519';

        $bat = implode("\r\n", [
            '@echo off',
            sprintf('title Connexion SSH - %s', $host),
            sprintf('ssh -i %%~dp0%s -p %d %s@%s', $keyFileName, $port, $sshUser, $host),
            'pause',
        ]);

        return [
            'port' => $port,
            'private_key' => $privateKey,
            'bat' => $bat,
        ];
    }

}
