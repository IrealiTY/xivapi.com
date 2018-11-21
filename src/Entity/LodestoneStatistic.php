<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * - This has UpperCase variables as its game content
 * @ORM\Table(name="lodestone_statistic")
 * @ORM\Entity(repositoryClass="App\Repository\LodestoneStatisticRepository")
 */
class LodestoneStatistic
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $added;
    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $type;
    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $queue;
    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $method;
    /**
     * @var string
     * @ORM\Column(type="string", length=200)
     */
    private $arguments;
    /**
     * @var string
     * @ORM\Column(type="string", length=10)
     */
    private $status;
    /**
     * @var string
     * @ORM\Column(type="string", length=200)
     */
    private $response;
    
    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->added = time();
    }
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function setId(string $id)
    {
        $this->id = $id;
        
        return $this;
    }
    
    public function getAdded(): int
    {
        return $this->added;
    }
    
    public function setAdded(int $added)
    {
        $this->added = $added;
        
        return $this;
    }
    
    public function getType(): string
    {
        return $this->type;
    }
    
    public function setType(string $type)
    {
        $this->type = $type;
        
        return $this;
    }
    
    public function getQueue(): string
    {
        return $this->queue;
    }
    
    public function setQueue(string $queue)
    {
        $this->queue = $queue;
        
        return $this;
    }
    
    public function getMethod(): string
    {
        return $this->method;
    }
    
    public function setMethod(string $method)
    {
        $this->method = $method;
        
        return $this;
    }
    
    public function getArguments(): string
    {
        return $this->arguments;
    }
    
    public function setArguments(string $arguments)
    {
        $this->arguments = $arguments;
        
        return $this;
    }
    
    public function getStatus(): string
    {
        return $this->status;
    }
    
    public function setStatus(string $status)
    {
        $this->status = $status;
        
        return $this;
    }
    
    public function getResponse(): string
    {
        return $this->response;
    }
    
    public function setResponse(string $response)
    {
        $this->response = $response;
        
        return $this;
    }
}
