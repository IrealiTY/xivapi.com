<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="apps")
 * @ORM\Entity(repositoryClass="App\Repository\AppRepository")
 */
class App
{
    const DEFAULT_API_KEY = 'default';

    const RATE_LIMITS = [
        1 => 1,
        2 => 2,
        3 => 10,
        4 => 20,
        5 => 30,
    ];

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
     * @var bool
     * @ORM\Column(type="boolean", name="is_default")
     */
    private $default = false;
    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="apps")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;
    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $name;
    /**
     * @var string
     * @ORM\Column(type="string", length=400, nullable=true)
     */
    private $description;
    /**
     * @var int
     * @ORM\Column(type="integer", length=2)
     */
    private $level = 2;
    /**
     * @var string
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private $apiKey;
    /**
     * the number of requests per second per ip
     * @var integer
     * @ORM\Column(type="integer", length=4)
     */
    private $apiRateLimit = 5;
    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $toolAccessMappy = false;
    
    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->generateApiKey();
        $this->added = time();
    }
    
    public function generateApiKey()
    {
        $this->apiKey = substr(str_ireplace('-', null,
            Uuid::uuid4()->toString() . Uuid::uuid4()->toString()), 0, 24
        );
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

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function setDefault(bool $default)
    {
        $this->default = $default;
        $this->level = 1;
        $this->apiRateLimit = self::RATE_LIMITS[1];
        $this->apiKey = 'default';
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level)
    {
        $this->level = $level;

        $this->setApiRateLimit(
            self::RATE_LIMITS[$this->level]
        );

        return $this;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function getApiRateLimit(): int
    {
        return $this->apiRateLimit;
    }

    public function setApiRateLimit(int $apiRateLimit)
    {
        $this->apiRateLimit = $apiRateLimit;
        return $this;
    }

    public function isToolAccessMappy(): bool
    {
        return $this->toolAccessMappy;
    }

    public function setToolAccessMappy(bool $toolAccessMappy)
    {
        $this->toolAccessMappy = $toolAccessMappy;
        return $this;
    }

    public function hasMappyAccess(): bool
    {
        return $this->toolAccessMappy;
    }

    public function isLimited()
    {
        return (time() - $this->added) < 3600;
    }
}
