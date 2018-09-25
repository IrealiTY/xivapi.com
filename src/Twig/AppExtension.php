<?php

namespace App\Twig;

use Carbon\Carbon;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('dateRelative', [$this, 'getDateRelative']),
        ];
    }
    
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('apiVersion', [$this, 'getApiVersion']),
            new \Twig_SimpleFunction('apiHash', [$this, 'getApiHash']),
            new \Twig_SimpleFunction('favIcon', [$this, 'getFavIcon']),
        ];
    }


    
    public function getDateRelative($unix)
    {
        $unix = is_numeric($unix) ? $unix : strtotime($unix);
        $difference = time() - $unix;
        
        // if over 72hrs, show date
        if ($difference > (60 * 60 * 72)) {
            return date('M jS', $unix);
        }
        
        return Carbon::now()->subSeconds($difference)->diffForHumans();
    }
    
    public function getApiVersion()
    {
        [$version, $hash] = explode("\n", file_get_contents(__DIR__.'/../../git_version.txt'));
        $version = substr_replace($version, '.', 2, 0);

        return sprintf('%s.%s', getenv('VERSION'), $version);
    }

    public function getApiHash()
    {
        [$version, $hash] = explode("\n", file_get_contents(__DIR__.'/../../git_version.txt'));
        return $hash;
    }
    
    public function getFavIcon()
    {
        return getenv('APP_ENV') == 'dev' ? '/favicon_dev.png' : '/favicon.png';
    }
}
