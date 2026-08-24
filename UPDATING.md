# Keeping PocketMine-Fenix up to date

This guide is written for **three audiences**. Pick yours:

| You are... | Read |
|------------|------|
| 🎮 A server owner (no programming) | [Section A](#a-for-server-owners-no-programming-needed) |
| 👨‍💻 A developer adding a new Minecraft version | [Section B](#b-for-developers-the-update-pattern) |
| 🤖 An AI agent / automation | [Section C](#c-for-ai-agents-executable-checklist) |

---

## A. For server owners (no programming needed)

### How do I know which Minecraft versions my server accepts?

Run this in your server folder:

```
php PocketMine-Fenix.phar --version
```

You will see something like:

```
Multi-version support: accepting client protocols from 589 up to 2169 (30 versions supported)
```

Everything between those protocol numbers can join your server.
Protocol → game version mapping: https://minecraft.wiki/w/Protocol_version

### A new Minecraft version just came out. What do I do?

Usually **nothing right away**:

1. Wait for a new **Release** here on GitHub
   ([watch releases](https://github.com/PocketMine-Fenix/PocketMine-Fenix/releases) with the *Watch → Custom → Releases* button).
2. Download `PocketMine-Fenix.phar` from the latest release.
3. Stop your server, replace the old `.phar` file with the new one.
4. Start the server. Done — old and new clients can both join.

**Do not delete** your `worlds/`, `players/`, `plugins/` or `pocketmine.yml` folders when updating.

### My players see "Outdated client" / "Outdated server"

| Message | Meaning |
|---------|---------|
| "Outdated client" | The player's Minecraft is too old for this server. Update the game, or lower the accepted versions in `pocketmine.yml`. |
| "Outdated server" | The server doesn't know the player's brand-new version yet. A newer PocketMine-Fenix release is needed — check the Releases page. |

---

## B. For developers: the update pattern

The multi-version engine lives inside this repository under `libs/`:

| Folder | What it does |
|--------|--------------|
| `libs/bedrock-protocol` | Packet classes; each packet knows how to encode/decode for every supported protocol |
| `libs/bedrock-data` | Static per-version data: block palettes (`canonical_block_states*.nbt`), item tables (`required_item_list*.json`), tags, etc. |
| `libs/bedrock-block-upgrade-schema` | Upgrades block data from old world saves |
| `libs/bedrock-item-upgrade-schema` | Upgrades item data from old world saves |

These are consumed by Composer as local path repositories (see `composer.json`),
under the package names `fenix/bedrock-*`. **Cloning this repo gives you everything.**

### The pattern (learned from real migrations: 1.26.30 → 1.26.40/2169)

When Mojang ships version `X.Y.Z` with protocol number `P`:

#### Step 1 — Identify the protocol number

Check https://minecraft.wiki/w/Bedrock_Edition_X.YZ ("Protocol version" in the infobox).
Watch out: hotfix builds sometimes reuse or slightly bump protocols (e.g. 1.26.40 = 2168 but
later client builds of the same cycle used 2169). If that happens, register **both** as separate
constants and use the login-time sniffing trick shown in
`src/network/mcpe/handler/LoginPacketHandler.php` (`PROTOCOL_1_26_44` handling).

#### Step 2 — Update `libs/bedrock-protocol/src/ProtocolInfo.php`

```php
public const CURRENT_PROTOCOL = self::PROTOCOL_1_XX_XX;
// add to ACCEPTED_PROTOCOL[] ...
public const MINECRAFT_VERSION = 'vX.YZ';
public const MINECRAFT_VERSION_NETWORK = 'X.Y.Z';
public const PROTOCOL_1_XX_XX = P;
```

Then diff the wire format against the previous protocol using community protocol reference
implementations (the Java-based ones other server projects use — Cloudburst-style codecs and
Geyser mappings are the usual sources) and adapt each changed packet's `encode()`/`decode()`.
Packet IDs rarely change; field layouts sometimes do.

#### Step 3 — Update `libs/bedrock-data`

1. Rename current unsuffixed files to carry the previous version suffix:
   `canonical_block_states.nbt` → `canonical_block_states-1.26.40.nbt`, same for
   `required_item_list.json`, `block_state_meta_map.json`, `item_tags.json`,
   `block_id_to_item_id_map.json`, etc.
2. Put the new version's generated data in as the unsuffixed files.
3. Bump `"version"` in `libs/bedrock-data/composer.json` (and in the protocol lib's).

> Data generation tooling exists in `tools/generate-bedrock-data-from-packets.php`.

#### Step 4 — Server-side per-protocol tables

Search the codebase — static analysis will also point you there — and add entries for the new
protocol in exactly these places (copy how the previous version is handled):

| File | What to add |
|------|-------------|
| `src/network/mcpe/convert/BlockTranslator.php` | `PATHS[PROTOCOL_1_XX_XX]` (usually `''` for newest) + shift previous latest to its `-suffix` |
| `src/network/mcpe/convert/ItemTypeDictionaryFromDataHelper.php` | `PATHS` entry |
| `src/data/bedrock/ItemTagToIdMap.php` | `PATHS` entry |
| `src/network/mcpe/convert/ItemTranslator.php` | `getItemSchemaId()` match arm |
| `src/data/bedrock/WorldDataVersions.php` | `NETWORK` + `LAST_OPENED_IN` |
| `src/network/mcpe/handler/LoginPacketHandler.php` | only if Mojang reused/bumped protocols mid-cycle |
| Packet `create()` call sites | **only if phpstan complains after step 6** — signature changes are caught automatically |

**Golden rule:** never overwrite an older version's row. Shift the previous "newest" into its
suffixed slot and give the new version the unsuffixed one. That is what keeps old clients working.

#### Step 5 — Refresh dependencies and generated code

```
composer update fenix/bedrock-protocol fenix/bedrock-data fenix/bedrock-item-upgrade-schema fenix/bedrock-block-upgrade-schema --with-all-dependencies
composer update-codegen
```

#### Step 6 — Validation gates (all must pass, in this order)

```
vendor/bin/phpstan analyze --no-progress --memory-limit=2G      # gate 1: catches missed packet API changes
vendor/bin/phpunit --bootstrap vendor/autoload.php tests/phpunit # gate 2: unit tests
bash tests/travis.sh -t4                                         # gate 3: boots a real server & runs TesterPlugin
```

Gate 1 is your safety net: if the new protocol changed any packet constructor,
PHPStan will list every call site you still need to fix.

#### Step 7 — Release

1. Add `changelogs/<version>.md` starting with the header `# <version>`.
2. Set `BASE_VERSION` in `src/VersionInfo.php`.
3. Push to `stable` and push a tag: `git tag X.Y.Z && git push origin X.Y.Z`.
4. CI builds `PocketMine-Fenix.phar` and creates a draft release — review & publish it.

---

## C. For AI agents: executable checklist

You are updating PocketMine-Fenix (repo layout: `src/`, `libs/bedrock-*`) to support a newly
released Minecraft Bedrock version with protocol number `P` and marketing version string `V`.

Work through these steps **in order**. Do not skip gates.

```
[ ] 1. CONFIRM protocol number P from minecraft.wiki page of the version.
[ ] 2. EDIT libs/bedrock-protocol/src/ProtocolInfo.php:
       - add const PROTOCOL_<V_SANITIZED> = P;
       - CURRENT_PROTOCOL = self::PROTOCOL_<V_SANITIZED>;
       - append to ACCEPTED_PROTOCOL before self::CURRENT_PROTOCOL;
       - set MINECRAFT_VERSION='v<V>' and MINECRAFT_VERSION_NETWORK='<V>'.
[ ] 3. DIFF wire format vs previous protocol against a community Java protocol
       implementation; patch encode()/decode() of changed packets, branching on
       $protocolId. Do NOT change behaviour for older protocols.
[ ] 4. SHIFT libs/bedrock-data unsuffixed files to '-<previous-version>' suffixes;
       ADD new version's data as unsuffixed. Files: canonical_block_states.nbt,
       required_item_list.json, block_state_meta_map.json, item_tags.json,
       block_id_to_item_id_map.json, creative/*.json if changed.
[ ] 5. BUMP "version" in libs/*/composer.json for every lib you touched.
[ ] 6. PATCH server tables: BlockTranslator PATHS, ItemTypeDictionaryFromDataHelper
       PATHS, ItemTagToIdMap PATHS, ItemTranslator::getItemSchemaId(),
       WorldDataVersions. Follow the existing previous-version rows as templates.
[ ] 7. RUN: composer update fenix/bedrock-protocol fenix/bedrock-data
       fenix/bedrock-block-upgrade-schema fenix/bedrock-item-upgrade-schema -W
[ ] 8. RUN: composer update-codegen
[ ] 9. GATE: vendor/bin/phpstan analyze --no-progress --memory-limit=2G
       Fix EVERY reported error (usually stale packet create() callsites) before
       continuing. Repeat until clean.
[ ] 10. GATE: vendor/bin/phpunit --bootstrap vendor/autoload.php tests/phpunit
[ ] 11. GATE: bash tests/travis.sh -t4   (boots real server + plugin test suite)
[ ] 12. VERIFY boot output contains:
        "accepting client protocols from 589 up to P"
[ ] 13. WRITE changelogs/<semver>.md with header "# <semver>"; SET BASE_VERSION in
        src/VersionInfo.php; commit; push stable; tag <semver>; publish the draft
        release after CI passes.
```

Rules of engagement:

- Never edit `LICENSE` files or source file LGPL headers.
- Never reorder or remove entries in `ACCEPTED_PROTOCOL` — only append.
- Old protocols must keep behaving exactly as before (per-protocol branches).
- If a step fails, fix before proceeding; never comment out failing code to pass a gate.

---

## Resumen rápido en español

**¿Dueño de un servidor?** Cuando salga una versión nueva de Minecraft: espera a que aparezca una
release aquí, descarga el `PocketMine-Fenix.phar` nuevo, reemplaza el archivo viejo y reinicia.
Tus mundos y plugins no se tocan. Con `php PocketMine-Fenix.phar --version` ves qué versiones
acepta tu servidor.

**¿Desarrollador?** Sigue la sección B: constantes de protocolo en `libs/bedrock-protocol`,
datos en `libs/bedrock-data` (mueve los actuales a sufijo `-<versión-anterior>` y mete los nuevos
sin sufijo), tablas por protocolo en `src/` (la tabla de arriba), y deja que PHPStan te señale lo
que falte. Los tres gates (phpstan → phpunit → travis.sh) deben pasar antes de publicar.
