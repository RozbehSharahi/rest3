<?php

namespace RozbehSharahi\Rexample\Controller;

use RozbehSharahi\Rest3\Controller\DomainObjectController;
use RozbehSharahi\Rexample\Domain\Model\Event;
use RozbehSharahi\Rexample\Domain\Repository\EventRepository;

class EventController extends DomainObjectController
{

    /**
     * @var string
     */
    protected $repositoryName = EventRepository::class;

    /**
     * @var string
     */
    protected $modelName = Event::class;

}
