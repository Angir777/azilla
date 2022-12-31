<?php

namespace App\Entity;

use App\Repository\RatingPostSwitchRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class RatingPostSwitch
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
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser;

    /**
     * @var int
     *
     * @ORM\Column(name="id_post", type="integer", nullable=false)
     */
    private $idPost;

    /**
     * @var int
     *
     * @ORM\Column(name="rating", type="integer", nullable=false)
     */
    private $rating;

    /**
     * @var int
     *
     * @ORM\Column(name="status_notification", type="integer", nullable=false)
     */
    private $statusNotification;

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdUser(): ?int
    {
        return $this->idUser;
    }

    public function setIdUser(int $idUser): self
    {
        $this->idUser = $idUser;

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

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): self
    {
        $this->rating = $rating;

        return $this;
    }

    public function getStatusNotification(): ?int
    {
        return $this->statusNotification;
    }

    public function setStatusNotification(int $statusNotification): self
    {
        $this->statusNotification = $statusNotification;

        return $this;
    }
}