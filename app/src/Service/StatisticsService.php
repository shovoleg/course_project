<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\CvRepository;
use App\Repository\PositionRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class StatisticsService
{
    private const NAMES = [
        '.NET',
        'Android',
        'Bootstrap',
        'C++',
        'CSS',
        'Docker',
        'Git',
        'HTML',
        'iOS',
        'Java',
        'JavaScript',
        'Linux',
        'Microsoft',
        'MySQL',
        'PHP',
        'Python',
        'REST',
        'SQL',
        'Symfony',
        'TypeScript',
    ];

    public function __construct(
        private CvRepository $cvs,
        private PositionRepository $positions,
        private UserRepository $users,
        private TagRepository $tags,
        private TagFactory $tagFactory,
        private EntityManagerInterface $em,
    ) {
    }

    public function home(?User $viewer, AccessEvaluator $access, array $values): array
    {
        $this->tagFactory->fromNames(self::NAMES);
        $this->em->flush();
        $latest = [];
        foreach ($this->positions->findRecent(30) as $position) {
            if ($access->canBrowse($position, $viewer, $values)) {
                $latest[] = $position;
            }
            if (count($latest) === 10) {
                break;
            }
        }

        return [
            'cvs24h' => $this->cvs->countSince(new \DateTimeImmutable('-24 hours')),
            'positions' => (int) $this->em->createQuery('SELECT COUNT(p.id) FROM App\Entity\Position p')->getSingleScalarResult(),
            'candidates' => $this->users->countByRole('ROLE_CANDIDATE'),
            'recruiters' => $this->users->countByRole('ROLE_RECRUITER'),
            'cvs' => $this->cvs->countAll(),
            'latest' => $latest,
            'popular' => $this->positions->popular(5),
            'tags' => $this->tags->cloud(100),
        ];
    }
}
