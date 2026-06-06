<x-filament-panels::page>
    @if($database)
    <div class="space-y-4">
        <x-filament::section>
            <x-slot name="heading">Informations de connexion</x-slot>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Hôte (local / externe)</p>
                    <p class="mt-1 font-mono text-sm select-all">127.0.0.1</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Hôte (Docker / FiveM)</p>
                    <p class="mt-1 font-mono text-sm select-all">172.18.0.1</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Port</p>
                    <p class="mt-1 font-mono text-sm">3306</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Base de données</p>
                    <p class="mt-1 font-mono text-sm select-all">{{ $database->db_name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Utilisateur</p>
                    <p class="mt-1 font-mono text-sm select-all">{{ $database->db_user }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Mot de passe</p>
                    <p class="mt-1 font-mono text-sm select-all">{{ $this->getDecryptedPassword() }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Chaîne de connexion</p>
                    <p class="mt-1 font-mono text-sm select-all break-all">mysql://{{ $database->db_user }}:{{ $this->getDecryptedPassword() }}@127.0.0.1:3306/{{ $database->db_name }}</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Accès phpMyAdmin</x-slot>
            <a href="{{ $this->getPhpMyAdminUrl() }}" target="_blank">
                <x-filament::button icon="tabler-external-link">
                    Ouvrir phpMyAdmin
                </x-filament::button>
            </a>
        </x-filament::section>
    </div>
    @else
        <x-filament::section>
            <p class="text-gray-500">Aucune base de données trouvée pour ce serveur.</p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
