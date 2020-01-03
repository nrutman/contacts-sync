<?php

namespace App\Repository;

use App\Entity\AuthenticationToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method AuthenticationToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthenticationToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthenticationToken[]    findAll()
 * @method AuthenticationToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthenticationTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthenticationToken::class);
    }
}
