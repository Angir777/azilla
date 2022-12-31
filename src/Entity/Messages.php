<?php

namespace App\Entity;

use App\Repository\MessagesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Messages
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
     * @ORM\Column(name="conversation", type="integer", nullable=false)
     */
    private $conversation;

    /**
     * @var int
     *
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser;

    /**
     * @var string
     *
     * @ORM\Column(name="msg", type="string", nullable=false)
     */
    private $msg;

    /**
     * @var string
     *
     * @ORM\Column(name="date", type="string", nullable=false)
     */
    private $date;

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

    public function getConversation(): ?int
    {
        return $this->conversation;
    }

    public function setConversation(int $conversation): self
    {
        $this->conversation = $conversation;

        return $this;
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

    public function getMsg(): ?string
    {
        return $this->msg;
    }

    public function setMsg(string $msg): self
    {
        $this->msg = $msg;

        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;

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