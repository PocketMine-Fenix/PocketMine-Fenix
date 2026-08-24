<p align="center">
	<b>PocketMine-Fenix</b><br>
	A community continuation of PocketMine-MP with <b>multi-version support</b> for Minecraft: Bedrock Edition
</p>

<p align="center">
	<a href="https://github.com/PocketMine-Fenix/PocketMine-Fenix/actions/workflows/main.yml"><img src="https://github.com/PocketMine-Fenix/PocketMine-Fenix/actions/workflows/main.yml/badge.svg" alt="CI" /></a>
	<a href="https://github.com/PocketMine-Fenix/PocketMine-Fenix/releases/latest"><img alt="GitHub release (latest SemVer)" src="https://img.shields.io/github/v/release/PocketMine-Fenix/PocketMine-Fenix?label=release&sort=semver"></a>
</p>

## What is this?
**PocketMine-Fenix** is a server software for Minecraft: Bedrock Edition written in PHP, continuing the legacy of the original [PocketMine-MP](https://github.com/pmmp/PocketMine-MP) after its discontinuation.

Its headline feature is **native multi-version support**: players on different Minecraft: Bedrock versions can join the same server at the same time — no forced updates, no proxies, no translation plugins.

- 🌉 **Multi-version clients** - supports a wide range of past client versions alongside the latest one (see table below)
- 🧩 **Powerful plugin API** - compatible with existing PocketMine-MP plugins
- 🗺️ **Rich ecosystem** and **large developer community** - most PocketMine-MP plugins work out of the box
- 🌐 **Multi-world support**
- ⤴️ **Easy to update** - adding new Minecraft versions is a documented, low-friction process (see [UPDATING.md](/UPDATING.md))

## Supported Minecraft versions

| Minecraft version | Protocol | Status |
|-------------------|----------|--------|
| 1.26.44 / 1.26.40 | 2169 / 2168 | ✅ Latest (reference implementation) |
| 1.26.30 | 1001 | ✅ Supported |
| 1.26.20 | 975 | ✅ Supported |
| 1.26.10 | 944 | ✅ Supported |
| 1.26.0 | 924 | ✅ Supported |
| ... | ... | ✅ Supported |
| 1.20.0 | 589 | ✅ Oldest supported |

The authoritative list is `ProtocolInfo::ACCEPTED_PROTOCOL` in the bundled protocol library.
By default all supported versions may join; you can restrict them in `pocketmine.yml`:

```yaml
network:
  #List of protocol versions allowed to join. Leave empty/commented to accept every supported version.
  #accepted-protocols: [1001, 975, 924]
```

> ℹ️ Players joining from older versions may see some newer blocks/items as fallbacks (e.g. update blocks), since their client simply doesn't know those features yet.

## :x: PocketMine-Fenix is NOT a vanilla Minecraft server software.
Like PocketMine-MP, it lacks many vanilla features (vanilla world generation, redstone, mob AI...).
If you want plain vanilla survival, use the [official Bedrock server software](https://minecraft.net/download/server/bedrock) instead. Missing features can be added with plugins from [Poggit](https://poggit.pmmp.io/plugins).

## Getting Started
- Download the latest release from [Releases](https://github.com/PocketMine-Fenix/PocketMine-Fenix/releases)
- Run the appropriate start script (`start.cmd` / `start.ps1` on Windows, `start.sh` on Linux/macOS)
- Documentation & plugin development: see the [PocketMine-MP docs](https://devdoc.pmmp.io) and [DevTools](https://github.com/pmmp/DevTools/) (the plugin API is inherited)

## Building from source
See [BUILDING.md](/BUILDING.md).

## Credits
This project stands on the shoulders of giants:

- **[PocketMine-MP](https://github.com/pmmp/PocketMine-MP)** by the pmmp team — the original server software and more than a decade of development. Without them, none of this exists.
- **[NetherGamesMC](https://github.com/NetherGamesMC)** — their multi-version implementation ([BedrockProtocol](https://github.com/NetherGamesMC/BedrockProtocol), [BedrockData](https://github.com/NetherGamesMC/BedrockData) and their PocketMine-MP fork) forms the foundation of the multi-version layer used here.
- All past contributors to both projects.

PocketMine-Fenix is free open-source software licensed under **LGPL-3.0** (see [LICENSE](/LICENSE)), as were its predecessors.

We are not affiliated with Mojang. All brands and trademarks belong to their respective owners.
