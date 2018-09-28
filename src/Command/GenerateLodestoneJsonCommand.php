<?php

namespace App\Command;

use App\Service\Redis\Cache;
use Lodestone\Api;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

//
// */15 * * * * /usr/bin/php /home/dalamud/dalamud/bin/console GenerateLodestoneJsonCommand
//
class GenerateLodestoneJsonCommand extends Command
{
    use CommandHelperTrait;
    
    /** @var Cache $cache */
    private $cache;
    
    public function __construct(?string $name = null, Cache $cache)
    {
        parent::__construct($name);
        $this->cache = $cache;
    }
    
    protected function configure()
    {
        $this
            ->setName('GenerateLodestoneJsonCommand')
            ->setDescription('Generate Lodestone Content')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->io->title('Starting');

        $data = (Object)[
            'Generated' => time(),
        ];

        $api = new Api();
        $data->Banners          = $api->getLodestoneBanners();
        $data->News             = $api->getLodestoneNews();
        $data->Topics           = $api->getLodestoneTopics();
        $data->Notices          = $api->getLodestoneNotices();
        $data->Maintenance      = $api->getLodestoneMaintenance();
        $data->Updates          = $api->getLodestoneUpdates();
        $data->Status           = $api->getLodestoneStatus();
        $data->WorldStatus      = $api->getWorldStatus();
        $data->DevBlog          = $api->getDevBlog();
        $data->DevPosts         = $api->getDevPosts();

        // on first cronjob
        if (date('i') < 10) {
            // pre-cache lodestone stuff, this is pretty dirty lol
            file_get_contents('http://xivapi.com/lodestone/banners');
            file_get_contents('http://xivapi.com/lodestone/news');
            file_get_contents('http://xivapi.com/lodestone/topics');
            file_get_contents('http://xivapi.com/lodestone/notices');
            file_get_contents('http://xivapi.com/lodestone/maintenance');
            file_get_contents('http://xivapi.com/lodestone/updates');
            file_get_contents('http://xivapi.com/lodestone/status');
            file_get_contents('http://xivapi.com/lodestone/worldstatus');
            file_get_contents('http://xivapi.com/lodestone/devblog');
            file_get_contents('http://xivapi.com/lodestone/devposts');
        }

        // cache for 24 hours (it's overwritten every 15 minutes)
        $this->cache->set('lodestone', $data, (60*60*24));
        $this->complete();
    }
}
