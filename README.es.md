<p align="center">
	<b>PocketMine-Fenix</b><br>
	Software de servidor para Minecraft Bedrock con <b>soporte multiversi&oacute;n nativo</b>: jugadores en distintas versiones del juego pueden jugar juntos en el mismo servidor.<br>
	Continuaci&oacute;n comunitaria, libre y de c&oacute;digo abierto de <b>PocketMine-MP</b>.
</p>

<p align="center">
	[English](README.md) | <b>Espa&ntilde;ol</b>
</p>

## &iquest;Qu&eacute; es PocketMine-Fenix?

**PocketMine-Fenix** es un software de servidor para *Minecraft: Edici&oacute;n Bedrock* escrito en PHP.
Contin&uacute;a el legado del original [PocketMine-MP](https://github.com/pmmp/PocketMine-MP) tras su cierre, y su caracter&iacute;stica principal es el **soporte multiversi&oacute;n integrado**: clientes desde **cualquier versi&oacute;n de Minecraft Bedrock 1.20.0 hasta la m&aacute;s reciente** pueden entrar al mismo servidor al mismo tiempo &mdash; sin actualizaciones forzadas, sin proxies externos y sin plugins de traducci&oacute;n.

Si administras un servidor de Bedrock y tus jugadores est&aacute;n repartidos entre versiones viejas y nuevas, PocketMine-Fenix les permite jugar juntos mientras sigues usando el enorme ecosistema de **plugins de PocketMine-MP** (Poggit funciona directamente).

### Caracter&iacute;sticas principales

| Caracter&iacute;stica | Descripci&oacute;n |
|----------------------|-------------|
| &#127754; **Clientes multiversi&oacute;n** | ~30 versiones de protocolo soportadas a la vez |
| &#128268; **API de plugins PMMP 5.x** | Los plugins existentes de PocketMine funcionan sin cambios |
| &#128506; **Multi-mundo** | Varios mundos por servidor, formatos LevelDB y Anvil |
| &#9889; **Rendimiento** | PHP 8.1-8.5, compresi&oacute;n as&iacute;ncrona, generaci&oacute;n multi-hilo |
| &#128295; **F&aacute;cil de actualizar** | Comando `updatepm` integrado y proceso documentado |

## Versiones de Minecraft soportadas

| Versi&oacute;n | Protocolo | Estado |
|---------|-----------|--------|
| 1.26.44 / 1.26.40 | 2169 / 2168 | &#9989; M&aacute;s reciente (implementaci&oacute;n de referencia) |
| 1.26.30 | 1001 | &#9989; Soportada |
| 1.26.20 &ndash; 1.26.0 | 975 &ndash; 924 | &#9989; Soportadas |
| Serie 1.21.x | 685 &ndash; 898 | &#9989; Soportadas |
| Serie 1.20.x | 589 &ndash; 671 | &#9989; Las m&aacute;s antiguas soportadas |

Puedes restringir qu&eacute; versiones entran con `network.accepted-protocols` en `pocketmine.yml`.

> &#8505;&#65039; Los jugadores en versiones anteriores pueden ver algunos bloques/&iacute;tems nuevos como bloques de actualizaci&oacute;n, porque su cliente a&uacute;n no conoce esas funciones.

## Preguntas frecuentes

<details>
<summary><b>&iquest;Es compatible con los plugins de PocketMine-MP?</b></summary>

S&iacute;. PocketMine-Fenix mantiene la API de plugins 5.x de PocketMine-MP: los plugins hechos para PMMP funcionan tal cual; inst&aacute;lalos en la carpeta <code>plugins/</code> o desc&aacute;rgalos de <a href="https://poggit.pmmp.io/plugins">Poggit</a>.
</details>

<details>
<summary><b>&iquest;Pueden entrar jugadores con versiones antiguas?</b></summary>

S&iacute; &mdash; es justo su funci&oacute;n principal. Clientes desde 1.20.0 hasta la &uacute;ltima versi&oacute;n se conectan al mismo mundo a la vez. La traducci&oacute;n de paquetes, paletas de bloques y tablas de &iacute;tems se maneja autom&aacute;ticamente por versi&oacute;n.
</details>

<details>
<summary><b>&iquest;Puedo importar mundos de Java Edition?</b></summary>

Parcialmente. Los mundos de <b>Java 1.12.2 o anteriores</b> se leen y convierten autom&aacute;ticamente al formato Bedrock en la primera carga (los originales se respaldan). Los mundos modernos (1.18+) usan nuestro lector paletizado nativo: los bloques vanilla est&aacute;ndar se convierten correctamente; los que PM a&uacute;n no implementa aparecen como aire.
</details>

<details>
<summary><b>&iquest;Puede actualizarse solo?</b></summary>

S&iacute;. Escribe <code>updatepm</code> en la consola o como OP dentro del juego: descarga la &uacute;ltima release y la deja lista; luego solo reinicia. Adem&aacute;s avisa a los OPs al entrar cuando hay una versi&oacute;n nueva.
</details>

<details>
<summary><b>&iquest;Windows o Linux?</b></summary>

Ambos. Descarga <code>PocketMine-Fenix.phar</code> de las Releases, un binario de PHP compatible, y usa <code>start.ps1</code>/<code>start.cmd</code> (Windows) o <code>start.sh</code> (Linux/macOS).
</details>

## &#10060; NO es un software de servidor vanilla.
Como PocketMine-MP, carece de muchas funciones vanilla (generaci&oacute;n vanilla, redstone, IA de mobs...).
Si quieres survival vanilla puro, usa el software oficial de Mojang. Lo que falta puede a&ntilde;adirse con plugins de [Poggit](https://poggit.pmmp.io/plugins).

## Documentaci&oacute;n

| Documento | Prop&oacute;sito |
|-----------|-----------|
| [UPDATING.md](/UPDATING.md) | C&oacute;mo actualizar; c&oacute;mo se agrega cada nueva versi&oacute;n del juego |
| [TESTING.md](/TESTING.md) | Lista de pruebas manuales tras actualizar |
| [BUILDING.md](/BUILDING.md) | Compilar desde el c&oacute;digo fuente |
| [CONTRIBUTING.md](/CONTRIBUTING.md) | Estilo de c&oacute;digo, ramas y PRs |

## Cr&eacute;ditos
PocketMine-Fenix se construye sobre el trabajo del equipo original de PocketMine-MP y la comunidad.
Licenciado bajo **LGPL-3.0** (ver LICENSE).

No estamos afiliados con Mojang ni Microsoft. "Minecraft" es una marca de Mojang Synergies AB.
