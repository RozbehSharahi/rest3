<?php

namespace RozbehSharahi\Rexample\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Seminar extends AbstractEntity
{

    /**
     * @var string
     */
    protected $title;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\RozbehSharahi\Rexample\Domain\Model\Event>
     */
    protected $events;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     */
    protected $images;

    /**
     * Seminar constructor.
     */
    public function __construct()
    {
        $this->events = new ObjectStorage();
        $this->images = new ObjectStorage();
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Seminar
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return ObjectStorage
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param ObjectStorage $events
     * @return Seminar
     */
    public function setEvents(ObjectStorage $events)
    {
        $this->events = $events;
        return $this;
    }

    /**
     * @return ObjectStorage
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param ObjectStorage $images
     * @return Seminar
     */
    public function setImages(ObjectStorage $images)
    {
        $this->images = $images;
        return $this;
    }

}
