<?php

namespace OndraM\SimpleGoogleReader\Spreadsheets;

use Cocur\Slugify\Slugify;
use Google\Client;
use Google\Service\Sheets;
use Psr\SimpleCache\CacheInterface;

class SpreadsheetsReader
{
    private const DEFAULT_TTL = 3600;

    public function __construct(protected Client $googleClient, private Slugify $slugify, private CacheInterface $cache)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function readById(string $spreadsheetId, string $sheetName = null, int $ttl = self::DEFAULT_TTL): array
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
        if (!is_array($rows) || count($rows) === 0) {
            return [];
        }

        $header = array_map(
            fn ($value) => $this->slugify->slugify($value, ['separator' => '_']),
            array_shift($rows)
        );

        // Map rows to associative arrays based on header
        $data = array_map(
            function ($value) use ($header) {
                return array_combine($header, array_pad($value, count($header), null));
            },
            $rows
        );

        // Filter out rows where all values are null
        $data = array_filter(
            $data,
            fn($row) => count(array_filter($row, fn($value) => $value !== null)) > 0
        );

        $this->cache->set($cacheKey, $data, $ttl);

        return $data;
    }

    private function generateCacheKey(string $spreadsheetId, string $sheetName = null): string
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
