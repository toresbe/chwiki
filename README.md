# Computer History Wiki

Container configuration for the production [Computer History Wiki](https://gunkies.org/wiki/Main_Page). It runs MediaWiki 1.43 and MySQL 8, with MediaWiki exposed locally on port 1125 for the host Apache server to proxy.

## Layout

- `compose.yml` defines the MediaWiki and MySQL services.
- `LocalSettings.php` contains the site configuration.
- `extensions/` contains the locally installed MediaWiki extensions and is mounted read-only.
- Uploaded files are mounted from the image dataset (currently the `file01:/util/chwiki-images` NFS export).
- MySQL data is kept in the `mysql-data` container volume.

## Configuration

Create a `.env` file alongside `compose.yml` containing:

```dotenv
CHWIKI_DB_ROOT_PASSWORD=replace-with-a-root-password
CHWIKI_DB_PASSWORD=replace-with-the-wiki-database-password
```

Do not commit this file. The host must also be able to mount the image dataset (currently the `file01` NFS export).

## Running

Start the services with either Podman Compose or Docker Compose:

```sh
podman-compose up -d
# or: docker compose up -d
```

MediaWiki then listens on `http://localhost:1125`. In production, Apache proxies requests under `/wiki` to this port and applies the existing short-URL rewrite rules, producing article URLs such as `/wiki/Main_Page`. The proxy should preserve the original host and HTTPS information.

To inspect or stop the stack:

```sh
podman-compose logs -f
podman-compose down
```

`podman-compose down` retains the database volume. Using `down -v` deletes it, so take a database backup before removing volumes.

After changing MediaWiki versions or extensions, run the database updater:

```sh
podman-compose exec mediawiki php maintenance/run.php update
```
