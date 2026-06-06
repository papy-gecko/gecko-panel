# Contribuer à Gecko Panel

Bienvenue dans le projet Gecko Panel ! Nous sommes ravis que vous souhaitiez contribuer. Ce guide vous aidera à configurer votre environnement de développement, comprendre nos standards de code, et faire votre première contribution.

## Premiers pas

Pour contribuer à Gecko Panel, vous devez avoir une compréhension de base de :

- PHP & Laravel
- Livewire & Filament
- Git & GitHub

## Mise en place de l'environnement de dev

1. **Forkez le dépôt**
2. **Clonez votre fork**
3. **Installez les dépendances** (modules PHP & Composer, puis `composer install`)
4. **Configurez l'environnement** via `php artisan p:environment:setup`
5. **Configurez la base de données** via `php artisan p:environment:database` et lancez les migrations (`php artisan migrate --seed --force`)
6. **Créez votre premier utilisateur admin** via `php artisan p:user:make`
7. **Démarrez votre serveur web** (Nginx ou Apache)

Comme IDE nous recommandons **Visual Studio Code** (gratuit) ou **PhpStorm** (payant).

Pour installer facilement PHP et le serveur web, nous recommandons [Laravel Herd](https://herd.laravel.com/) (Windows & macOS).

## Standards de code

Nous utilisons **PHPStan / Larastan** et **PHP-CS-Fixer / Pint** pour enforcer certains styles et standards de code.

```bash
vendor/bin/phpstan analyse
vendor/bin/pint
```

## Faire une contribution

- Travaillez sur votre propre branche depuis votre fork (**ne faites pas de modifications directement sur `main`**)
- Quand vous êtes prêt, soumettez une **pull request** vers le dépôt Gecko Panel
- Si votre PR est encore en cours ou que vous avez besoin d'aide, marquez-la comme **Draft**
- Faites des PR ciblées et simples — une PR = une fonctionnalité / un fix

## Traductions

- Ajoutez les nouvelles chaînes de traduction uniquement en **français** (langue principale)
- Les autres langues sont héritées de Pelican via [Crowdin](https://crowdin.com/project/pelican-dev)

## Processus de review

Votre pull request sera examinée par les mainteneurs du projet.
Une fois approuvée, elle sera mergée dans `main`.

## Communauté et support

- **Aide** : [Discord](#)
- **Bugs** : [GitHub Issues](https://github.com/papy-gecko/gecko-panel/issues)
- **Fonctionnalités** : [GitHub Discussions](https://github.com/papy-gecko/gecko-panel/discussions)
- **Vulnérabilités de sécurité** : Voir notre [politique de sécurité](SECURITY.md)
