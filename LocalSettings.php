<?php

# $wgReadOnly = 'Spam issue';


# If you customize your file layout, set $IP to the directory that contains
# the other MediaWiki files. It will be used as a base to locate files.
if( defined( 'MW_INSTALL_PATH' ) ) {
  $IP = MW_INSTALL_PATH;
} else {
  $IP = dirname( __FILE__ );
}

$path = array( $IP, "$IP/includes", "$IP/languages" );
set_include_path( implode( PATH_SEPARATOR, $path ) . PATH_SEPARATOR . get_include_path() );

// This extension installed by toresbe on 2007-05-16.
wfLoadExtension("DynamicPageList4");
wfLoadExtension('ParserFunctions');
wfLoadExtension('Nuke');
$wgUseFileCache = false;
$wgEnableSidebarCache = true;
# Localisation cache. Mounted on the l10n-cache volume by compose.yml so it
# survives container restarts.
$wgCacheDirectory= "$IP/l10n-cache";

# Never rebuild the localisation cache on demand: a cold cache would otherwise
# have every concurrent request racing to regenerate it. compose.yml runs
# rebuildLocalisationCache.php at container start instead, so the cache is
# always warm before Apache accepts a request. After changing MediaWiki
# versions or extensions, restart the container (or run the script by hand).
$wgLocalisationCacheConf['manualRecache'] = true;

if ( $wgCommandLineMode ) {
  if ( isset( $_SERVER ) && array_key_exists( 'REQUEST_METHOD', $_SERVER ) ) {
    die( "This script must be run from the command line\n" );
  }
} elseif ( empty( $wgNoOutputBuffer ) ) {
  ## Compress output if the browser supports it
  if( !ini_get( 'zlib.output_compression' ) ) @ob_start( 'ob_gzhandler' );
}

# podman-compose maps the MediaWiki container's port 80 to localhost:1125.
# The production Apache virtual host proxies the public /wiki prefix to that
# port and retains the short-URL rewrite rules.
$wgServer = "https://gunkies.org";
$wgSitename         = "Computer History Wiki";

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
// nginx proxies /wiki/* as pretty article URLs (rewritten to
// index.php?title=...) and /w/* straight through to the container for
// index.php, load.php, api.php, etc. Keep these in sync with gunkies.org.
$wgScriptPath = "/w";
$wgArticlePath = "/wiki/$1";

$wgLogo = "$wgScriptPath/icon.png";

## For more information on customizing the URLs please see:
## http://www.mediawiki.org/wiki/Manual:Short_URL

#$wgEnableEmail      = true;
#$wgEnableUserEmail  = true;

$wgPasswordSender = "toresbe@gmail.com";

## For a detailed description of the following switches see
## http://meta.wikimedia.org/Enotif and http://meta.wikimedia.org/Eauthent
## There are many more options for fine tuning available see
## /includes/DefaultSettings.php
## UPO means: this is also a user preference option
$wgEnotifUserTalk = true; # UPO
$wgEnotifWatchlist = true; # UPO
$wgEmailAuthentication = false;
$wgEmailConfirmToEdit = false;

#$wgNamespaceProtection[NS_MAIN]     = $wgNamespaceProtection[NS_USER]  =
#$wgNamespaceProtection[NS_PROJECT]  = $wgNamespaceProtection[NS_IMAGE] =
#$wgNamespaceProtection[NS_TEMPLATE] = $wgNamespaceProtection[NS_HELP]  =
#$wgNamespaceProtection[NS_CATEGORY] = array( 'emailconfirmed' );

$wgDBtype           = "mysql";
$wgDBserver         = "mysql";
$wgDBname           = "mediawiki";
$wgDBuser           = "chwikiuser";
$wgDBpassword       = getenv( 'CHWIKI_DB_PASSWORD' );
$wgDBport           = "3306";
$wgDBprefix         = "wiki_";

## Shared memory settings
# https://www.mediawiki.org/wiki/User:Ilmari_Karonen/Performance_tuning
#
# The main object cache (and, following it, sessions) lives in the memcached
# container defined in compose.yml. CACHE_MEMCACHED uses MediaWiki's pure-PHP
# client, so no PECL extension is needed in the mediawiki image.
$wgMainCacheType = CACHE_MEMCACHED;
$wgMemCachedServers = [ 'memcached:11211' ];

# Messages are cheap to regenerate, so they can live in volatile memory.
$wgMessageCacheType = CACHE_MEMCACHED;

# The parser cache stays in the database: it is large and expensive to rebuild,
# and memcached would evict it (and lose it entirely on a restart).
$wgParserCacheType = CACHE_DB;

# The default expiry is one day, which means re-parsing every article daily
# even though almost nothing here changes. Edits invalidate the cache for the
# affected page anyway, so a long expiry costs nothing but disk.
$wgParserCacheExpireTime = 30 * 24 * 3600;

# Cache rendered pages and resources in the browser and in the nginx proxy.
$wgUseCdn = false; # no purge-capable CDN in front of the wiki
$wgCachePages = true;
$wgResourceLoaderMaxage = [
	'versioned' => 30 * 24 * 3600, # ResourceLoader URLs carry a version hash
	'unversioned' => 5 * 60,
];

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads       = true;
$wgUseImageResize      = true;
$wgUseImageMagick = true;
$wgImageMagickConvertCommand = "/usr/bin/convert";

## If you want to use image uploads under safe mode,
## create the directories images/archive, images/thumb and
## images/temp, and make them all writable. Then uncomment
## this, if it's not already uncommented:
# $wgHashedUploadDirectory = false;

$wgLocalInterwikis = [ $wgSitename ];

$wgLanguageCode = "en";

## Default skin: you can change the default skin. Use the internal symbolic
## names, ie 'standard', 'nostalgia', 'cologneblue', 'monobook':
wfLoadSkin('MonoBook');
$wgDefaultSkin = 'MonoBook';

## For attaching licensing metadata to pages, and displaying an
## appropriate copyright notice / icon. GNU Free Documentation
## License and Creative Commons licenses are supported so far.
$wgRightsPage = ""; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl = "http://www.gnu.org/copyleft/fdl.html";
$wgRightsText = "GNU Free Documentation License 1.2";
# $wgRightsIcon = "{$wgScriptPath}/skins/common/images/gnu-fdl.png";
# $wgRightsCode = "gfdl"; # Not yet used

$wgDiff3 = "/usr/bin/diff3";

# Changing this file clears cached pages: MediaWiki does the $wgCacheEpoch
# bump itself when $wgInvalidateCacheOnLocalSettingsChange is on, which it is
# by default. See includes/SetupDynamicConfig.php.

$wgGroupPermissions['*']['edit'] = false;
$wgGroupPermissions['*']['createaccount'] = false;
$wgGroupPermissions['user']['edit']           = true;
$wgGroupPermissions['user']['createpage']           = true;
$wgGroupPermissions['sysop']['createaccount'] = true;

$wgMaxShellMemory = 307200;

# Do not leak stack traces or SQL to visitors. Errors go to the container log
# instead; read them with `podman-compose logs mediawiki`. Set both to true
# temporarily when debugging, and turn them back off afterwards.
$wgShowExceptionDetails = false;
$wgShowSQLErrors = false;

?>
