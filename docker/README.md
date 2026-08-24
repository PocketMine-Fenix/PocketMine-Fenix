# Docker image

Build a PocketMine-Fenix server image locally:

```
docker build -t pocketmine-fenix .
```

Run it:

```
docker run -it -p 19132:19132/udp -v $PWD/data:/data -v $PWD/plugins:/plugins pocketmine-fenix
```

## Environment variables

| Variable | Description |
|----------|-------------|
| `POCKETMINE_ARGS` | Extra arguments passed to the server on startup |

If `$POCKETMINE_PLUGINS` is set, plugins listed there will be downloaded automatically
from Poggit on container start (e.g. `POCKETMINE_PLUGINS="EconomyAPI:5.7.2"`).

## Notes

- The `Dockerfile` builds PHP from scratch using the scripts in `build/php`.
- World data lives in the `/data` volume; keep it persisted between runs.
