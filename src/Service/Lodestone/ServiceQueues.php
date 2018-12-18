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
    
    // timeout for manual update
    const UPDATE_TIMEOUT = 86400;
    
    // maximum characters to process per minute
    const TOTAL_ACHIEVEMENT_UPDATES  = 15;  // Avg: 4.0s - Always 10 pages
    const TOTAL_PVP_TEAM_UPDATES     = 50;  // Avg: 0.4s - Usually only 1 page
    const TOTAL_LINKSHELL_UPDATES    = 40;  // Avg: 0.4s - Max 3 pages
    const TOTAL_FREE_COMPANY_UPDATES = 15;  // Avg: 1s   - Max: 10 pages
    const TOTAL_CHARACTER_UPDATES    = 80;  // Avg: 0.4s - Max 1 page
    const TOTAL_CHARACTER_FRIENDS    = 50;  // Avg: 0.4s - Max 4 pages
}
