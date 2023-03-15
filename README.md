# Simple Google Spreadsheets Reader

PHP library providing a simple way to load data from Google Spreadsheets.

This is a wrapper for [google/apiclient](https://github.com/googleapis/google-api-php-client), with minimal
additional dependency footprint. It is intended to be easily integrated to any framework or pure PHP.

## Installation

Install using [Composer](https://getcomposer.org/):

```sh
$ composer require ondram/simple-google-reader
```

## Usage

1. [Obtain service account credentials](https://github.com/googleapis/google-api-php-client#authentication-with-service-accounts)
1. Add JSON keys and download JSON file with credentials (save for example as `google_client.json`)
1. Enable Spreadsheet API in Google Cloud Console
1. Share the intended document with your service account, copy spreadsheed ID (from the URL)
1. Make sure to install any package [implementing PSR-6 caching](https://packagist.org/providers/psr/simple-cache-implementation)
1. Also install Slugify - `composer require cocur/slugify`
1. Example usage in pure PHP:
```php
<?php declare(strict_types=1);

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Cocur\Slugify\Slugify;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use OndraM\SimpleGoogleReader\Spreadsheets\Reader;

require_once __DIR__ . '/vendor/autoload.php';

// Create instance of Slugify
$slugify = new Slugify(['rulesets' => ['default', 'czech']]);

// Create instance of Cache, in this case we use FilesystemCache
$cache = new FilesystemCachePool(new Filesystem(new Local(__DIR__ . '/data')));

// Instantiate the Google Client with your credentials
$client = new Google\Client();
$client->setAuthConfig(__DIR__ . '/data/google_client.json');
$client->setSubject('foo@bar.cz'); // name of the user which has right to access the spreadsheet
$client->addScope(\Google_Service_Sheets::SPREADSHEETS);

$reader = new Reader($client, $slugify, $cache);

$rows = $reader->readById('pasteHereGoogleSpreadsheedId', 'optional sheet name');
foreach ($rows as $row) {
    echo $row['column_name'];
    echo $row['another_column'];
}

```

## Changelog
For latest changes see [CHANGELOG.md](CHANGELOG.md) file. This project follows [Semantic Versioning](https://semver.org/).
