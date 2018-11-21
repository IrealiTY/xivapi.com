<?php

namespace App\Service\LodestoneQueue;

/**
 * These constants should match the Lodestone API Parser function calls
 */
class LodestoneApi
{
    const GET_CHARACTER = 'getCharacter';
    const GET_CHARACTER_FRIENDS = 'getCharacterFriends';
    const GET_CHARACTER_FOLLOWING = 'getCharacterFollowing';
    const GET_CHARACTER_ACHIEVEMENTS = 'getCharacterAchievements';
    const GET_CHARACTER_FC = 'getFreeCompany';
    const GET_CHARACTER_FC_MEMBERS = 'getFreeCompanyMembers';
    const GET_CHARACTER_LS = 'getLinkshellMembers';
    const GET_CHARACTER_PVP_MEMBERS= 'getPvPTeamMembers';
}
