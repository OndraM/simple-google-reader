<?php declare(strict_types=1);

namespace OndraM\SimpleGoogleReader\Spreadsheets;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cocur\Slugify\Slugify;
use Google\Client;
use Google\Service\Sheets;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SpreadsheetsReaderTest extends TestCase
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
        $reader = new SpreadsheetsReader($this->googleClient, $this->slugify, $this->cache);

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
                4 => [ // the number is 4, because rows 2 and 3 in the source table are empty and thus not included in the result
                       'column_a' => '3 after two empty rows',
                       'column_b' => 'B 4 data',
                       'treti_sloupec_with_special_chars_in_its_name' => 'C 4 data',
                ],
                5 => [
                       'column_a' => '4 with empty cols',
                       'column_b' => null,
                       'treti_sloupec_with_special_chars_in_its_name' => null,
                ],
            ],
            $data,
        );
    }

    #[Test]
    public function shouldReadSpecifiedSheet(): void
    {
        $reader = new SpreadsheetsReader($this->googleClient, $this->slugify, $this->cache);
        $data = $reader->readById(self::SPREADSHEET_ID, 'Second sheet');

        $this->assertSame(
            [
                0 => ['second_sheet_header' => 'Foo'],
                1 => ['second_sheet_header' => 'Bar'],
            ],
            $data
        );
    }

    #[Test]
    public function shouldReadEmptyTable(): void
    {
        $reader = new SpreadsheetsReader($this->googleClient, $this->slugify, $this->cache);
        $data = $reader->readById(self::SPREADSHEET_ID, 'Empty table');

        $this->assertSame([], $data);
    }
}
