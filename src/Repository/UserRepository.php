<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findTeamUser(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :roleAdmin')
            ->orWhere('u.roles LIKE :roleSuperAdmin')
            ->setParameter('roleAdmin', '%ROLE_ADMIN%')
            ->setParameter('roleSuperAdmin', '%ROLE_SUPER_ADMIN%')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return QueryBuilder
     */
    public function findUser(): QueryBuilder
    {
        // 🏆 Correction : On sélectionne l'entité complète 'u' et ses champs
        // pour permettre à la fois le tri et l'accès à toutes les propriétés
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :roleUser')
            ->orWhere('u.roles LIKE :roleAnonymized')
            ->setParameter('roleUser', '%ROLE_USER%')
            ->setParameter('roleAnonymized', '%ROLE_ANONYMIZED%');
    }
    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
