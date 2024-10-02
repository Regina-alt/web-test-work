<?php

namespace App\Tests\Command;

use App\Command\SendUrlsCommand;
use App\Entity\Url;
use App\Repository\UrlRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SendUrlsCommandTest extends KernelTestCase
{
    public function testExecuteNoUnsentUrls(): void
    {
        $urlRepository = $this->createMock(UrlRepository::class);
        $urlRepository->method('findUnsentUrls')->willReturn([]);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $command = new SendUrlsCommand($urlRepository, $entityManager, 'http://example.com');
        $commandTester = new CommandTester($command);

        $commandTester->execute();

        $this->assertStringContainsString('No unsent URLs to process.', $commandTester->getDisplay());
    }

    public function testExecuteWithUnsentUrls(): void
    {
        $url = new Url();
        $url->setUrl('https://example.com');
        $url->setSent(false);

        $urlRepository = $this->createMock(UrlRepository::class);
        $urlRepository->method('findUnsentUrls')->willReturn([$url]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturn(null);
        $entityManager->method('flush')->willReturn(null);

        $command = new SendUrlsCommand($urlRepository, $entityManager, 'http://example.com');
        $commandTester = new CommandTester($command);

        $commandTester->execute();

        $this->assertStringContainsString('All unsent URLs have been processed.', $commandTester->getDisplay());
    }
}
