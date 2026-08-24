# 🧪 Guía de pruebas manuales — PocketMine-Fenix

Lista de verificación para probar a mano las funciones nuevas y existentes.
Arranca el servidor desde la carpeta `test-server` con `start.ps1`.

---

## 1. Arranque e identidad

| # | Prueba | Resultado esperado |
|---|--------|--------------------|
| 1.1 | Iniciar el servidor | Banner muestra: `PocketMine-Fenix version 5.46.0` |
| 1.2 | Observar línea tras "Starting Minecraft..." | `Multi-version support: accepting client protocols from 589 up to 2169 (30 versions supported)` |
| 1.3 | Consola durante el arranque | **Ya no aparece** el spam de `Cyclic Garbage Collector Run #1...` de cada worker |
| 1.4 | Mensaje final de arranque | Solo enlaces propios: *Source code* (GitHub Fenix), *Free plugins* (Poggit), *Bug reporting* — sin Discord/docs de pmmp |

## 2. Comandos nuevos

| # | Prueba | Resultado esperado |
|---|--------|--------------------|
| 2.1 | `version` en consola | Muestra además: `Multi-version support: 30 client protocols supported (589 - 2169)` |
| 2.2 | `updatepm` en consola | Si estás al día: *"You are already running the latest version"* |
| 2.3 | `upm` (alias) | Igual que 2.2 |
| 2.4 | Jugador sin OP escribe `/updatepm` | Mensaje de falta de permiso |

## 3. Multiversión (lo importante)

| # | Prueba | Resultado esperado |
|---|--------|--------------------|
| 3.1 | Entrar con cliente **más reciente** | Entra sin problemas; consola registra su conexión |
| 3.2 | Entrar con un cliente **antiguo** (ej. 1.21.x) | También entra y puede jugar junto al moderno |
| 3.3 | Línea de consola al conectar cada jugador | `[Player: Nombre] Connected with Minecraft 1.26.44 (protocol 2169) on Android` (versión/protocolo/plataforma reales del cliente) |
| 3.4 | `status` con jugadores conectados | Nueva sección `Client versions:` agrupando por versión (ej. `1.26.44 x2, 1.21.90 x1`) + rango de protocolos soportados |
| 3.5 | Ver el servidor en la lista de servidores del juego | El nombre termina en `[MV]` |

## 4. Autoupdater

| # | Prueba | Resultado esperado |
|---|--------|--------------------|
| 4.1 | Con todo al día, arrancar | Sin avisos falsos (silencioso) |
| 4.2 | Poner `auto-updater.enabled: false` en pocketmine.yml y arrancar | No intenta ninguna comprobación |
| 4.3 | Cuando salga una release nueva y un OP entre | Recibe un mensaje privado dorado: *"PocketMine-Fenix X.Y.Z is available! Use /updatepm..."* |
| 4.4 | `/updatepm` cuando HAY versión nueva | Descarga, reemplaza el phar y pide reiniciar; tras reiniciar queda actualizado |

> 4.3/4.4 solo se pueden probar cuando publiquemos una versión más nueva que la instalada.

## 5. Configuración (toggles)

Todas en `pocketmine.yml`:

| Clave | Default | Efecto al cambiarla |
|-------|---------|---------------------|
| `network.accepted-protocols` | `[]` (todas) | Lista tipo `[1001, 2169]`: solo esas versiones entran |
| `network.query-multiversion-tag` | `true` | `false` quita el `[MV]` de la lista de servidores |
| `auto-updater.enabled` | `true` | `false` desactiva comprobaciones de updates |
| `auto-updater.notify-ops-on-join` | `true` | `false` deja de avisar a los OPs al entrar |
| `auto-report.enabled` | `false` ahora | Los crashes ya no se suben a ningún archivo externo (se guardan en `crashdumps/`) |

## 6. Regresión (que nada se haya roto)

| # | Prueba | Resultado esperado |
|---|--------|--------------------|
| 6.1 | Romper/colocar bloques, moverse, saltar | Fluidos, sin kicks |
| 6.2 | Abrir inventario, craftear, horno | Funciona (recetas nuevas van por UUID en clientes nuevos) |
| 6.3 | Comandos clásicos: `plugins`, `status`, `seed`, `time set day` | Responden normal |
| 6.4 | Chat entre jugadores de versiones distintas | Todos ven los mensajes |
| 6.5 | Matar al jugador / caídas / mobs | Comportamiento normal |

---

**Si algo falla:** anota el número de prueba, copia el texto exacto de la consola
y lo revisamos juntos.
