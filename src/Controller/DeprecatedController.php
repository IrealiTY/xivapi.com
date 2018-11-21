<?php

namespace App\Controller;

use App\Entity\MapPosition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Routes in here will eventually be deleted
 */
class DeprecatedController extends Controller
{
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em;
    }
    
    /**
     * @Route("/mapdata/{name}/{id}")
     */
    public function deprecatedMapData($name, $id)
    {
        $name = strtolower($name);

        $nameToField = [
            'map'       => 'MapID',
            'placename' => 'PlaceNameID',
            'territory' => 'MapTerritoryID',
        ];

        $field = $nameToField[$name] ?? false;
        if (!$field) {
            throw new \Exception('There is no map data for the content: '. $name);
        }

        $repo = $this->em->getRepository(MapPosition::class);
        $pos  = [];

        /** @var MapPosition $position */
        foreach ($repo->findBy([ $field => $id ], [ 'Added' => 'ASC' ]) as $position) {
            $pos[] = $position->toArray();
        }

        return $this->json($pos);
    }
}
