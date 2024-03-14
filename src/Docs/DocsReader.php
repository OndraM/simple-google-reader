<?php

namespace OndraM\SimpleGoogleReader\Docs;

use Google\Client;
use Google\Service\Docs;
use Google\Service\Drive;
use Psr\SimpleCache\CacheInterface;

class DocsReader
{
    private const DEFAULT_TTL = 3600;

    public function __construct(private readonly Client $googleClient, private readonly CacheInterface $cache)
    {
    }

    /**
     * Read contents of the document as plaintext. Only text elements and read, other elements like tables are ignored.
     * All document formatting is ignored.
     *
     * You must enable Google Docs API in your Google Cloud Console project:
     * https://console.cloud.google.com/apis/library/docs.googleapis.com
     */
    public function readAsPlaintext(string $documentId, int $ttl = self::DEFAULT_TTL): string
    {
        $cacheKey = $this->generateCacheKey($documentId, 'text');
        if ($this->cache->has($cacheKey)) {
            $cacheData = $this->cache->get($cacheKey, '');

            return is_string($cacheData) ? $cacheData : '';
        }

        $documentService = $this->createDocsService();

        $doc = $documentService->documents->get($documentId);
        $bodyContent = $doc->getBody()->getContent();

        $bodyText = '';
        foreach ($bodyContent as $structuralElement) {
            if ($structuralElement->getParagraph() === null) {
                continue;
            }

            foreach ($structuralElement->getParagraph()->getElements() as $element) {
                if ($element->getTextRun()) {
                    $bodyText .= $element->getTextRun()->getContent();
                }
            }
        }

        $this->cache->set($cacheKey, $bodyText, $ttl);

        return $bodyText;
    }

    /**
     * Read contents of the document as HTML.
     *
     * To be able to read the doc as HTML, you must enable Google Drive API in your Google Cloud Console project:
     * https://console.cloud.google.com/apis/library/drive.googleapis.com
     */
    public function readAsHtml(string $documentId, int $ttl = self::DEFAULT_TTL): string
    {
        $cacheKey = $this->generateCacheKey($documentId, 'html');
        if ($this->cache->has($cacheKey)) {
            $cacheData = $this->cache->get($cacheKey, '');

            return is_string($cacheData) ? $cacheData : '';
        }

        $driveService = $this->createDriveService();

        $response = $driveService->files->export($documentId, 'text/html', [
            'alt' => 'media',
        ]);
        $htmlContent = $response->getBody()->getContents();

        $this->cache->set($cacheKey, $htmlContent, $ttl);

        return $htmlContent;
    }

    private function generateCacheKey(string $documentId, string $type): string
    {
        return 'doc_' . sha1($documentId) . '_' . $type;
    }

    private function createDocsService(): Docs
    {
        return new Docs($this->googleClient);
    }

    private function createDriveService(): Drive
    {
        return new Drive($this->googleClient);
    }
}
