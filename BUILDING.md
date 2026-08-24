# Building PocketMine-Fenix from source

## Requirements

- PHP 8.1 or newer (64-bit CLI), with several non-standard extensions required by
  the server. The full list is in [`composer.json`](/composer.json) under `require`
  (entries starting with `ext-`).
- [Composer](https://getcomposer.org/)

You can use any PHP build that includes the required extensions, including the
community prebuilt PHP binaries commonly used for PocketMine-style servers.

## Cloning the repository

```
git clone https://github.com/PocketMine-Fenix/PocketMine-Fenix.git
cd PocketMine-Fenix
```

No extra repositories or submodules are needed — protocol/data libraries are
included in this repository under `libs/`.

## Building `PocketMine-Fenix.phar`

Run:

```
composer make-server
```

This installs dependencies and drops a `PocketMine-Fenix.phar` into the current
working directory.

## Running from source code

Instead of building a phar, you can run directly from a source checkout:

```
composer install --no-dev --classmap-authoritative
php src/PocketMine.php
```

## Verifying a build

See [UPDATING.md](/UPDATING.md) section 4 for a quick local boot test.
