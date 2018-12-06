<?php

namespace App\Service\Lodestone;

class ServiceQueues
{
    const CACHE_CHARACTER_QUEUE             = 'lodestone_characters';
    const CACHE_ACHIEVEMENTS_QUEUE          = 'lodestone_achievements';
    const CACHE_FRIENDS_QUEUE               = 'lodestone_friends';
    const CACHE_FREECOMPANY_QUEUE           = 'lodestone_freecompany';
    const CACHE_FREECOMPANY_MEMBERS_QUEUE   = 'lodestone_freecompany_members';
    const CACHE_LINKSHELL_QUEUE             = 'lodestone_linkshell';
    const CACHE_PVPTEAM_QUEUE               = 'lodestone_pvpteam';
    
    // 6 hours
    const UPDATE_TIMEOUT = (60 * 60 * 6);
    const TIME_LIMIT = 57;
    
    // total
    const TOTAL_ACHIEVEMENT_UPDATES  = 30;
    const TOTAL_PVP_TEAM_UPDATES     = 50;
    const TOTAL_LINKSHELL_UPDATES    = 50;
    const TOTAL_FREE_COMPANY_UPDATES = 50;
    const TOTAL_CHARACTER_UPDATES    = 100;
    const TOTAL_CHARACTER_FRIENDS    = 50;
}
