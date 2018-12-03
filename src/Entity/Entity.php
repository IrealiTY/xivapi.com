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

    const PRIORITY_NORMAL   = 0;  // everyone gets this
    const PRIORITY_DEAD     = 1;  // Characters considered dead
    const PRIORITY_LOW      = 2;  // characters that hardly change,
    const PRIORITY_HIGH     = 10; // patreon characters
    
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
    /**
     * @ORM\Column(type="integer", length=16)
     */
    public $priority = 0;
    
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

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

}
