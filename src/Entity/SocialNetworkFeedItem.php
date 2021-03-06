<?php declare(strict_types=1);

namespace App\Entity;

use App\Criticalmass\Website\Crawler\Crawlable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="social_network_feed_item", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="unique_feed_item", columns={"social_network_profile_id", "uniqueIdentifier"})
 *    })
 * @ORM\Entity(repositoryClass="App\Repository\SocialNetworkFeedItemRepository")
 */
class SocialNetworkFeedItem //implements Crawlable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="SocialNetworkProfile", inversedBy="feedItems")
     * @ORM\JoinColumn(name="social_network_profile_id", referencedColumnName="id")
     */
    protected $socialNetworkProfile;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $uniqueIdentifier;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $permalink;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $title;

    /**
     * @ORM\Column(type="text", nullable=false)
     */
    protected $text;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $dateTime;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $hidden = false;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $deleted = false;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * ORM\Column(type="boolean")
     */
    //protected $crawled = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): SocialNetworkFeedItem
    {
        $this->id = $id;

        return $this;
    }

    public function getSocialNetworkProfile(): SocialNetworkProfile
    {
        return $this->socialNetworkProfile;
    }

    public function setSocialNetworkProfile(SocialNetworkProfile $socialNetworkProfile): SocialNetworkFeedItem
    {
        $this->socialNetworkProfile = $socialNetworkProfile;

        return $this;
    }

    public function getUniqueIdentifier(): string
    {
        return $this->uniqueIdentifier;
    }

    public function setUniqueIdentifier(string $uniqueIdentifier): SocialNetworkFeedItem
    {
        $this->uniqueIdentifier = $uniqueIdentifier;

        return $this;
    }

    public function getPermalink(): string
    {
        return $this->permalink;
    }

    public function setPermalink(string $permalink): SocialNetworkFeedItem
    {
        $this->permalink = $permalink;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): SocialNetworkFeedItem
    {
        $this->title = $title;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): SocialNetworkFeedItem
    {
        $this->text = $text;

        return $this;
    }

    public function getDateTime(): \DateTime
    {
        return $this->dateTime;
    }

    public function setDateTime(\DateTime $dateTime): SocialNetworkFeedItem
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    public function getHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): SocialNetworkFeedItem
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): SocialNetworkFeedItem
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): SocialNetworkFeedItem
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /*public function isCrawled(): bool
    {
        return $this->crawled;
    }

    public function setCrawled(bool $crawled): Crawlable
    {
        $this->crawled = $crawled;

        return $this;
    }*/
}
