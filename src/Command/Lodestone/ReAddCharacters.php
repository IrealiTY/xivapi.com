<?php

namespace App\Command\Lodestone;

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
            $output->writeln('Add: '. $id);
            $url = "https://staging.xivapi.com/character/{$id}/add?key=f0ef8bd32a004f1daf0d53b1";
            file_get_contents($url);
        }
    }
}
