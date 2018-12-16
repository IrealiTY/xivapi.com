<?php

namespace App\Service\LodestoneQueue;

/**
 * These constants should match the Lodestone API Parser function calls
 */
class LodestoneApi
{
    const GET_CHARACTER                   = 'getCharacter';
    // todo - this needs a "full" method
    const GET_CHARACTER_FRIENDS           = 'getCharacterFriends';
    const GET_CHARACTER_FOLLOWING         = 'getCharacterFollowing';
    const GET_CHARACTER_ACHIEVEMENTS      = 'getCharacterAchievements';
    const GET_CHARACTER_ACHIEVEMENTS_FULL = 'getCharacterAchievements';

    const GET_FREE_COMPANY                = 'getFreeCompany';
    const GET_FREE_COMPANY_FULL           = 'getFreeCompanyFull';
    const GET_FREE_COMPANY_MEMBERS        = 'getFreeCompanyMembers';
    
    const GET_LINKSHELL_MEMBERS           = 'getLinkshellMembers';
    const GET_LINKSHELL_MEMBERS_FULL      = 'getLinkshellMembersFull';

    const GET_PVP_TEAM_MEMBERS            = 'getPvPTeamMembers';
}
