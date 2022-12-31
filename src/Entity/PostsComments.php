<?php

namespace App\Entity;

use App\Repository\PostsCommentsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class PostsComments
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
     * @ORM\Column(name="id_post", type="integer", nullable=false)
     */
    private $idPost;

    /**
     * @var int
     *
     * @ORM\Column(name="id_author", type="integer", nullable=false)
     */
    private $idAuthor;

    /**
     * @var string
     *
     * @ORM\Column(name="date", type="string", nullable=false)
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="id_parent", type="string", nullable=true)
     */
    private $idParent;

    /**
     * @var string
     *
     * @ORM\Column(name="id_parent2", type="string", nullable=true)
     */
    private $idParent2;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="string", nullable=false)
     */
    private $content;

    /**
     * @var int
     *
     * @ORM\Column(name="status_notification1", type="integer", nullable=false)
     */
    private $statusNotification1;

    /**
     * @var int
     *
     * @ORM\Column(name="status_notification2", type="integer", nullable=false)
     */
    private $statusNotification2;

    public function getId(): int
    {
        return $this->id;
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

    public function getIdAuthor(): ?int
    {
        return $this->idAuthor;
    }

    public function setIdAuthor(int $idAuthor): self
    {
        $this->idAuthor = $idAuthor;

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

    public function getIdParent(): ?string
    {
        return $this->idParent;
    }

    public function setIdParent(string $idParent): self
    {
        $this->idParent = $idParent;

        return $this;
    }

    public function getIdParent2(): ?string
    {
        return $this->idParent2;
    }

    public function setIdParent2(string $idParent2): self
    {
        $this->idParent2 = $idParent2;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getStatusNotification1(): ?int
    {
        return $this->statusNotification1;
    }

    public function setStatusNotification1(int $statusNotification1): self
    {
        $this->statusNotification1 = $statusNotification1;

        return $this;
    }

    public function getStatusNotification2(): ?int
    {
        return $this->statusNotification2;
    }

    public function setStatusNotification2(int $statusNotification2): self
    {
        $this->statusNotification2 = $statusNotification2;

        return $this;
    }

}