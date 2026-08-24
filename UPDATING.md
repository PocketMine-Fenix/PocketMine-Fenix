# Updating PocketMine-Fenix

This guide explains how to keep PocketMine-Fenix working with new and old
Minecraft: Bedrock Edition versions.

---

## 1. Releasing a new server version (no new game support)

Use this when you want to ship bug fixes or plugin-facing improvements.

1. Make your changes on a feature branch and open a Pull Request into `stable`.
2. Add a changelog file `changelogs/<version>.md` describing user-visible changes.
3. In `src/VersionInfo.php`, set `BASE_VERSION` to the version being released.
   - The release CI triggers automatically when a PR touching
     `src/VersionInfo.php` is merged into `stable`, or when a tag is pushed.
4. CI builds `PocketMine-Fenix.phar` and creates a **draft** GitHub release.
   Review it, then publish it manually.

---

## 2. Adding support for a new Minecraft: Bedrock version

All protocol/data libraries are vendored inside this repository under `libs/`:

| Folder | Role |
|--------|------|
| `libs/bedrock-protocol` | Packet definitions + per-protocol encoders/decoders |
| `libs/bedrock-data` | Per-protocol static data (block states, items, biomes...) |
| `libs/bedrock-block-upgrade-schema` | World save block data upgrade schemas |
| `libs/bedrock-item-upgrade-schema` | World save item data upgrade schemas |

To support a new game version:

1. Update `libs/bedrock-protocol/src/ProtocolInfo.php`: add the new
   `PROTOCOL_1_XX_XX` constant, register it in `ACCEPTED_PROTOCOL`, update
   `CURRENT_PROTOCOL` / version strings, and adapt any packet format changes
   for the new protocol (use community protocol reference projects such as the
   ones other server software rely on, e.g. Cloudburst-based or Java-based
   protocol implementations, to diff the wire format).
2. Update `libs/bedrock-data`: add per-version data files for the new version
   (`canonical_block_states.nbt`, `required_item_list.json`,
   `block_state_meta_map.json`, `item_tags.json`, ...) generated from the new
   client/server build; move the previous latest-version files to their
   `-<version>` suffixed names.
3. Update the per-protocol tables in the server code — search for `match`
   statements on `$protocolId` and `PATHS` tables (`BlockTranslator`,
   `ItemTranslator::getItemSchemaId`, `ItemTypeDictionaryFromDataHelper`,
   `ItemTagToIdMap`) and add entries for the new protocol where static analysis
   points you.
4. Bump the `version` field of each changed `libs/*/composer.json` and run:

   ```
   composer update fenix/bedrock-protocol fenix/bedrock-data fenix/bedrock-item-upgrade-schema fenix/bedrock-block-upgrade-schema
   composer update-codegen
   ```

5. Run `vendor/bin/phpstan analyze` and the PHPUnit tests, then follow section 1.

### Version compatibility rules of thumb

- Every supported client needs its **own protocol number** registered in
  `ACCEPTED_PROTOCOL` inside `libs/bedrock-protocol`.
- Clients older than the oldest supported protocol are rejected automatically.
- To **remove** support for old versions, restrict via `pocketmine.yml`
  (`network.accepted-protocols`) — no code changes needed.

---

## 3. Verifying a build locally (Windows)

```
php C:\tools\composer.phar install --no-dev --classmap-authoritative
php -dphar.readonly=0 build/server-phar.php --git <commit-hash>
```

Boot test:

```
echo stop | php PocketMine-Fenix.phar --no-wizard --data=<empty-dir> --plugins=<empty-dir>
```

Expected output includes:
`Multi-version support: accepting client protocols from 589 up to <latest> (<N> versions supported)`
