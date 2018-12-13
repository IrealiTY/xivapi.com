<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="lodestone_freecompany",
 *     indexes={
 *          @ORM\Index(name="state", columns={"state"}),
 *          @ORM\Index(name="updated", columns={"updated"}),
 *          @ORM\Index(name="priority", columns={"priority"}),
 *          @ORM\Index(name="notFoundChecks", columns={"notFoundChecks"}),
 *          @ORM\Index(name="achievementsPrivateChecks", columns={"achievementsPrivateChecks"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\FreeCompanyRepository")
 */
class FreeCompany extends Entity
{

}
