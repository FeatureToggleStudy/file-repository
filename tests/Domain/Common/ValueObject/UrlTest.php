<?php declare(strict_types=1);

namespace Tests\Domain\Common\ValueObject;

use App\Domain\Common\ValueObject\BaseUrl;
use App\Domain\Common\ValueObject\Url;
use PHPUnit\Framework\TestCase;

/**
 * @see Url
 */
class UrlTest extends TestCase
{
    /**
     * @see Url::isLocalFileUrl()
     */
    public function testIsLocalFileUrl(): void
    {
        $url = new Url('/api/riot/make', new BaseUrl(true, 'riotkit.org'));
        $localFileUrl = new Url('file:///tmp', null);

        $this->assertTrue($localFileUrl->isLocalFileUrl());
        $this->assertFalse($url->isLocalFileUrl());
    }

    /**
     * @see Url::withQueryParam()
     */
    public function testWithQueryParam(): void
    {
        $url = new Url('/api/riot/make', new BaseUrl(true, 'riotkit.org'));
        $urlWithParam = $url->withQueryParam('place', 'town-square');

        $this->assertNotSame($url, $urlWithParam);
        $this->assertSame('https://riotkit.org/api/riot/make?place=town-square', $urlWithParam->getValue());
    }

    /**
     * @see Url::withVar()
     */
    public function testWithVar(): void
    {
        $template = new Url('/content/{{ postId }}', new BaseUrl(true, 'iwa-ait.org'));
        $url = $template->withVar('postId', 'postal-workers-take-action-management-goes-after-zsp');

        $this->assertSame(
            'https://iwa-ait.org/content/postal-workers-take-action-management-goes-after-zsp',
            $url->getValue()
        );
    }
}
