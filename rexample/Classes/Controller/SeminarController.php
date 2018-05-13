<?php

namespace RozbehSharahi\Rexample\Controller;

use RozbehSharahi\Rest3\Controller\SimpleModelController;
use RozbehSharahi\Rexample\Domain\Model\Seminar;
use RozbehSharahi\Rexample\Domain\Repository\SeminarRepository;

class SeminarController extends SimpleModelController
{

    /**
     * @var string
     */
    protected $repositoryName = SeminarRepository::class;

    /**
     * @var string
     */
    protected $modelName = Seminar::class;

}
