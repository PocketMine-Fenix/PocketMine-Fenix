# Contributing to PocketMine-Fenix

Code contributions are welcome! Please submit changes using GitHub Pull
Requests against the `stable` branch (or `minor-next` for larger API changes).

## Repository layout

| Path | Contents |
|------|----------|
| `src/` | Server source code (the `pocketmine` namespace) |
| `libs/` | First-party protocol/data libraries used by the server |
| `generated/` | Code generated from data files — regenerate with `composer update-codegen` |
| `tests/` | PHPUnit tests, PHPStan rules and integration test plugins |
| `tools/` | Developer utilities |

Dependencies published on Packagist under the `pocketmine/*` names (RakLib,
BinaryUtils, NBT, Math, etc.) are independent libraries shared with the wider
PocketMine ecosystem; everything version-specific lives in `libs/`.

## Branches

- `stable` - releases and bug fixes
- `minor-next` - next minor release (new features, API additions)

## Coding standards

- **Code must follow the project's PHP-CS-Fixer configuration** (`.php-cs-fixer.php`). CI runs `php-cs-fixer fix --dry-run --diff`.
- **PHPStan level 9 must pass** with no errors: `vendor/bin/phpstan analyze`.
- **PHPUnit tests must pass**: `vendor/bin/phpunit --bootstrap vendor/autoload.php tests/phpunit`.

## Pull requests

1. Fork the repository on GitHub.
2. Create a new branch for your changes.
3. Make your changes and add tests where appropriate.
4. Run the checks above locally.
5. Open a pull request describing what you changed and why.

Thanks for helping improve PocketMine-Fenix!
