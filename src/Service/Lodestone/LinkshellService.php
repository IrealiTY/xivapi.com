<?php

namespace App\Service\Lodestone;

use App\Entity\Linkshell;
use App\Service\Content\LodestoneData;
use App\Service\LodestoneQueue\LinkshellQueue;
use App\Service\Service;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class LinkshellService extends Service
{
    public function get($id)
    {
        /** @var Linkshell $ent */
        $ent = $this->getRepository(Linkshell::class)->find($id);
        return $ent ? $this->fetch($ent) : $this->register($id);
    }
    
    public function fetch(Linkshell $ent): array
    {
        if ($ent->getState() === Linkshell::STATE_CACHED) {
            $data = LodestoneData::load('linkshell', 'data', $ent->getId());
        }
    
        return [ $ent, $data[0] ?? null, $data[1] ?? null ];
    }
    
    public function register($id): array
    {
        if (!is_numeric($id) || strlen($id) > 45) {
            throw new NotAcceptableHttpException("Invalid character id: {$id}");
        }
    
        // send a request to rabbit mq to add this character
        LinkshellQueue::request($id, 'linkshell_add');
        
        $ent = new Linkshell($id);
        $this->persist($ent);
    
        return [ $ent, null, null ];
    }
    
    public function delete(Linkshell $ent)
    {
        $path = LodestoneData::folder('linkshell', $ent->getId());
        @unlink("{$path}/data.json");
        $this->remove($ent);
    }
}
