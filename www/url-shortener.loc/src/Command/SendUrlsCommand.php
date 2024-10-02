<?php

namespace App\Command;

use App\Repository\UrlRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-urls',
    description: 'Send unsent URLs to a specified endpoint.',
)]
class SendUrlsCommand extends Command
{
    private UrlRepository $urlRepository;
    private EntityManagerInterface $entityManager;
    private string $endpoint;

    public function __construct(UrlRepository $urlRepository, EntityManagerInterface $entityManager, string $endpoint)
    {
        parent::__construct();
        $this->urlRepository = $urlRepository;
        $this->entityManager = $entityManager;
        $this->endpoint = $endpoint;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $unsentUrls = $this->urlRepository->findUnsentUrls();

        if (empty($unsentUrls)) {
            $io->success('No unsent URLs to process.');
            return Command::SUCCESS;
        }

        foreach ($unsentUrls as $url) {
            $data = [
                'url' => $url->getUrl(),
                'created_date' => $url->getCreatedDate()->format('Y-m-d H:i:s'),
            ];

            $response = $this->sendDataToEndpoint($this->endpoint, $data);

            if ($response) {
                $url->setSent(true);
                $this->entityManager->persist($url);
            } else {
                $io->error(sprintf('Failed to send URL: %s', $url->getUrl()));
            }
        }

        $this->entityManager->flush();
        $io->success('All unsent URLs have been processed.');

        return Command::SUCCESS;
    }

    private function sendDataToEndpoint(string $endpoint, array $data): bool
    {
        try {
            $options = [
                'http' => [
                    'header'  => "Content-type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => json_encode($data),
                    'timeout' => 10,
                ],
            ];
            $context  = stream_context_create($options);
            $result = file_get_contents($endpoint, false, $context);

            if ($result === false) {
                throw new \Exception('No response from endpoint.');
            }

            $httpResponseCode = $http_response_header[0] ?? '';
            if (strpos($httpResponseCode, '200') === false) {
                throw new \Exception('Unexpected HTTP response: ' . $httpResponseCode);
            }

            return true;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}
