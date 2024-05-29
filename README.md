## Getting Started

# Installing

- composer init & install via `composer install`

- migrate db `php artisan migrate`

- seed db `php artisan db:seed`

- run the app `php artisan serve`

# Tech Stack

- Auth : Sanctum

# Setup

## VSCode

- Install PHP CS FIXER

## Roles & Permissions

- At the start of the project, you want to setup the roles and permissions.
- You can do this by editing the `database/seeders/PermissionSeeder.php` file.

## Users

- At the start of the project, you want to create default users.
- You can do this by editing the `database/seeders/UserSeeder.php` file.
