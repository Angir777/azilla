<?php

namespace App\Entity;

use App\Repository\ReportUserRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ReportUser
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="id_user_a", type="integer", nullable=false)
     */
    private $idUserA;

    /**
     * @var int
     *
     * @ORM\Column(name="id_user_b", type="integer", nullable=false)
     */
    private $idUserB;

    /**
     * @var int
     *
     * @ORM\Column(name="id_post", type="integer", nullable=false)
     */
    private $idPost;

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdUserA(): ?int
    {
        return $this->idUserA;
    }

    public function setIdUserA(int $idUserA): self
    {
        $this->idUserA = $idUserA;

        return $this;
    }

    public function getIdUserB(): ?int
    {
        return $this->idUserB;
    }

    public function setIdUserB(int $idUserB): self
    {
        $this->idUserB = $idUserB;

        return $this;
    }

    public function getIdPost(): ?int
    {
        return $this->idPost;
    }

    public function setIdPost(int $idPost): self
    {
        $this->idPost = $idPost;

        return $this;
    }
}