# Simple Google Spreadsheets Reader

PHP library providing a simple way to load data from Google Spreadsheets.

The aim is to provide universal low-level access to the data, without any additional features like data manipulation
or formatting. It means you can implement any domain object mapping or data processing in your application.

This library is a wrapper for [google/apiclient](https://github.com/googleapis/google-api-php-client),
with minimal additional dependency footprint. It is intended to be easily integrated to any framework or pure PHP.

## Installation

Install using [Composer](https://getcomposer.org/):

```sh
$ composer require ondram/simple-google-reader
```

## Usage

1. [Obtain service account credentials](https://github.com/googleapis/google-api-php-client#authentication-with-service-accounts) for your project
1. In service account details in IAM admin console open Keys settings and add JSON keys. Download generated JSON file with credentials (save for example as `google_client.json`).
1. Enable [Google Sheets API](https://console.cloud.google.com/apis/library/sheets.googleapis.com) in Google Cloud Console for your project
1. Share the intended document with your service account, copy document ID (from the URL)
1. Make sure to install any package [implementing PSR-6 caching](https://packagist.org/providers/psr/simple-cache-implementation)
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
$client = new \Google\Client();
$client->setAuthConfig(__DIR__ . '/data/google_client.json');
// If you service account has domain-wide access, you need to use setSubject to set the name of the user
// which will the service account impersonate. This user must have right to access the spreadsheet.
$client->setSubject('foo@bar.cz');
$client->addScope(\Google\Service\Sheets::SPREADSHEETS);

$reader = new Reader($client, $slugify, $cache);

$rows = $reader->readById('pasteHereGoogleSpreadsheedId', '[optional sheet name]'/*, optional cache TTL*/);
foreach ($rows as $row) {
    echo $row['column_name'];
    echo $row['another_column'];
}
```

## Reading spreadsheets

For spreadsheets, it is required that the first row contains column names. The library will use these names (converted to slugs)
as keys in the associative array. Consider table:

| First column | Second column |
|--------------|---------------|
| Value 1      | Foo           |
| Value 2      | Bar           |

This will be read as:

```php
[
    ['first_column' => 'Value 1', 'second_column' => 'Foo'],
    ['first_column' => 'Value 2', 'second_column' => 'Bar'],
]
```

Empty rows are skipped. There is currently (intentional) limitation to read columns A:Z only.

## Testing

Tests in this library are mainly integration, meaning they require real Google Sheets API access.
To run them, you must download and store JSON credentials for you service account to `tests/google_client.json` file.

The tests then use [this table](https://docs.google.com/spreadsheets/d/1cEgUJA35YE56jn3JQRrJMfXKK9rkw0qaWEiYWnADLa8/edit) to read example data.

```sh
$ composer test
```

## Changelog
For latest changes see [CHANGELOG.md](CHANGELOG.md) file. This project follows [Semantic Versioning](https://semver.org/).
