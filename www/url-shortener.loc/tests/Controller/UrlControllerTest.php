<?php

namespace App\Tests\Controller;

use App\Entity\Url;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UrlControllerTest extends WebTestCase
{
    public function testEncodeUrl(): void
    {
        $client = static::createClient();
        $client->request('POST', '/encode-url', ['url' => 'https://example.com']);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hash' => '']);
    }

    public function testDecodeUrlNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/decode-url', ['hash' => 'invalid_hash']);

        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains(['error' => 'Non-existent hash.']);
    }

    public function testRedirectToUrlNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/gourl?hash=invalid_hash');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testRedirectToValidUrl(): void
    {
        $url = new Url();
        $url->setUrl('https://example.com');
        $url->setHash('valid_hash');

        $entityManager = $this->getMockBuilder(EntityManagerInterface::class)->disableOriginalConstructor()->getMock();
        $entityManager->method('persist')->willReturn($url);

        $client = static::createClient();
        $client->request('GET', '/gourl?hash=valid_hash');

        $this->assertResponseRedirects('https://example.com');
    }
}
