<?php declare(strict_types=1);

namespace OndraM\SimpleGoogleReader\Docs;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Google\Client;
use Google\Service\Docs;
use Google\Service\Sheets;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class DocsReaderTest extends TestCase
{
    // @see https://docs.google.com/document/d/1T46U8sJEimVDhtmixxKLtf7Oxl1FzM2ae2EDYQ-HT_4/edit
    private const DOCUMENT_ID = '1T46U8sJEimVDhtmixxKLtf7Oxl1FzM2ae2EDYQ-HT_4';

    private ArrayCachePool $cache;
    private Client $googleClient;

    protected function setUp(): void
    {
        $this->cache = new ArrayCachePool();
        $this->googleClient = new Client();
        $this->googleClient->setAuthConfig(__DIR__ . '/../google_client.json');
        $this->googleClient->addScope(Docs::DOCUMENTS_READONLY);
        $this->googleClient->addScope(Docs::DRIVE_READONLY);

        parent::setUp();
    }

    #[Test]
    public function shouldReadDocumentAsPlaintext(): void
    {
        $reader = new DocsReader($this->googleClient, $this->cache);

        $data = $reader->readAsPlaintext(self::DOCUMENT_ID);

        $this->assertSame(<<<HTXT
            Header 1
            Lorem ipsum dolor sit amet, consectetuer adipiscing elit. 
            Header 2
            List item 1
            List item 2

            HTXT, $data);
    }

    #[Test]
    public function shouldReadDocumentAsHtml(): void
    {
        $reader = new DocsReader($this->googleClient, $this->cache);

        $html = $reader->readAsHtml(self::DOCUMENT_ID);

        $crawler = new Crawler($html);
        $h1 = $crawler->filter('h1');
        $this->assertCount(1, $h1);
        $this->assertEquals('Header 1', $h1->text());

        $li = $crawler->filter('li');
        $this->assertCount(2, $li);
        $this->assertEquals('List item 1', $li->eq(0)->text());
        $this->assertEquals('List item 2', $li->eq(1)->text());
    }
}
