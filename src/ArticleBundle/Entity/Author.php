<?php

namespace ArticleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Author
 *
 * @ORM\Table(name="author")
 * @ORM\Entity(repositoryClass="ArticleBundle\Repository\AuthorRepository")
 */
class Author
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="site", type="string", length=100)
     */
    private $site;

    /**
     * @var int
     *
     * @ORM\OneToOne(targetEntity="Article", inversedBy="author")
     */
    private $article;

/*
    public function __construct()
    {
        $this->article = new ArrayCollection();
    }
*/
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Tag
     */
    public function setName($name)
    {
        $this->name = trim( strip_tags($name) );

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setSite($site)
    {
        $this->site = trim( strip_tags($site) );

        return $this;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function setArticle($article)
    {
        $this->article = $article;

        return $this;
    }

    public function getArticle()
    {
        return $this->article;
    }


}

