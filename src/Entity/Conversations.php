<?php

namespace App\Entity;

use App\Repository\ConversationsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Conversations
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
     * @ORM\Column(name="conversation", type="integer", nullable=false)
     */
    private $conversation;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer", nullable=false)
     */
    private $position;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

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

    public function getConversation(): ?int
    {
        return $this->conversation;
    }

    public function setConversation(int $conversation): self
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }
}