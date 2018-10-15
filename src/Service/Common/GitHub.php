<?php

namespace App\Service\Common;

use App\Service\Redis\Cache;
use PhpParser\Node\Expr\Cast\Object_;

/**
 * Access GitHub repository information
 */
class GitHub
{
    public static function getGithubCommitHistory()
    {
        $key     = 'github_commits';
        $cache   = new Cache();
        $commits = $cache->get($key);

        if (!$commits) {
            $client  = new \Github\Client();

            $commits = (Object)[
                'master'  => $client->api('repo')->commits()->all('xivapi', 'xivapi.com', ['sha' => 'master']),
                'staging' => $client->api('repo')->commits()->all('xivapi', 'xivapi.com', ['sha' => 'staging'])
            ];

            // cache for an hour, I don't commit that often!
            $cache->set($key, $commits, 60*60);
        }

        //
        // Attempt to find the current master or staging commit
        //

        $commits->current = null;
        foreach ($commits->master as $commit) {
            if ($commit->sha == SiteVersion::get()->hash) {
                $commits->current = $commit;
                break;
            }
        }

        foreach ($commits->staging as $commit) {
            if ($commit->sha == SiteVersion::get()->hash) {
                $commits->current = $commit;
                break;
            }
        }

        return $commits;
    }
}
