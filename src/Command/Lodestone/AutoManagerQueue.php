<?php

namespace App\Command\Lodestone;

use App\Entity\Character;
use App\Entity\CharacterAchievements;
use App\Entity\CharacterFriends;
use App\Entity\Entity;
use App\Entity\FreeCompany;
use App\Entity\Linkshell;
use App\Entity\PvPTeam;
use App\Repository\CharacterAchievementRepository;
use App\Repository\CharacterFriendsRepository;
use App\Repository\CharacterRepository;
use App\Repository\FreeCompanyRepository;
use App\Repository\LinkshellRepository;
use App\Repository\PvPTeamRepository;
use App\Service\LodestoneQueue\CharacterAchievementQueue;
use App\Service\LodestoneQueue\CharacterFriendQueue;
use App\Service\LodestoneQueue\CharacterQueue;
use App\Service\LodestoneQueue\FreeCompanyQueue;
use App\Service\LodestoneQueue\LinkshellQueue;
use App\Service\LodestoneQueue\PvPTeamQueue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This would run on a cronjob on XIVAPI
 * todo - uncomment queues are things become more stable
 */
class AutoManagerQueue extends Command
{
    /** @var SymfonyStyle */
    private $io;
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }
    
    protected function configure()
    {
        $this->setName('AutoManagerQueue');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->queueCharacters();
        $this->queueFriendLists();
        $this->queueAchievements();
        $this->queueFreeCompanies();
        $this->queueLinkshells();
        $this->queuePvpTeams();
    }

    //
    // Queues
    //

    private function queueCharacters()
    {
        /** @var CharacterRepository $repo */
        $repo = $this->em->getRepository(Character::class);

        // 6 queues for basic auto-updating
        $this->io->text(__METHOD__ .' character_update_0_normal');
        CharacterQueue::queue($repo->toUpdate(0, Entity::PRIORITY_NORMAL), 'character_update_0_normal');
        $this->io->text(__METHOD__ .' character_update_1_normal');
        CharacterQueue::queue($repo->toUpdate(1, Entity::PRIORITY_NORMAL), 'character_update_1_normal');
        $this->io->text(__METHOD__ .' character_update_2_normal');
        CharacterQueue::queue($repo->toUpdate(2, Entity::PRIORITY_NORMAL), 'character_update_2_normal');
        $this->io->text(__METHOD__ .' character_update_3_normal');
        CharacterQueue::queue($repo->toUpdate(3, Entity::PRIORITY_NORMAL), 'character_update_3_normal');
        // $this->io->text(__METHOD__ .' character_update_4_normal');
        // CharacterQueue::queue($repo->toUpdate(4, Entity::PRIORITY_NORMAL), 'character_update_4_normal');
        // $this->io->text(__METHOD__ .' character_update_5_normal');
        // CharacterQueue::queue($repo->toUpdate(5, Entity::PRIORITY_NORMAL), 'character_update_5_normal');

        // 2 queues for patrons
        $this->io->text(__METHOD__ .' character_update_0_patreon');
        CharacterQueue::queue($repo->toUpdate(0, Entity::PRIORITY_HIGH), 'character_update_0_patreon');
        $this->io->text(__METHOD__ .' character_update_1_patreon1');
        CharacterQueue::queue($repo->toUpdate(1, Entity::PRIORITY_HIGH), 'character_update_1_patreon');

        // 2 queues for inactive
        // $this->io->text(__METHOD__ .' character_update_0_low');
        // CharacterQueue::queue($repo->toUpdate(0, Entity::PRIORITY_LOW), 'character_update_0_low');
        // $this->io->text(__METHOD__ .' character_update_1_low');
        // CharacterQueue::queue($repo->toUpdate(1, Entity::PRIORITY_LOW), 'character_update_1_low');
    }

    private function queueFriendLists()
    {
        /** @var CharacterFriendsRepository $repo */
        $repo = $this->em->getRepository(CharacterFriends::class);

        // 2 queues for basic updates
        $this->io->text(__METHOD__ .' character_friends_update_0_normal');
        CharacterFriendQueue::queue($repo->toUpdate(0, Entity::PRIORITY_NORMAL), 'character_friends_update_0_normal');
        $this->io->text(__METHOD__ .' character_friends_update_1_normal');
        CharacterFriendQueue::queue($repo->toUpdate(1, Entity::PRIORITY_NORMAL), 'character_friends_update_1_normal');

        // 2 queues for patrons
        $this->io->text(__METHOD__ .' character_friends_update_0_patreon');
        CharacterFriendQueue::queue($repo->toUpdate(0, Entity::PRIORITY_HIGH), 'character_friends_update_0_patreon');
        // $this->io->text(__METHOD__ .' character_friends_update_1_patreon');
        // CharacterFriendQueue::queue($repo->toUpdate(1, Entity::PRIORITY_HIGH), 'character_friends_update_1_patreon');
    }

    private function queueAchievements()
    {
        /** @var CharacterAchievementRepository $repo */
        $repo = $this->em->getRepository(CharacterAchievements::class);

        // 6 queues for basic updates
        $this->io->text(__METHOD__ .' character_achievements_update_0_normal');
        CharacterAchievementQueue::queue($repo->toUpdate(0, Entity::PRIORITY_NORMAL), 'character_achievements_update_0_normal');
        $this->io->text(__METHOD__ .' character_achievements_update_1_normal');
        CharacterAchievementQueue::queue($repo->toUpdate(1, Entity::PRIORITY_NORMAL), 'character_achievements_update_1_normal');
        $this->io->text(__METHOD__ .' character_achievements_update_2_normal');
        CharacterAchievementQueue::queue($repo->toUpdate(1, Entity::PRIORITY_NORMAL), 'character_achievements_update_2_normal');
        $this->io->text(__METHOD__ .' character_achievements_update_3_normal');
        // CharacterAchievementQueue::queue($repo->toUpdate(1, Entity::PRIORITY_NORMAL), 'character_achievements_update_3_normal');
        // $this->io->text(__METHOD__ .' character_achievements_update_4_normal');
        // CharacterAchievementQueue::queue($repo->toUpdate(1, Entity::PRIORITY_NORMAL), 'character_achievements_update_4_normal');
        // $this->io->text(__METHOD__ .' character_achievements_update_5_normal');
        // CharacterAchievementQueue::queue($repo->toUpdate(1, Entity::PRIORITY_NORMAL), 'character_achievements_update_5_normal');

        // 2 queues for patrons
        $this->io->text(__METHOD__ .' character_achievements_update_0_patreon');
        CharacterAchievementQueue::queue($repo->toUpdate(0, Entity::PRIORITY_HIGH), 'character_achievements_update_0_patreon');
        // $this->io->text(__METHOD__ .' character_achievements_update_1_patreon');
        // CharacterAchievementQueue::queue($repo->toUpdate(1, Entity::PRIORITY_HIGH), 'character_achievements_update_1_patreon');
    }

    private function queueFreeCompanies()
    {
        /** @var FreeCompanyRepository $repo */
        $repo = $this->em->getRepository(FreeCompany::class);

        // 2 queues for updating free companies
        $this->io->text(__METHOD__ .' free_company_update_0_normal');
        FreeCompanyQueue::queue($repo->toUpdate(0, Entity::PRIORITY_NORMAL), 'free_company_update_0_normal');
        $this->io->text(__METHOD__ .' free_company_update_1_normal');
        FreeCompanyQueue::queue($repo->toUpdate(1, Entity::PRIORITY_NORMAL), 'free_company_update_1_normal');

        // 1 queue for updating patron free companies
        $this->io->text(__METHOD__ .' free_company_update_0_patron');
        FreeCompanyQueue::queue($repo->toUpdate(0, Entity::PRIORITY_HIGH), 'free_company_update_0_patron');
    }

    private function queueLinkshells()
    {
        /** @var LinkshellRepository $repo */
        $repo = $this->em->getRepository(Linkshell::class);

        // 2 queues for updating linkshells
        $this->io->text(__METHOD__ .' linkshell_update_0_normal');
        LinkshellQueue::queue($repo->toUpdate(0, Entity::PRIORITY_NORMAL), 'linkshell_update_0_normal');
        $this->io->text(__METHOD__ .' linkshell_update_1_normal');
        LinkshellQueue::queue($repo->toUpdate(1, Entity::PRIORITY_NORMAL), 'linkshell_update_1_normal');

        // 1 queue for updating patron linkshells
        $this->io->text(__METHOD__ .' linkshell_update_0_patron');
        LinkshellQueue::queue($repo->toUpdate(0, Entity::PRIORITY_HIGH), 'linkshell_update_0_patron');
    }

    private function queuePvpTeams()
    {
        /** @var PvPTeamRepository $repo */
        $repo = $this->em->getRepository(PvPTeam::class);

        // 2 queues for updating linkshells
        $this->io->text(__METHOD__ .' pvp_team_update_0_normal');
        PvPTeamQueue::queue($repo->toUpdate(0, Entity::PRIORITY_NORMAL), 'pvp_team_update_0_normal');
        $this->io->text(__METHOD__ .' pvp_team_update_1_normal');
        PvPTeamQueue::queue($repo->toUpdate(1, Entity::PRIORITY_NORMAL), 'pvp_team_update_1_normal');

        // 1 queue for updating patron linkshells
        $this->io->text(__METHOD__ .' pvp_team_update_0_patron');
        PvPTeamQueue::queue($repo->toUpdate(0, Entity::PRIORITY_HIGH), 'pvp_team_update_0_patron');
    }
}
