<?php

namespace App\Entity;

use App\Repository\GroupDeletePostNotificationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class GroupDeletePostNotification
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

    /**
     * @var int
     *
     * @ORM\Column(name="id_group", type="integer", nullable=false)
     */
    private $idGroup;

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

    public function getIdGroup(): ?int
    {
        return $this->idGroup;
    }

    public function setIdGroup(int $idGroup): self
    {
        $this->idGroup = $idGroup;

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