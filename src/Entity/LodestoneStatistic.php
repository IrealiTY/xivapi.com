<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="lodestone_statistic",
 *     indexes={
 *          @ORM\Index(name="added", columns={"added"}),
 *          @ORM\Index(name="queue", columns={"queue"}),
 *          @ORM\Index(name="status", columns={"status"}),
 *          @ORM\Index(name="method", columns={"method"}),
 *          @ORM\Index(name="duration", columns={"duration"}),
 *          @ORM\Index(name="cronjob", columns={"cronjob"})
 *     }
 * )
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
     * @var float
     * @ORM\Column(type="float")
     */
    private $duration;
    /**
     * @var string
     * @ORM\Column(type="string", length=200)
     */
    private $response;
    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $cronjob;
    
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

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function setDuration(float $duration)
    {
        $this->duration = $duration;

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

    public function getCronjob(): string
    {
        return $this->cronjob;
    }

    public function setCronjob(string $cronjob)
    {
        $this->cronjob = $cronjob;

        return $this;
    }
}
