<?php

namespace App\Tests\Repository;

use App\Entity\Url;
use App\Repository\UrlRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UrlRepositoryTest extends KernelTestCase
{
    private UrlRepository $urlRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::$container->get('doctrine')->getManager();
        $this->urlRepository = $this->entityManager->getRepository(Url::class);
    }

    public function testFindOneByHashFound(): void
    {
        $url = new Url();
        $url->setUrl('https://example.com');
        $url->setHash('existing_hash');
        $this->entityManager->persist($url);
        $this->entityManager->flush();

        $foundUrl = $this->urlRepository->findOneByHash('existing_hash');
        $this->assertInstanceOf(Url::class, $foundUrl);
        $this->assertSame('https://example.com', $foundUrl->getUrl());
    }

    public function testFindOneByHashNotFound(): void
    {
        $this->expectException(NoResultException::class);
        $this->urlRepository->findOneByHash('non_existing_hash');
    }
}
