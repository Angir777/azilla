<?php

namespace App\Entity;

use App\Repository\HashtagSwitchRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class HashtagSwitch
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
     * @var string
     *
     * @ORM\Column(name="tag_name", type="string", nullable=true)
     */
    private $tagName;

    /**
     * @var string
     *
     * @ORM\Column(name="tag_url", type="string", nullable=true)
     */
    private $tagUrl;

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

    public function getTagName(): ?string
    {
        return $this->tagName;
    }

    public function setTagName(string $tagName): self
    {
        $this->tagName = $tagName;

        return $this;
    }

    public function getTagUrl(): ?string
    {
        return $this->tagUrl;
    }

    public function setTagUrl(string $tagUrl): self
    {
        $this->tagUrl = $tagUrl;

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