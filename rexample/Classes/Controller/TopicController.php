<?php

namespace RozbehSharahi\Rexample\Controller;

use RozbehSharahi\Rest3\Controller\DomainObjectController;
use RozbehSharahi\Rexample\Domain\Model\Topic;
use RozbehSharahi\Rexample\Domain\Repository\TopicRepository;

class TopicController extends DomainObjectController
{

    /**
     * @var string
     */
    protected $repositoryName = TopicRepository::class;

    /**
     * @var string
     */
    protected $modelName = Topic::class;

}
