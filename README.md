# Simple Google Spreadsheets and Docs Reader

[![Latest Stable Version](https://img.shields.io/packagist/v/ondram/simple-google-reader.svg?style=flat-square)](https://packagist.org/packages/ondram/simple-google-reader)
[![Coverage Status](https://img.shields.io/coveralls/OndraM/simple-google-reader/main.svg?style=flat-square)](https://coveralls.io/r/OndraM/simple-google-reader)
[![GitHub Actions Build Status](https://img.shields.io/github/actions/workflow/status/OndraM/simple-google-reader/tests.yaml?style=flat-square&label=GitHub%20Actions%20build)](https://github.com/OndraM/simple-google-reader/actions)

PHP library providing a simple way to load data from Google Spreadsheets and Google Docs.

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
1. Enable required APIs in [Google Cloud Console](https://console.cloud.google.com/apis/dashboard) for your project:
   - [Google Sheets API](https://console.cloud.google.com/apis/library/sheets.googleapis.com) if you plan reading Spreadsheets,
   - [Google Docs API](https://console.cloud.google.com/apis/library/docs.googleapis.com) to read Docs,
   - [Google Drive API](https://console.cloud.google.com/apis/library/drive.googleapis.com) if you need to read Docs as HTML,
1. Share the intended document with your service account, copy document ID (from the URL)
1. Make sure to install any package [implementing PSR-6 caching](https://packagist.org/providers/psr/simple-cache-implementation)
1. Prepare cache and initialize Google Client:

```php
<?php declare(strict_types=1);

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Cocur\Slugify\Slugify;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use OndraM\SimpleGoogleReader\Spreadsheets\SpreadsheetsReader;

require_once __DIR__ . '/vendor/autoload.php';

// Create instance of Cache, in this case we use FilesystemCache
$cache = new FilesystemCachePool(new Filesystem(new Local(__DIR__ . '/data')));

// Instantiate the Google Client with your credentials
$client = new \Google\Client();
$client->setAuthConfig(__DIR__ . '/data/google_client.json');
// If you service account has domain-wide access, you need to use setSubject to set the name of the user
// which will the service account impersonate. This user must have right to access the spreadsheet.
$client->setSubject('foo@bar.cz');

// see below for spreadsheets and docs usage
```

### Reading spreadsheets

In Google Cloud Console, do not forget to enable [Google Sheets API](https://console.cloud.google.com/apis/library/sheets.googleapis.com).

```php
// $client is the instance from above example

$client->addScope(\Google\Service\Sheets::SPREADSHEETS);

// Create instance of Slugify, needed for spreadsheets
$slugify = new Slugify(['rulesets' => ['default', 'czech']]);

$reader = new SpreadsheetsReader($client, $slugify, $cache);

$rows = $reader->readById('pasteHereGoogleSpreadsheedId', '[optional sheet name]'/*, optional cache TTL*/);
foreach ($rows as $row) {
    echo $row['first_column'];
    echo $row['second_column'];
}
```

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

### Reading documents

#### As plaintext

In Google Cloud Console, do not forget to enable [Google Docs API](https://console.cloud.google.com/apis/library/docs.googleapis.com).

```php
// $client is the instance from above example

$client->addScope(\Google\Service\Docs::DOCUMENTS_READONLY);

$docsReader = new DocsReader($client, $cache);

$document = $docsReader->readAsPlaintext('pasteHereGoogleDocsId'/*, optional cache TTL*/);
```

This will read the whole document as plain text. Only text elements are included, other elements like tables are
ignored. Also, any document formatting is ignored.

#### As HTML

To read document as HTML, do not forget to enable [Google Drive API](https://console.cloud.google.com/apis/library/drive.googleapis.com) in Google Cloud console.

```php
$client->addScope(\Google\Service\Docs::DRIVE_READONLY);

$html = $docsReader->readAsHtml('pasteHereGoogleDocsId'/*, optional cache TTL*/);
```

Note the output will be quite bloated-HTML, with many inline styles and nested elements. It may be useful to apply
on the output some HTML sanitizer like [symfony/html-sanitizer](https://symfony.com/doc/current/html_sanitizer.html)
to remove unwanted elements and attributes.

## Testing

Tests in this library are mainly integration, meaning they require real Google API access.
To run them, you must download and store JSON credentials for you service account to `tests/google_client.json` file.

The tests then use [this table](https://docs.google.com/spreadsheets/d/1cEgUJA35YE56jn3JQRrJMfXKK9rkw0qaWEiYWnADLa8/edit)
and [this document](https://docs.google.com/document/d/1T46U8sJEimVDhtmixxKLtf7Oxl1FzM2ae2EDYQ-HT_4/edit)
to read example data.

```sh
$ composer test
```

## Changelog
For latest changes see [CHANGELOG.md](CHANGELOG.md) file. This project follows [Semantic Versioning](https://semver.org/).
