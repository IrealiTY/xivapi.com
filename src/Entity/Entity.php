<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

class Entity
{
    const STATE_NONE        = 0;
    const STATE_ADDING      = 1;
    const STATE_CACHED      = 2;
    const STATE_NOT_FOUND   = 3;
    const STATE_BLACKLISTED = 4;
    const STATE_PRIVATE     = 5;
    
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=64)
     */
    public $id;
    /**
     * @ORM\Column(type="integer", length=2)
     */
    public $state = self::STATE_ADDING;
    /**
     * @ORM\Column(type="integer", length=16)
     */
    public $updated = 0;
    
    public function __construct(string $id)
    {
        $this->id = $id;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setId($id)
    {
        $this->id = $id;
        
        return $this;
    }
    
    public function getState()
    {
        return $this->state;
    }
    
    public function setState($state)
    {
        $this->state = $state;
        
        return $this;
    }
    
    public function getUpdated()
    {
        return $this->updated;
    }
    
    public function setUpdated($updated)
    {
        $this->updated = $updated;
        $this->updated = $this->updated < 0 ? 0 : $this->updated;
        return $this;
    }
}
