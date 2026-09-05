# Computer History Wiki

Container configuration for the production [Computer History Wiki](https://gunkies.org/wiki/Main_Page). It runs MediaWiki 1.43 and MySQL 8, with MediaWiki exposed locally on port 1125 for the host Apache server to proxy.

## Layout

- `compose.yml` defines the MediaWiki, MySQL and memcached services.
- `LocalSettings.php` contains the site configuration.
- `php-opcache.ini` overrides the image's OPcache limits, which are too small
  for MediaWiki.
- `extensions/` contains the locally installed MediaWiki extensions and is mounted read-only.
- Uploaded files are mounted from the image dataset (currently the `file01:/util/chwiki-images` NFS export).
- MySQL data is kept in the `mysql-data` container volume.
- The localisation cache is kept in the `l10n-cache` container volume (~670 MB;
  it holds every language).

## Caching

MediaWiki's main object cache (and with it sessions and the message cache) lives
in the `memcached` service, configured through `$wgMainCacheType` and
`$wgMemCachedServers`. It holds 512 MB and is not reachable from outside the
compose network. The parser cache deliberately stays in MySQL: it is large,
expensive to rebuild, and would be evicted or lost on a memcached restart.

The cache is purely derived data, so it is safe to drop at any time:

```sh
podman-compose restart memcached
```

Note that restarting memcached logs everyone out, since sessions live there too.
Editing `LocalSettings.php` bumps `$wgCacheEpoch`, which invalidates cached
pages automatically.

Two caches matter as much as the object cache and are easy to overlook:

- **OPcache.** The `mediawiki` image ships PHP's generic recommended settings,
  which cap the opcode cache at 4000 files against roughly 8400 PHP files in
  the tree, so most of the code is recompiled on every request.
  `php-opcache.ini` raises the limits and is mounted so that it sorts after the
  image's own `opcache-recommended.ini`.
- **The localisation cache.** `manualRecache` is on, so MediaWiki never
  regenerates it mid-request. The container rebuilds it at start instead:
  around 22 seconds on an empty volume, about a second once it is warm.

Not currently done: caching rendered pages for anonymous readers in a reverse
proxy. memcached saves database queries but still runs PHP for every request;
a purge-capable proxy in front (Varnish, with `$wgUseCdn` and `$wgCdnServers`)
would skip PHP entirely for anonymous traffic. It needs care to avoid serving
cached pages to logged-in users, so it is deliberately left as separate work.

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

The localisation cache also needs rebuilding after such a change. Restarting
the container does it, or run it directly:

```sh
podman-compose exec mediawiki php maintenance/run.php rebuildLocalisationCache
```

## Debugging

`$wgShowExceptionDetails` and `$wgShowSQLErrors` are off, so visitors do not see
stack traces or SQL. Errors go to the container log:

```sh
podman-compose logs mediawiki
```

Turn both on in `LocalSettings.php` when you need detail in the browser, and
turn them back off afterwards.
