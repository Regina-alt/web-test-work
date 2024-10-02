<?php

namespace App\Repository;

use App\Entity\Url;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\NoResultException;

class UrlRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Url::class);
    }

    public function findOneByHash(string $value): ?Url
    {
        try {
            return $this->createQueryBuilder('u')
                ->andWhere('u.hash = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NoResultException $e) {
            error_log('No result found for hash: ' . $value);
            return null;
        } catch (ORMException $e) {
            error_log('ORM exception while finding URL by hash: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            error_log('General exception while finding URL by hash: ' . $e->getMessage());
            return null;
        }
    }

    public function findUnsentUrls(): array
    {
        try {
            return $this->createQueryBuilder('u')
                ->where('u.sent = :sent')
                ->setParameter('sent', false)
                ->getQuery()
                ->getResult();
        } catch (ORMException $e) {
            error_log('ORM exception while finding unsent URLs: ' . $e->getMessage());
            return [];
        } catch (\Exception $e) {
            error_log('General exception while finding unsent URLs: ' . $e->getMessage());
            return [];
        }
    }
}
