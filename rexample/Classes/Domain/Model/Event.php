<?php

namespace RozbehSharahi\Rexample\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Event extends AbstractEntity
{

    /**
     * @var string
     */
    protected $title;

    /**
     * @var \RozbehSharahi\Rexample\Domain\Model\Seminar
     */
    protected $seminar;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Event
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return Seminar
     */
    public function getSeminar()
    {
        return $this->seminar;
    }

    /**
     * @param Seminar $seminar
     * @return Event
     */
    public function setSeminar(Seminar $seminar = null)
    {
        $this->seminar = $seminar;
        return $this;
    }

}
