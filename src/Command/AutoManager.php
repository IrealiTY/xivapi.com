<?php

namespace App\Command;

use App\Service\Redis\Cache;
use App\Service\Content\LodestoneData;
use App\Service\LodestoneAutoManagers\{
    AutoCharacterManager,
    AutoFreeCompanyManager,
    AutoLinkshellManager,
    AutoPvpTeamManager
};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * |
 * |    * * * * * /usr/bin/php /home/dalamud/dalamud/bin/console AutoManager
 * |
 * | DEV:
 * |    php bin/console AutoManager
 * |
 */
class AutoManager extends Command
{
    /** @var Cache */
    private $cache;
    /** @var AutoCharacterManager */
    private $autoCharacterManager;
    /** @var AutoFreeCompanyManager */
    private $autoFreeCompanyManager;
    /** @var AutoLinkshellManager */
    private $autoLinkshellManager;
    /** @var AutoPvpTeamManager */
    private $autoPvpTeamManager;
    
    public function __construct(
        ?string $name = null,
        Cache $cache,
        
        AutoCharacterManager $autoCharacterManager,
        AutoFreeCompanyManager $autoFreeCompanyManager,
        AutoLinkshellManager $autoLinkshellManager,
        AutoPvpTeamManager $autoPvpTeamManager
    ) {
        parent::__construct($name);
        $this->cache = $cache;
        
        $this->autoCharacterManager   = $autoCharacterManager;
        $this->autoFreeCompanyManager = $autoFreeCompanyManager;
        $this->autoLinkshellManager   = $autoLinkshellManager;
        $this->autoPvpTeamManager     = $autoPvpTeamManager;
    }
    
    protected function configure()
    {
        $this->setName('AutoManager')
            ->setDescription("Auto manage lodestone population queues.");
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(__CLASS__ .' - '. __METHOD__);
        
        LodestoneData::initContentCache($this->cache);
    
        $io->section('Auto Character Manager');
        $this->autoCharacterManager
             ->setSymfonyStyle($io)
             //->handleAddedCharacters()
             //->handleUpdatedCharacters()
             ->handleUpdatedAchievements()
             ->handleUpdatedFriends();
    
        $io->section('Auto Free Company Manager');
        $this->autoFreeCompanyManager
             ->setSymfonyStyle($io)
             ->handleAddedFreeCompany()
             ->handleUpdatedFreeCompany();
    
        $io->section('Auto Linkshell Manager');
        $this->autoLinkshellManager
             ->setSymfonyStyle($io)
             ->handleAddedLinkshell()
             ->handleUpdatedLinkshell();
    
        $io->section('Auto PvpTeam Manager');
        $this->autoPvpTeamManager
             ->setSymfonyStyle($io)
             ->handleAddedPvpTeam()
             ->handleUpdatedPvpTeam();
    }
}
