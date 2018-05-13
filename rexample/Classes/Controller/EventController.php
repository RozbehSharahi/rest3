<?php

namespace RozbehSharahi\Rexample\Controller;

use RozbehSharahi\Rest3\Controller\SimpleModelController;
use RozbehSharahi\Rexample\Domain\Model\Event;
use RozbehSharahi\Rexample\Domain\Repository\EventRepository;

class EventController extends SimpleModelController
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
