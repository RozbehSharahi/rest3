<?php

namespace RozbehSharahi\Rexample;

use RozbehSharahi\Rest3\Normalizer\DomainObjectNormalizer;

class SeminarNormalizer extends DomainObjectNormalizer
{

    protected $excludedAttributes = [
        'description'
    ];

}