<?php

namespace RozbehSharahi\Rexample\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Location extends AbstractEntity
{

    /**
     * @var string
     */
    protected $title;

    /**
     * @var \RozbehSharahi\Rexample\Domain\Model\Event
     */
    protected $event;

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
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param Event $event
     * @return Location
     */
    public function setEvent(Event $event = null)
    {
        $this->event = $event;
        return $this;
    }

}
