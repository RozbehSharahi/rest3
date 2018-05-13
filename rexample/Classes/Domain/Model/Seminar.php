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
     * Seminar constructor.
     */
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

}
