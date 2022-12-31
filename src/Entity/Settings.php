<?php

namespace App\Entity;

use App\Repository\SettingsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Settings
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
     * @ORM\Column(name="email_notifications", type="integer", nullable=false)
     */
    private $emailNotifications;

    /**
     * @var int
     *
     * @ORM\Column(name="dark_theme", type="integer", nullable=false)
     */
    private $darkTheme;

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
     * @ORM\Column(name="nick_show", type="integer", nullable=false)
     */
    private $nickShow;

    /**
     * @var int
     *
     * @ORM\Column(name="availability", type="integer", nullable=false)
     */
    private $availability;

    /**
     * @var int
     *
     * @ORM\Column(name="account_type", type="integer", nullable=false)
     */
    private $accountType;

    /**
     * @var string
     *
     * @ORM\Column(name="user_description", type="string", nullable=true)
     */
    private $userDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="avatar", type="string", nullable=true)
     */
    private $avatar;

    /**
     * @var string
     *
     * @ORM\Column(name="background", type="string", nullable=true)
     */
    private $background;

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

    public function getEmailNotifications(): ?int
    {
        return $this->emailNotifications;
    }

    public function setEmailNotifications(int $emailNotifications): self
    {
        $this->emailNotifications = $emailNotifications;

        return $this;
    }

    public function getDarkTheme(): ?int
    {
        return $this->darkTheme;
    }

    public function setDarkTheme(int $darkTheme): self
    {
        $this->darkTheme = $darkTheme;

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

    public function getNickShow(): ?int
    {
        return $this->nickShow;
    }

    public function setNickShow(int $nickShow): self
    {
        $this->nickShow = $nickShow;

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

    public function getAccountType(): ?int
    {
        return $this->accountType;
    }

    public function setAccountType(int $accountType): self
    {
        $this->accountType = $accountType;

        return $this;
    }

    public function getUserDescription(): ?string
    {
        return $this->userDescription;
    }

    public function setUserDescription(string $userDescription): self
    {
        $this->userDescription = $userDescription;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getBackground(): ?string
    {
        return $this->background;
    }

    public function setBackground(string $background): self
    {
        $this->background = $background;

        return $this;
    }
}