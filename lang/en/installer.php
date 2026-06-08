<?php

return [
    'title' => 'Panel Installer',
    'requirements' => [
        'title' => 'Server Requirements',
        'sections' => [
            'version' => [
                'title' => 'PHP Version',
                'or_newer' => ':version or newer',
                'content' => 'Your PHP Version is :version.',
            ],
            'extensions' => [
                'title' => 'PHP Extensions',
                'good' => 'All needed PHP Extensions are installed.',
                'bad' => 'The following PHP Extensions are missing: :extensions',
            ],
            'permissions' => [
                'title' => 'Folder Permissions',
                'good' => 'All Folders have the correct permissions.',
                'bad' => 'The following Folders have wrong permissions: :folders',
            ],
        ],
        'exception' => 'Some requirements are missing',
    ],
    'ssh_security' => [
        'title' => 'SSH Security',
        'fields' => [
            'enabled' => 'Secure SSH access',
            'enabled_help' => 'Generates a dedicated SSH key, switches to a custom port and disables password authentication. The default port 22 is the #1 target of automated bots — switching away from it greatly reduces your exposure.',
            'port' => 'New SSH port',
            'port_help' => 'Pick a port between 1024 and 65535. The switch is done safely: the new port is opened and tested before the old one is ever closed, so you cannot be locked out.',
            'private_key' => 'Private key',
            'private_key_help' => 'Download the key using the button below — it will not be shown again and has been removed from the server.',
            'download_key' => 'Download private key (gecko_id_ed25519)',
            'bat' => 'Connection shortcut (.bat)',
            'bat_help' => 'Download the shortcut using the button below and place it next to your private key — double-click it to connect.',
            'download_bat' => 'Download connection shortcut (.bat)',
        ],
        'success' => 'SSH secured on port :port. Save the key and shortcut below, then click "Finish" again to complete the installation — they will not be shown again.',
        'exceptions' => [
            'failed' => 'Could not secure SSH automatically',
        ],
    ],
    'environment' => [
        'title' => 'Environment',
        'fields' => [
            'app_name' => 'App Name',
            'app_name_help' => 'This will be the Name of your Panel.',
            'app_url' => 'App URL',
            'app_url_help' => 'This will be the URL you access your Panel from.',
            'account' => [
                'section' => 'Admin User',
                'email' => 'E-Mail',
                'username' => 'Username',
                'password' => 'Password',
            ],
        ],
    ],
    'database' => [
        'title' => 'Database',
        'driver' => 'Database Driver',
        'driver_help' => 'The driver used for the panel database. We recommend "SQLite".',
        'fields' => [
            'host' => 'Database Host',
            'host_help' => 'The host of your database. Make sure it is reachable.',
            'port' => 'Database Port',
            'port_help' => 'The port of your database.',
            'path' => 'Database Path',
            'path_help' => 'The path of your .sqlite file relative to the database folder.',
            'name' => 'Database Name',
            'name_help' => 'The name of the panel database.',
            'username' => 'Database Username',
            'username_help' => 'The name of your database user.',
            'password' => 'Database Password',
            'password_help' => 'The password of your database user. Can be empty.',
        ],
        'exceptions' => [
            'connection' => 'Database connection failed',
            'migration' => 'Migrations failed',
        ],
    ],
    'egg' => [
        'title' => 'Eggs',
        'no_eggs' => 'No Eggs Available',
        'background_install_started' => 'Egg Install Started',
        'background_install_description' => 'Install of :count eggs has been queued and will continue in the background.',
        'exceptions' => [
            'failed_to_update' => 'Failed to update egg index',
            'no_eggs' => 'No eggs are available to install at this time.',
            'installation_failed' => 'Failed to install selected eggs. Please import them after the installation via the egg list.',
        ],
    ],
    'session' => [
        'title' => 'Session',
        'driver' => 'Session Driver',
        'driver_help' => 'The driver used for storing sessions. We recommend "Filesystem" or "Database".',
    ],
    'cache' => [
        'title' => 'Cache',
        'driver' => 'Cache Driver',
        'driver_help' => 'The driver used for caching. We recommend "Filesystem".',
        'fields' => [
            'host' => 'Redis Host',
            'host_help' => 'The host of your redis server. Make sure it is reachable.',
            'port' => 'Redis Port',
            'port_help' => 'The port of your redis server.',
            'username' => 'Redis Username',
            'username_help' => 'The name of your redis user. Can be empty',
            'password' => 'Redis Password',
            'password_help' => 'The password for your redis user. Can be empty.',
        ],
        'exception' => 'Redis connection failed',
    ],
    'queue' => [
        'title' => 'Queue',
        'driver' => 'Queue Driver',
        'driver_help' => 'The driver used for handling queues. We recommend "Database".',
        'fields' => [
            'done' => 'I have done both steps below.',
            'done_validation' => 'You need to do both steps before continuing!',
            'crontab' => 'Run the following command to set up your crontab. Note that <code>www-data</code> is your webserver user. On some systems this username might be different!',
            'service' => 'To setup the queue worker service you simply have to run the following command.',
        ],
    ],
    'exceptions' => [
        'write_env' => 'Could not write to .env file',
        'migration' => 'Could not run migrations',
        'create_user' => 'Could not create admin user',
    ],
    'finish' => 'Finish',
];
