<?php

namespace RozbehSharahi\Rexample\Controller;

use RozbehSharahi\Rest3\Controller\DomainObjectController;
use RozbehSharahi\Rexample\Domain\Model\Location;
use RozbehSharahi\Rexample\Domain\Repository\LocationRepository;

class LocationController extends DomainObjectController
{

    /**
     * @var string
     */
    protected $repositoryName = LocationRepository::class;

    /**
     * @var string
     */
    protected $modelName = Location::class;

}
