<?php declare(strict_types=1);

namespace OndraM\SimpleGoogleReader\Spreadsheets;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cocur\Slugify\Slugify;
use Google\Client;
use Google\Service\Sheets;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    // @see https://docs.google.com/spreadsheets/d/1cEgUJA35YE56jn3JQRrJMfXKK9rkw0qaWEiYWnADLa8/edit
    private const SPREADSHEET_ID = '1cEgUJA35YE56jn3JQRrJMfXKK9rkw0qaWEiYWnADLa8';

    private Slugify $slugify;
    private ArrayCachePool $cache;
    private Client $googleClient;

    protected function setUp(): void
    {
        $this->slugify = new Slugify(['rulesets' => ['default', 'czech']]);
        $this->cache = new ArrayCachePool();
        $this->googleClient = new Client();
        $this->googleClient->setAuthConfig(__DIR__ . '/../google_client.json');
        $this->googleClient->addScope(Sheets::SPREADSHEETS);

        parent::setUp();
    }

    #[Test]
    public function shouldReadTable(): void
    {
        $reader = new Reader($this->googleClient, $this->slugify, $this->cache);

        $data = $reader->readById(self::SPREADSHEET_ID);

        $this->assertSame(
            [
                0 => [
                    'column_a' => '1',
                    'column_b' => 'B 2 data',
                    'treti_sloupec_with_special_chars_in_its_name' => 'C 2 data',
                ],
                1 => [
                    'column_a' => '2',
                    'column_b' => 'B 3 data',
                    'treti_sloupec_with_special_chars_in_its_name' => 'C 3 data',
                ],
            ],
            $data,
        );
    }

    #[Test]
    public function shouldReadSpecifiedSheet(): void
    {
        $reader = new Reader($this->googleClient, $this->slugify, $this->cache);
        $data = $reader->readById(self::SPREADSHEET_ID, 'Second sheet');

        $this->assertSame(
            [
                0 => ['second_sheet_header' => 'Foo'],
                1 => ['second_sheet_header' => 'Bar'],
            ],
            $data
        );
    }
}
