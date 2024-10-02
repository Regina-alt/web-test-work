<?php

namespace App\Entity;

use App\Repository\UrlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UrlRepository::class)
 */
class Url
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=14)
     */
    private $hash;

    /**
     * @ORM\Column(name="created_date", type="datetime_immutable")
     */
    private $createdDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $expiresAt;

    private bool $sent = false;

    public function __construct()
    {
        $date = new \DateTimeImmutable();
        $this->setCreatedDate($date);
        $this->setHash($date->format('YmdHis'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isSent(): ?bool
    {
        return $this->sent;
    }

    public function setSent(bool $sent): self
    {
        $this->sent = $sent;

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

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getCreatedDate(): ?\DateTimeImmutable
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTimeImmutable $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }
}
