<p align="center">
	<b>PocketMine-Fenix</b><br>
	Minecraft Bedrock server software with <b>native multiversion support</b> -- players on different game versions can play together on the same server.<br>
	Free, open-source community continuation of <b>PocketMine-MP</b>.
</p>

<p align="center">
	<a href="https://github.com/PocketMine-Fenix/PocketMine-Fenix/actions/workflows/main.yml"><img src="https://github.com/PocketMine-Fenix/PocketMine-Fenix/actions/workflows/main.yml/badge.svg" alt="CI status" /></a>
	<a href="https://github.com/PocketMine-Fenix/PocketMine-Fenix/releases/latest"><img alt="Latest release" src="https://img.shields.io/github/v/release/PocketMine-Fenix/PocketMine-Fenix?label=release&sort=semver"></a>
	<a href="https://github.com/PocketMine-Fenix/PocketMine-Fenix/releases/latest"><img alt="Downloads" src="https://img.shields.io/github/downloads/PocketMine-Fenix/PocketMine-Fenix/total?label=downloads"></a>
	<img alt="Supported client versions" src="https://img.shields.io/badge/client%20versions-1.20.0%20to%20latest-blue">
</p>

English | [Espanol](README.es.md)

## What is PocketMine-Fenix?

**PocketMine-Fenix** is a server software for *Minecraft: Bedrock Edition* written in PHP.
It continues the legacy of the original [PocketMine-MP](https://github.com/pmmp/PocketMine-MP) after its discontinuation,
and its defining feature is **built-in multiversion support**: clients running **any Minecraft Bedrock version from 1.20.0 up to the latest release** can join the same server at the same time -- no forced updates, no external proxies, no translation plugins.

If you host a Bedrock server and your players are spread across old and new game versions, PocketMine-Fenix lets them all play together while you keep using the huge ecosystem of **PocketMine-MP plugins** (Poggit works out of the box).

### Key features

| Feature | Description |
|---------|-------------|
| **Multiversion clients** | Supports ~30 client protocol versions simultaneously |
| **Plugin API (5.x)** | Full API compatibility with PocketMine-MP plugins from Poggit and elsewhere |
| **Multi-world support** | Multiple worlds per server, LevelDB and Anvil formats |
| **Performance** | PHP 8.1-8.5, async compression, multi-threaded generation |
| **Easy updates** | Documented process to add every new Minecraft version |
| **Java world import** | Reads and converts Java Edition worlds (see details below) |

## Supported Minecraft versions

Players can join from these versions (older clients are gracefully rejected with an update message):

| Minecraft version | Protocol | Status |
|-------------------|----------|--------|
| 1.26.44 / 1.26.40 | 2169 / 2168 | Latest (reference implementation) |
| 1.26.30 | 1001 | Supported |
| 1.26.20 | 975 | Supported |
| 1.26.10 | 944 | Supported |
| 1.26.0 | 924 | Supported |
| 1.21.x series | 685 - 898 | Supported |
| 1.20.x series | 589 - 671 | Oldest supported |

You can restrict which versions may join via `network.accepted-protocols` in `pocketmine.yml`.

Note: Players joining from older versions may see some newer blocks/items as fallbacks (e.g. update blocks), since their client simply doesn't know those features yet.

## Frequently asked questions (FAQ)

<details>
<summary><b>Is this compatible with PocketMine-MP plugins?</b></summary>

Yes. PocketMine-Fenix keeps the PocketMine-MP 5.x plugin API. Plugins built for PMMP 5 work as-is; install them in the `plugins/` folder or get them from [Poggit](https://poggit.pmmp.io/plugins).
</details>

<details>
<summary><b>Can players on older Minecraft versions join?</b></summary>

Yes -- that is the core feature. Clients from 1.20.0 through the latest release connect to the same world at the same time. Packet translation, block palettes and item tables are handled automatically per version.
</details>

<details>
<summary><b>Can I import Java Edition worlds?</b></summary>

Partially. Worlds from Java 1.12.2 or older are read and automatically converted to the Bedrock format on first load (the original files are backed up). Modern Java worlds (1.18+) use our native paletted chunk reader: standard vanilla blocks convert correctly; blocks that PM hasn't implemented yet appear as air. The server detects all cases and never crashes.
</details>

<details>
<summary><b>Can the server update itself?</b></summary>

Yes. Type `updatepm` in the console or as an OP in-game: it downloads the newest release and swaps it into place, then you just restart. The server also warns ops on join when an update is available (toggleable in `pocketmine.yml`).
</details>

<details>
<summary><b>Is it free? Is it open source?</b></summary>

100% free software under LGPL-3.0, like the original PocketMine-MP. No ads, no paywalls.
</details>

<details>
<summary><b>Windows or Linux?</b></summary>

Both. Download `PocketMine-Fenix.phar` from Releases, grab a matching PHP binary, and use `start.ps1`/`start.cmd` (Windows) or `start.sh` (Linux/macOS).
</details>

## It is NOT a vanilla Minecraft server software.
Like PocketMine-MP, it lacks many vanilla features (vanilla world generation, redstone, mob AI...).
If you want plain vanilla survival, use the [official Bedrock server software](https://www.minecraft.net/download/server/bedrock) instead. Missing features can be added with plugins from [Poggit](https://poggit.pmmp.io/plugins).

## Getting Started
1. Download the latest release from [Releases](https://github.com/PocketMine-Fenix/PocketMine-Fenix/releases)
2. Run the appropriate start script (`start.cmd` / `start.ps1` on Windows, `start.sh` on Linux/macOS)
3. Connect from any supported Minecraft Bedrock client version

Plugin development: plugins using the PocketMine-MP 5.x API work as-is -- find them on [Poggit](https://poggit.pmmp.io/plugins).

## Documentation

| Document | Purpose |
|----------|---------|
| [UPDATING.md](/UPDATING.md) | How to update (owners), how new game versions are added (developers/AI) |
| [TESTING.md](/TESTING.md) | Manual test checklist after updating |
| [BUILDING.md](/BUILDING.md) | Compile from source |
| [CONTRIBUTING.md](/CONTRIBUTING.md) | Code style, branches and PR guidelines |

## Building from source
See [BUILDING.md](/BUILDING.md).

## Credits
PocketMine-Fenix is built on the work of the original PocketMine-MP team and community contributors.
Licensed under **LGPL-3.0** (see [LICENSE](/LICENSE)).

We are not affiliated with Mojang or Microsoft. "Minecraft" is a trademark of Mojang Synergies AB. This software is not approved by or associated with Mojang.
