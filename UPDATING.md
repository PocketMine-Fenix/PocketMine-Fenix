# Updating PocketMine-Fenix

This guide explains how to keep PocketMine-Fenix working with new and old
Minecraft: Bedrock Edition versions. No deep PHP knowledge is required to
follow the release process; adding brand-new game versions requires more work
(see section 2).

---

## 1. Releasing a new server version (no new game support)

Use this when you want to ship bug fixes or plugin-facing improvements.

1. Make your changes on a feature branch and open a Pull Request into `stable`.
2. Add a changelog file `changelogs/<version>.md` describing user-visible changes.
3. In `src/VersionInfo.php`, set `BASE_VERSION` to the version being released.
   - The release CI triggers automatically when a PR touching
     `src/VersionInfo.php` is merged into `stable`.
4. CI builds `PocketMine-Fenix.phar` and creates a **draft** GitHub release.
   Review it, then publish it manually.

---

## 2. Adding support for a new Minecraft: Bedrock version

The multi-version layer comes from two Composer packages maintained in the
NetherGamesMC organisation:

| Package | Role |
|---------|------|
| `nethergamesmc/bedrock-protocol` | Packet definitions + per-protocol encoders/decoders |
| `nethergamesmc/bedrock-data` | Per-protocol static data (block states, items, biomes...) |

Both are pinned to exact commits in `composer.json` for reproducible builds.

### When NetherGames updates their libraries first (easiest path)

1. Check whether `NetherGamesMC/BedrockProtocol` has a commit adding the new
   protocol (look for a new `PROTOCOL_1_XX_XX` constant in `src/ProtocolInfo.php`
   plus entries in `ACCEPTED_PROTOCOL`).
2. Do the same check for `NetherGamesMC/BedrockData` (new per-version data files).
3. Update the pinned commits in `composer.json` and run:

   ```
   composer update nethergamesmc/bedrock-protocol nethergamesmc/bedrock-data --with-all-dependencies
   ```

4. Run `composer update-codegen` (regenerates `generated/` from new data).
5. Search the codebase for `match` statements on `$protocolId` / calls like
   `getItemSchemaId()` / `PATHS` tables (`BlockTranslator`,
   `ItemTypeDictionaryFromDataHelper`, `ItemTagToIdMap`) and add an entry for
   the new protocol where the compiler/static analysis points you.
6. Run `vendor/bin/phpstan analyze` and the PHPUnit tests, then follow section 1.

### When nobody has updated the libraries yet (self-hosted path)

Fork both libraries into the `PocketMine-Fenix` organisation, add the new
protocol there, then change the `repositories` URLs in `composer.json` to point
at the Fenix forks. The upstream projects to reference when implementing a new
protocol are:

- pmmp/BedrockProtocol & pmmp/BedrockData (reference single-version implementations)
- CloudburstMC/Protocol (Java reference codec definitions)
- The Mojang changelog / community protocol documentation (e.g. wiki.vg successors)

### Version compatibility rules of thumb

- Every supported client needs its **own protocol number** registered in the
  protocol library's `ACCEPTED_PROTOCOL`.
- Clients older than the oldest supported protocol are rejected automatically.
- To **remove** support for old versions, restrict via `pocketmine.yml`
  (`network.accepted-protocols`) — no code changes needed.

---

## 3. Pulling upstream PocketMine-MP bug/security fixes

pmmp/PocketMine-MP is archived, but forks may continue receiving fixes.

1. Add the upstream remote once:
   `git remote add upstream https://github.com/pmmp/PocketMine-MP.git`
2. `git fetch upstream stable`
3. Cherry-pick relevant commits, resolving conflicts carefully around the
   multi-version network layer (files under `src/network/mcpe/` diverge the most).
4. Run phpstan + tests before merging.

---

## 4. Verifying a build locally (Windows)

```
php C:\tools\composer.phar install --no-dev --classmap-authoritative
php -dphar.readonly=0 build/server-phar.php --git <commit-hash>
```

Boot test:

```
echo stop | php PocketMine-Fenix.phar --no-wizard --data=<empty-dir> --plugins=<empty-dir>
```

Expected output includes:
`Multi-version support: accepting client protocols from 589 up to 1001 (28 versions supported)`
