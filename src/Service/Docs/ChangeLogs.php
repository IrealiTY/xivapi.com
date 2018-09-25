<?php

namespace App\Service\Docs;

use App\Service\Redis\Cache;

class ChangeLogs
{
    public function get()
    {
        // this is going to break while its on github, needs switching overrrrrrorororororororoorrorooror
        return [];

        /*
        $cache = new Cache();

        $commits = $cache->get('bitbucket_commits');

        // grab latest commits
        if (!$commits) {
            $oAuthParams = [
                'oauth_consumer_key'    => getenv('BB_OAUTH_CONSUMER_KEY'),
                'oauth_consumer_secret' => getenv('BB_OAUTH_CONSUMER_SECRET')
            ];

            $commits = new \Bitbucket\API\Repositories\Commits();
            $commits->getClient()->addListener(
                new \Bitbucket\API\Http\Listener\OAuthListener($oAuthParams)
            );

            $commits = $commits->all('dalamud', 'xivapi');
            $commits = json_decode($commits->getContent());

            // cache for 1 hour
            $cache->set('bitbucket_commits', $commits, (60*1));
        }

        return $commits;
        */
    }
}
