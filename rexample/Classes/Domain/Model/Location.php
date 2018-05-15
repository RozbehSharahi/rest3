<?php

namespace RozbehSharahi\Rexample\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Location extends AbstractEntity
{

    /**
     * @var string
     */
    protected $title;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\RozbehSharahi\Rexample\Domain\Model\Event>
     */
    protected $events;

    public function __construct()
    {
        $this->events = new ObjectStorage();
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
     * @return Location
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
     * @return Location
     */
    public function setEvent(ObjectStorage $events)
    {
        $this->events = $events;
        return $this;
    }

}
