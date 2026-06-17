<?php

namespace App\Livewire\Installer;

use App\Enums\TablerIcon;
use App\Livewire\Installer\Steps\DatabaseStep;
use App\Livewire\Installer\Steps\EnvironmentStep;
use App\Livewire\Installer\Steps\RequirementsStep;
use App\Livewire\Installer\Steps\SshSecurityStep;
use App\Models\Node;
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
use Symfony\Component\HttpFoundation\StreamedResponse;
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
            SshSecurityStep::make(),
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
            // La sécurisation SSH était auparavant déclenchée par le hook
            // afterValidation() de SshSecurityStep — mais comme c'est la
            // DERNIÈRE étape du wizard, ce hook n'est jamais appelé : Filament
            // ne l'exécute que lors du passage à l'étape suivante (nextStep),
            // jamais sur le bouton "Terminer" final, qui soumet directement
            // le formulaire. On la déclenche donc ici, en tout premier.
            //
            // On s'arrête volontairement après cette étape (sans lancer les
            // migrations) pour forcer l'affichage de la clé privée et du
            // .bat générés : ils ne sont jamais conservés sur le disque du
            // serveur, donc si on enchaînait directement sur la redirection
            // finale, l'utilisateur les perdrait définitivement. Un second
            // clic sur "Terminer" poursuivra l'installation normalement (la
            // condition ci-dessous devient fausse une fois le résultat rempli).
            if (
                ($this->data['ssh_security']['enabled'] ?? false)
                && blank($this->data['ssh_security']['result']['private_key'] ?? null)
            ) {
                $this->performSshHardening();

                return;
            }

            // Run migrations
            $this->runMigrations();

            // Create admin user & login
            $user = $this->createAdminUser($userCreationService);
            auth()->guard()->login($user, true);

            // Create default node automatically
            $this->createDefaultNode();

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
     * Lance hardenSsh() et range son résultat dans le formulaire pour que
     * SshSecurityStep affiche la clé privée et le .bat générés (ou un message
     * d'erreur en cas d'échec). Centralise la gestion d'erreurs qui vivait
     * auparavant dans le hook afterValidation() de l'étape — devenu mort code
     * puisqu'il n'est jamais appelé sur la dernière étape d'un wizard.
     */
    protected function performSshHardening(): void
    {
        try {
            $result = $this->hardenSsh((int) $this->data['ssh_security']['port']);
            $this->data['ssh_security']['result'] = $result;

            Notification::make()
                ->title(trans('installer.ssh_security.success', ['port' => $result['port']]))
                ->success()
                ->persistent()
                ->send();
        } catch (Exception $exception) {
            report($exception);

            Notification::make()
                ->title(trans('installer.ssh_security.exceptions.failed'))
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * Crée automatiquement un node Wings local avec les paramètres par défaut.
     * Utilise le domaine du panel comme FQDN et SSL si le panel est en HTTPS.
     */
    protected function createDefaultNode(): void
    {
        try {
            $host = parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost();
            $scheme = request()->isSecure() ? 'https' : 'http';

            Node::create([
                'name'                => 'Main Node',
                'fqdn'                => $host,
                'scheme'              => $scheme,
                'behind_proxy'        => false,
                'public'              => true,
                'maintenance_mode'    => false,
                'memory'              => 0,
                'memory_overallocate' => 0,
                'disk'                => 0,
                'disk_overallocate'   => 0,
                'cpu'                 => 0,
                'cpu_overallocate'    => 0,
                'upload_size'         => 256,
                'daemon_base'         => '/var/lib/pelican/volumes',
                'daemon_sftp'         => 2022,
                'daemon_listen'       => 8080,
                'daemon_connect'      => 8080,
            ]);
        } catch (Exception $exception) {
            report($exception);
            // Non bloquant : le node peut être créé manuellement ensuite
        }
    }

    /**
     * Télécharge la clé privée SSH générée par le wizard en tant que fichier
     * (gecko_id_ed25519, sans extension, format OpenSSH). Le contenu est lu
     * depuis l'état du formulaire Livewire — il n'est plus sur le disque
     * serveur à ce stade (hardenSsh() l'a déjà effacé après affichage).
     */
    public function downloadPrivateKey(): StreamedResponse
    {
        $key      = $this->data['ssh_security']['result']['private_key']  ?? '';
        $filename = $this->data['ssh_security']['result']['key_filename']  ?? 'gecko_ed25519';

        return response()->streamDownload(
            fn () => print($key),
            $filename,
            ['Content-Type' => 'application/octet-stream'],
        );
    }

    /**
     * Télécharge le raccourci de connexion SSH (.bat Windows) généré par le
     * wizard. Le fichier doit être placé à côté de la clé privée et
     * double-cliqué pour se connecter automatiquement au serveur.
     */
    public function downloadBat(): StreamedResponse
    {
        $bat      = $this->data['ssh_security']['result']['bat']           ?? '';
        // key_filename = "gecko-monserveur.fr_ed25519" → bat = "gecko-monserveur.fr.bat"
        $keyFile  = $this->data['ssh_security']['result']['key_filename']  ?? 'gecko-server_ed25519';
        $filename = preg_replace('/_ed25519$/', '', $keyFile) . '.bat';

        return response()->streamDownload(
            fn () => print($bat),
            $filename,
            ['Content-Type' => 'application/octet-stream'],
        );
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

        // Le script n'écrit QUE le chemin de la clé sur stdout (tout le reste —
        // logs de progression — part sur stderr) ; on ne garde que la dernière
        // ligne non vide par sécurité, au cas où une sortie parasite s'invite.
        $outputLines = preg_split('/\r?\n/', trim($process->getOutput()));
        $keyPath = trim((string) end($outputLines));
        $privateKey = file_get_contents($keyPath);

        if ($privateKey === false) {
            throw new Exception("Clé générée introuvable : {$keyPath}");
        }

        // La clé privée ne doit pas rester sur le disque du serveur une fois
        // affichée à l'utilisateur : il doit la sauvegarder lui-même.
        @unlink($keyPath);
        @unlink($keyPath . '.pub');

        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost();

        // Nom de fichier unique par serveur : "gecko-monserveur.fr_ed25519"
        // — évite tout écrasement si l'utilisateur gère plusieurs serveurs
        // Gecko ou s'il possède déjà une clé nommée "gecko_id_ed25519".
        $safeHost = preg_replace('/[^a-z0-9._-]/i', '_', $host);
        $keyFileName = "gecko-{$safeHost}_ed25519";

        $bat = implode("\r\n", [
            '@echo off',
            sprintf('title Connexion SSH - %s', $host),
            sprintf('ssh -i "%%~dp0%s" -p %d %s@%s', $keyFileName, $port, $sshUser, $host),
            'pause',
        ]);

        return [
            'port'          => $port,
            'private_key'   => $privateKey,
            'key_filename'  => $keyFileName,
            'bat'           => $bat,
        ];
    }

}
