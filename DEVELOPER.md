# Developer Guide

This guide provides information for developers working on the Hubleto Framework.

## Project Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/hubleto/framework.git
   cd framework
   ```

2. Install PHP dependencies using Composer:
   ```bash
   composer install
   ```

## Dependencies

The project relies on the following key dependencies:

*   `illuminate/database`: ^12
*   `illuminate/pagination`: ^12
*   `laravel/serializable-closure`: ^2
*   `monolog/monolog`: ^3
*   `php`: ^8.2
*   `symfony/yaml`: ^7
*   `twig/twig`: ^3
*   `phpmailer/phpmailer`: ^6

Development dependencies include:

*   `phpstan/phpstan`: ^2
*   `phpstan/phpstan-deprecation-rules`: ^2
*   `friendsofphp/php-cs-fixer`: ^3
*   `stichoza/google-translate-php`: ^5

## Testing

To run tests and ensure code quality, use the following commands:

*   **PHPStan (Static Analysis):**
    ```bash
    ./vendor/bin/phpstan analyse src
    ```

*   **PHP-CS-Fixer (Code Style):**
    ```bash
    ./vendor/bin/php-cs-fixer fix src --rules=@PSR12
    ```

*   **Unit Tests:**
    (Add instructions for running unit tests here once a testing framework is established.)
