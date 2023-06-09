<?php

namespace OndraM\SimpleGoogleReader\Spreadsheets;

use Cocur\Slugify\Slugify;
use Google\Client;
use Google\Service\Sheets;
use Psr\SimpleCache\CacheInterface;

class Reader
{
    private const DEFAULT_TTL = 3600;
    /** @var Client */
    protected $googleClient;
    /** @var Slugify */
    private $slugify;
    /** @var CacheInterface */
    private $cache;

    public function __construct(Client $googleClient, Slugify $slugify, CacheInterface $cache)
    {
        $this->googleClient = $googleClient;
        $this->slugify = $slugify;
        $this->cache = $cache;
    }

    public function readById(string $spreadsheetId, string $sheetName = null): array
    {
        $cacheKey = $this->generateCacheKey($spreadsheetId, $sheetName);
        if ($this->cache->has($cacheKey)) {
            return (array) $this->cache->get($cacheKey, []);
        }

        $sheetService = $this->createService();
        $range = 'A:Z';
        if ($sheetName !== null) {
            $range = $sheetName . '!' . $range;
        }

        $response = $sheetService->spreadsheets_values->get($spreadsheetId, $range);
        $rows = $response->getValues();
        if (count($rows) === 0) {
            return [];
        }

        $header = array_map(
            function ($value) {
                return $this->slugify->slugify($value, ['separator' => '_']);
            },
            array_shift($rows)
        );

        $data = array_map(
            function ($value) use ($header) {
                $mappedData = [];
                for ($i = 0, $iCount = count($header); $i < $iCount; $i++) {
                    $mappedData[$header[$i]] = $value[$i] ?? null;
                }

                return $mappedData;
            },
            $rows
        );

        $this->cache->set($cacheKey, $data, self::DEFAULT_TTL);

        return $data;
    }

    public function generateCacheKey(string $spreadsheetId, string $sheetName = null): string
    {
        return 'spreadsheet_'
            . sha1(
                $spreadsheetId
                . ($sheetName !== null ? '_' . $sheetName : '')
            );
    }

    private function createService(): Sheets
    {
        return new Sheets($this->googleClient);
    }
}
