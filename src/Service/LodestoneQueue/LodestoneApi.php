<?php

namespace App\Service\LodestoneQueue;

/**
 * These constants should match the Lodestone API Parser function calls
 */
class LodestoneApi
{
    const GET_CHARACTER                 = 'getCharacter';
    const GET_CHARACTER_FRIENDS         = 'getCharacterFriends';
    const GET_CHARACTER_FOLLOWING       = 'getCharacterFollowing';
    const GET_CHARACTER_ACHIEVEMENTS    = 'getCharacterAchievements';
    // todo - FC + FC Members needs combining into 1 call and does all pages
    const GET_FREE_COMPANY              = 'getFreeCompany';
    const GET_FREE_COMPANY_MEMBERS      = 'getFreeCompanyMembers';
    // todo - ls members should parse all pages
    const GET_LINKSHELL_MEMBERS         = 'getLinkshellMembers';
    // todo - pvp teams should parse all pages
    const GET_PVP_TEAM_MEMBERS          = 'getPvPTeamMembers';
}
