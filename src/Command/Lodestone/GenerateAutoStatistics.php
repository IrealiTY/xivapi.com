<?php

namespace App\Command\Lodestone;

use App\Entity\LodestoneStatistic;
use App\Repository\LodestoneStatisticRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateAutoStatistics extends Command
{
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }
    
    protected function configure()
    {
        $this->setName('GenerateAutoStatistics');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var LodestoneStatisticRepository $repo */
        $repo = $this->em->getRepository(LodestoneStatistic::class)->findAll();

        // delete old rows
        $repo->removeExpiredRows();

        $time1minute = time() - 60;
        $time1hour   = time() - 3600;

        // build stats on remaining rows
        /** @var LodestoneStatistic $ls */
        $stats = (Object)[
            'average_duration'      => null,
            'average_duration_data' => [],
            'method_stats'          => [],
            'queue_stats'           => [],
            'counts' => (Object)[
                'min' => 0,
                'hr'  => 0,
                'day' => 0,
            ]
        ];

        foreach ($repo->findAll() as $ls) {
            $stats->counts->day++;

            if ($ls->getAdded() >= $time1minute) {
                $stats->counts->min++;
            }

            if ($ls->getAdded() >= $time1hour) {
                $stats->counts->hr++;
            }

            //
            // Avg Duration
            //
            $stats->average_duration_data[] = $ls->getDuration();

            //
            // Counts
            //
            if (!isset($stats->method_stats[$ls->getMethod()])) {
                $stats->method_stats[$ls->getMethod()] = 0;
            }

            if (!isset($stats->queue_stats[$ls->getQueue()])) {
                $stats->queue_stats[$ls->getQueue()] = 0;
            }

            $stats->method_stats[$ls->getMethod()] += 1;
            $stats->queue_stats[$ls->getQueue()] += 1;
        }

        $stats->average_duration = array_sum($stats->average_duration_data) / count($stats->average_duration_data);
        $stats->average_duration_data = null;
    }
}