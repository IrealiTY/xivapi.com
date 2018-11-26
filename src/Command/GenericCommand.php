<?php

namespace App\Command;

use App\Entity\MapPosition;
use App\Entity\User;
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
    
    public function __construct(?string $name = null, EntityManagerInterface $em, Cache $cache)
    {
        $this->em     = $em;
        $this->cache  = $cache;
        parent::__construct($name);
    }
    
    protected function configure()
    {
        $this
            ->setName('GenericCommand')
            ->setDescription('Generic command.')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('getting users');


        $users = $this->em->getRepository(User::class)->findAll();

        /** @var User $user */
        foreach ($users as $user) {
            $id = $user->getToken()->id;

            if (empty($id)) {
                print_r($user);
                die('no id for user');
            }

            $user->setSsoId($id);

            $output->writeln('.');
            $this->em->persist($user);
        }

        $this->em->flush();
    }
}
