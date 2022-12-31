<?php

namespace App\Entity;

use App\Repository\PostsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Posts
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
     * @ORM\Column(name="id_author", type="integer", nullable=false)
     */
    private $idAuthor;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="post_url", type="string", nullable=false)
     */
    private $postUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="string", nullable=true)
     */
    private $text;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", nullable=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="url_video", type="string", nullable=true)
     */
    private $urlVideo;

    /**
     * @var string
     *
     * @ORM\Column(name="url_photos", type="string", nullable=true)
     */
    private $urlPhotos;

    /**
     * @var string
     *
     * @ORM\Column(name="date_added", type="string", nullable=false)
     */
    private $dateAdded;

    /**
     * @var string
     *
     * @ORM\Column(name="time_added", type="string", nullable=false)
     */
    private $timeAdded;

    /**
     * @var int
     *
     * @ORM\Column(name="number_likes", type="integer", nullable=false)
     */
    private $numberLikes;

    /**
     * @var int
     *
     * @ORM\Column(name="number_dislikes", type="integer", nullable=false)
     */
    private $numberDislikes;

    /**
     * @var int
     *
     * @ORM\Column(name="spoiler", type="integer", nullable=false)
     */
    private $spoiler;

    /**
     * @var int
     *
     * @ORM\Column(name="nsfw", type="integer", nullable=false)
     */
    private $nsfw;

    /**
     * @var int
     *
     * @ORM\Column(name="id_group", type="integer", nullable=false)
     */
    private $idGroup;

    /**
     * @var int
     *
     * @ORM\Column(name="availability", type="integer", nullable=false)
     */
    private $availability;

    public function getId(): int
    {
        return $this->id;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPostUrl(): ?string
    {
        return $this->postUrl;
    }

    public function setPostUrl(string $postUrl): self
    {
        $this->postUrl = $postUrl;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrlVideo(): ?string
    {
        return $this->urlVideo;
    }

    public function setUrlVideo(string $urlVideo): self
    {
        $this->urlVideo = $urlVideo;

        return $this;
    }

    public function getUrlPhotos(): ?string
    {
        return $this->urlPhotos;
    }

    public function setUrlPhotos(string $urlPhotos): self
    {
        $this->urlPhotos = $urlPhotos;

        return $this;
    }

    public function getDateAdded(): ?string
    {
        return $this->dateAdded;
    }

    public function setDateAdded(string $dateAdded): self
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    public function getTimeAdded(): ?string
    {
        return $this->timeAdded;
    }

    public function setTimeAdded(string $timeAdded): self
    {
        $this->timeAdded = $timeAdded;

        return $this;
    }

    public function getNumberLikes(): ?int
    {
        return $this->numberLikes;
    }

    public function setNumberLikes(int $numberLikes): self
    {
        $this->numberLikes = $numberLikes;

        return $this;
    }

    public function getNumberDislikes(): ?int
    {
        return $this->numberDislikes;
    }

    public function setNumberDislikes(int $numberDislikes): self
    {
        $this->numberDislikes = $numberDislikes;

        return $this;
    }

    public function getSpoiler(): ?int
    {
        return $this->spoiler;
    }

    public function setSpoiler(int $spoiler): self
    {
        $this->spoiler = $spoiler;

        return $this;
    }

    public function getNsfw(): ?int
    {
        return $this->nsfw;
    }

    public function setNsfw(int $nsfw): self
    {
        $this->nsfw = $nsfw;

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

    public function getAvailability(): ?int
    {
        return $this->availability;
    }

    public function setAvailability(int $availability): self
    {
        $this->availability = $availability;

        return $this;
    }
}