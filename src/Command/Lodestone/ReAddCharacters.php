<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReAddCharacters extends Command
{
    protected function configure()
    {
        $this->setName('ReAddCharacters');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(__METHOD__);
        
        $characters = file_get_contents(__DIR__.'/lodestone_character.csv');
        $characters = explode("\n", $characters);
        $characters = array_filter($characters);
        
        foreach ($characters as $id) {
            print_r($id);
            die;
        }
    }
}
