<p align="center">
	<b>PocketMine-Fenix</b><br>
	Software de servidor para Minecraft Bedrock con <b>soporte multiversión nativo</b>: jugadores en distintas versiones del juego pueden jugar juntos en el mismo servidor.<br>
	Continuación comunitaria, libre y de código abierto de <b>PocketMine-MP</b>.
</p>

<p align="center">
	[English](README.md) | <b>Español</b>
</p>

## ¿Qué es PocketMine-Fenix?

**PocketMine-Fenix** es un software de servidor para *Minecraft: Edición Bedrock* escrito en PHP.
Continúa el legado del original [PocketMine-MP](https://github.com/pmmp/PocketMine-MP) tras su cierre, y su característica principal es el **soporte multiversión integrado**: clientes desde **cualquier versión de Minecraft Bedrock 1.20.0 hasta la más reciente** pueden entrar al mismo servidor al mismo tiempo — sin actualizaciones forzadas, sin proxies externos y sin plugins de traducción.

Si administras un servidor de Bedrock y tus jugadores están repartidos entre versiones viejas y nuevas, PocketMine-Fenix les permite jugar juntos mientras sigues usando el enorme ecosistema de **plugins de PocketMine-MP** (Poggit funciona directamente).

### Características principales

| Característica | Descripción |
|----------------|-------------|
| 🌉 **Clientes multiversión** | ~30 versiones de protocolo soportadas a la vez |
| 🔌 **API de plugins PMMP 5.x** | Los plugins existentes de PocketMine funcionan sin cambios |
| 🗺️ **Multi-mundo** | Varios mundos por servidor, formatos LevelDB y Anvil |
| ⚡ **Rendimiento** | PHP 8.1–8.5, compresión asíncrona, generación multi-hilo |
| 🛠️ **Fácil de actualizar** | Comando `updatepm` integrado y proceso documentado ([UPDATING.md](/UPDATING.md)) |

## Versiones de Minecraft soportadas

| Versión | Protocolo | Estado |
|---------|-----------|--------|
| 1.26.44 / 1.26.40 | 2169 / 2168 | ✅ Más reciente (implementación de referencia) |
| 1.26.30 | 1001 | ✅ Soportada |
| 1.26.20 – 1.26.0 | 975 – 924 | ✅ Soportadas |
| Serie 1.21.x | 685 – 898 | ✅ Soportadas |
| Serie 1.20.x | 589 – 671 | ✅ Las más antiguas soportadas |

Puedes restringir qué versiones entran con `network.accepted-protocols` en `pocketmine.yml`.

> ℹ️ Los jugadores en versiones anteriores pueden ver algunos bloques/ítems nuevos como bloques de actualización, porque su cliente aún no conoce esas funciones.

## Preguntas frecuentes

<details>
<summary><b>¿Es compatible con los plugins de PocketMine-MP?</b></summary>

Sí. PocketMine-Fenix mantiene la API de plugins 5.x de PocketMine-MP: los plugins hechos para PMMP funcionan tal cual; instálalos en la carpeta <code>plugins/</code> o descárgalos de <a href="https://poggit.pmmp.io/plugins">Poggit</a>.
</details>

<details>
<summary><b>¿Pueden entrar jugadores con versiones antiguas?</b></summary>

Sí — es justo su función principal. Clientes desde 1.20.0 hasta la última versión se conectan al mismo mundo a la vez. La traducción de paquetes, paletas de bloques y tablas de ítems se maneja automáticamente por versión.
</details>

<details>
<summary><b>¿Puede actualizarse solo?</b></summary>

Sí. Escribe <code>updatepm</code> en la consola o como OP dentro del juego: descarga la última release y la deja lista; luego solo reinicia el servidor. Además avisa a los OPs al entrar cuando hay una versión nueva (configurable en <code>pocketmine.yml</code>, clave <code>auto-updater.notify-ops-on-join</code>).
</details>

<details>
<summary><b>¿Windows o Linux?</b></summary>

Ambos. Descarga <code>PocketMine-Fenix.phar</code> de las [Releases](https://github.com/PocketMine-Fenix/PocketMine-Fenix/releases), un binario de PHP compatible, y usa <code>start.ps1</code>/<code>start.cmd</code> (Windows) o <code>start.sh</code> (Linux/macOS).
</details>

## :x: NO es un software de servidor vanilla.
Como PocketMine-MP, carece de muchas funciones vanilla (generación vanilla, redstone, IA de mobs...).
Si quieres survival vanilla puro, usa el [software oficial de Mojang](https://www.minecraft.net/download/server/bedrock). Lo que falta puede añadirse con plugins de [Poggit](https://poggit.pmmp.io/plugins).

## Documentación

| Documento | Propósito |
|-----------|-----------|
| [UPDATING.md](/UPDATING.md) | Cómo actualizar; cómo se agrega cada nueva versión del juego |
| [TESTING.md](/TESTING.md) | Lista de pruebas manuales tras actualizar |
| [BUILDING.md](/BUILDING.md) | Compilar desde el código fuente |
| [CONTRIBUTING.md](/CONTRIBUTING.md) | Estilo de código, ramas y PRs |

## Créditos
PocketMine-Fenix se construye sobre el trabajo del equipo original de PocketMine-MP y la comunidad.
Licenciado bajo **LGPL-3.0** (ver [LICENSE](/LICENSE)).

No estamos afiliados con Mojang ni Microsoft. "Minecraft" es una marca de Mojang Synergies AB.
