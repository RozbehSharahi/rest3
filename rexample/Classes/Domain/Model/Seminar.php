<?php

namespace RozbehSharahi\Rexample\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Seminar extends AbstractEntity
{

    /**
     * @var string
     */
    protected $title;

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

}
