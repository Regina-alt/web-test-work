<?php

namespace App\Controller;

use App\Entity\Url;
use App\Repository\UrlRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UrlController extends AbstractController
{
    /**
     * @Route("/encode-url", name="encode_url")
     */
    public function encodeUrl(Request $request): JsonResponse
    {
        $inputUrl = $request->get('url');

        if (empty($inputUrl)) {
            return $this->json(['error' => 'URL is required.'], 400);
        }

        $urlRepository = $this->getDoctrine()->getRepository(Url::class);
        $existingUrl = $urlRepository->findOneBy(['url' => $inputUrl]);

        if ($existingUrl) {
            return $this->json(['hash' => $existingUrl->getHash()]);
        }

        $url = new Url();
        $url->setUrl($inputUrl);

        // Устанавливаем уникальный хэш и время истечения
        $hash = uniqid();
        $url->setHash($hash);
        $url->setExpiresAt(new \DateTime('+24 hours')); // Установите срок действия на 24 часа

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($url);
        $entityManager->flush();

        return $this->json(['hash' => $hash]);
    }

    /**
     * @Route("/decode-url", name="decode_url")
     */
    public function decodeUrl(Request $request): JsonResponse
    {
        $hash = $request->get('hash');
        if (empty($hash)) {
            return $this->json(['error' => 'Hash is required.'], 400);
        }

        /** @var UrlRepository $urlRepository */
        $urlRepository = $this->getDoctrine()->getRepository(Url::class);
        $url = $urlRepository->findOneByHash($hash);

        if (empty($url)) {
            return $this->json(['error' => 'Non-existent hash.'], 404);
        }

        $expiresAt = $url->getExpiresAt();
        if ($expiresAt === null) {
            return $this->json(['error' => 'Expiration time not set.'], 500); // Или обработайте по-другому
        }

        if ($expiresAt < new \DateTime()) {
            // Логирование или вывод отладочной информации
            $currentDate = new \DateTime();

            return $this->json([
                'error' => 'This URL has expired.',
                'current_date' => $currentDate->format('Y-m-d H:i:s'),
                'expires_at' => $expiresAt->format('Y-m-d H:i:s')
            ], 410);
        }

        return $this->json(['url' => $url->getUrl()]);
    }

    /**
     * @Route("/gourl", name="gourl")
     */
    public function redirectToUrl(Request $request, UrlRepository $urlRepository): Response
    {
        $hash = $request->query->get('hash');

        if (empty($hash)) {
            throw new BadRequestHttpException('Hash parameter is required.');
        }

        $url = $urlRepository->findOneBy(['hash' => $hash]);

        if (!$url) {
            throw new NotFoundHttpException('URL not found.');
        }

        return $this->redirect($url->getUrl());
    }

    /**
     * @Route("/api/urls", name="api_add_url", methods={"POST"})
     */
    public function addUrl(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['url']) || !isset($data['created_date'])) {
            return new JsonResponse(['error' => 'Invalid input. URL and created date are required.'], 400);
        }

        $createdDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['created_date']);
        if ($createdDate === false) {
            return new JsonResponse(['error' => 'Invalid created date format. Expected format: YYYY-MM-DD HH:MM:SS.'], 400);
        }

        $urlEntity = new Url();
        $urlEntity->setUrl($data['url']);
        $urlEntity->setCreatedDate($createdDate);
        $urlEntity->setExpiresAt(new \DateTime('+24 hours')); // Установите срок действия на 24 часа

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($urlEntity);
        $entityManager->flush();

        return new JsonResponse(['message' => 'URL added successfully'], 201);
    }

    /**
     * @Route("/api/urls/stats", name="api_get_stats", methods={"GET"})
     */
    public function getStats(Request $request, UrlRepository $urlRepository): JsonResponse
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        if (!$startDate || !$endDate) {
            return new JsonResponse(['error' => 'Start date and end date are required.'], 400);
        }

        try {
            $startDate = new \DateTimeImmutable($startDate);
            $endDate = new \DateTimeImmutable($endDate);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid date format.'], 400);
        }

        $uniqueUrlsCount = $urlRepository->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.url)')
            ->where('u.createdDate BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        $domain = $request->query->get('domain');
        if ($domain) {
            $domainUrlsCount = $urlRepository->createQueryBuilder('u')
                ->select('COUNT(DISTINCT u.url)')
                ->where('u.url LIKE :domain')
                ->setParameter('domain', '%' . $domain . '%')
                ->getQuery()
                ->getSingleScalarResult();
        } else {
            $domainUrlsCount = 0;
        }

        return new JsonResponse([
            'unique_urls_count' => $uniqueUrlsCount,
            'unique_urls_with_domain_count' => $domainUrlsCount,
        ]);
    }
}
