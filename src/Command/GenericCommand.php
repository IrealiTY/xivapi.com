<?php

namespace App\Command;

use App\Entity\MapPosition;
use App\Service\Redis\Cache;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenericCommand extends Command
{
    use CommandHelperTrait;
    
    /** @var EntityManagerInterface */
    private $em;
    /** @var Cache */
    private $cache;
    
    public function __construct(
        ?string $name = null,
        EntityManagerInterface $em,
        Cache $cache
    ) {
        $this->em     = $em;
        $this->cache  = $cache;
        parent::__construct($name);
    }
    
    protected function configure()
    {
        $this
            ->setName('GenericCommand')
            ->setDescription('Generic command.')
            ->addArgument('a', InputArgument::OPTIONAL, 'A')
            ->addArgument('b', InputArgument::OPTIONAL, 'B')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->io->title("Generic");

        $data = explode("\n", file_get_contents(__DIR__.'/../../data/csv/MapData.csv'));
        unset($data[0]);

        $count = 0;
        foreach ($data as $i => $row) {
            $row = str_getcsv($row);

            $map = new MapPosition();
            $fields = [
                'Hash',
                'ContentIndex',
                'ENpcResidentID',
                'BNpcNameID',
                'BNpcBaseID',
                'Name',
                'Type',
                'MapID',
                'MapIndex',
                'MapTerritoryID',
                'PlaceNameID',
                'CoordinateX',
                'CoordinateY',
                'CoordinateZ',
                'PosX',
                'PosY',
                'PixelX',
                'PixelY'
            ];

            foreach ($fields as $j => $field) {
                $value = $row[$j];

                if (empty($value)) {
                    break;
                }

                $method = "set{$field}";
                $map->{$method}($value);
            }

            if (empty($map->getHash())) {
                continue;
            }

            $count++;
            $this->em->persist($map);

            if ($count > 250) {
                $this->io->text('Saving 250 map positions');
                $this->em->flush();
                $count = 0;
            }
        }

    }
}
