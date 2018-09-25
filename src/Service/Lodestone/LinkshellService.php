<?php

namespace App\Service\Lodestone;

use App\Entity\Linkshell;
use App\Service\Content\LodestoneData;
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
            $this->persist($ent->setUpdated($ent->getUpdated()-10));
            $data = LodestoneData::load('linkshell', 'data', $ent->getId());
        }
    
        return [ $ent, $data[0] ?? null, $data[1] ?? null ];
    }
    
    public function register($id): array
    {
        if (!is_numeric($id)) {
            throw new NotAcceptableHttpException("ID is not numeric: {$id}");
        }
    
        if (strlen($id) > 42) {
            throw new NotAcceptableHttpException("ID length is too long");
        }
        
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
