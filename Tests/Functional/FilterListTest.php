<?php

namespace TYPO3\CMS\Core\Tests\Functional;

use RozbehSharahi\Rest3\FilterList\Filter\FilterInterface;
use RozbehSharahi\Rest3\FilterList\Filter\ManyToManyFilter;
use RozbehSharahi\Rest3\FilterList\FilterList;
use RozbehSharahi\Rest3\FilterList\Filter\AttributeFilter;
use RozbehSharahi\Rest3\FilterList\Filter\ManyToOneFilter;
use RozbehSharahi\Rest3\FilterList\Filter\OneToManyFilter;
use RozbehSharahi\Rest3\Tests\Functional\FunctionalTestBase;
use RozbehSharahi\Rexample\Domain\Repository\EventRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;

class FilterListTest extends FunctionalTestBase
{

    /**
     * @test
     */
    public function canListWithFilterSet()
    {
        $this->setUpTestWebsite();
        $this->setUpDatabaseData('tx_rexample_domain_model_seminar', [
            [
                'title' => 'Seminar 1'
            ],
            [
                'title' => 'Seminar 2'
            ],
            [
                'title' => 'Seminar 3'
            ],
            [
                'title' => 'Seminar 4'
            ],
            [
                'title' => 'Seminar 5'
            ],
            [
                'title' => 'Seminar 6'
            ],
            [
                'title' => 'Seminar 7'
            ],
            [
                'title' => 'Seminar 8 (Filtered out)'
            ],
        ]);
        $this->setUpDatabaseData('tx_rexample_domain_model_event', [
            [
                'title' => 'Event 1',
                'seminar' => 1,
            ],
            [
                'title' => 'Event 2',
                'seminar' => 1,
            ],
            [
                'title' => 'Event 3',
                'seminar' => 1,
            ],
            [
                'title' => 'Event 4',
                'seminar' => 3,
            ],
            [
                'title' => 'Event 5',
                'seminar' => 3,
            ],
            [
                'title' => 'Event 6',
                'seminar' => 4,
            ],
            [
                'title' => 'Event Last',
            ],
            [
                'title' => 'Event Last',
                'seminar' => 5,
            ],
        ]);
        $this->setUpDatabaseData('tx_rexample_domain_model_topic', [
            [
                'title' => 'Some topic A',
                'event' => 1,
            ],
            [
                'title' => 'Some topic B',
                'event' => 1,
            ],
            [
                'title' => 'Some topic C',
                'event' => 2,
            ],
        ]);
        $this->setUpDatabaseData('tx_rexample_domain_model_location', [
            [
                'title' => 'Location A',
            ],
            [
                'title' => 'Location B',
            ]
        ]);
        $this->setUpDatabaseData('tx_rexample_location_event_mm', [
            [
                'uid_local' => 1,
                'uid_foreign' => 1,
            ],
            [
                'uid_local' => 2,
                'uid_foreign' => 1,
            ]
        ]);

        /** @var RepositoryInterface $seminarRepository */
        $seminarRepository = $this->getObjectManager()->get(EventRepository::class);

        /**
         * @param string $className
         * @return FilterInterface|object
         */
        function getFilter(string $className): FilterInterface
        {
            return GeneralUtility::makeInstance(ObjectManager::class)->get($className);
        }

        /** @var FilterList $list */
        $list = $this->getObjectManager()->get(FilterList::class, [
            'title' => getFilter(AttributeFilter::class)->setConfiguration([
                'propertyName' => 'title'
            ]),
            'seminar' => getFilter(ManyToOneFilter::class)->setConfiguration([
                'propertyName' => 'seminar',
                'foreignTable' => 'tx_rexample_domain_model_seminar',
                'foreignLabel' => 'title'
            ]),
            'topic' => getFilter(OneToManyFilter::class)->setConfiguration([
                'propertyName' => 'topics',
                'foreignTable' => 'tx_rexample_domain_model_topic',
                'foreignField' => 'event',
                'foreignLabel' => 'title'
            ]),
            'location' => getFilter(ManyToManyFilter::class)->setConfiguration([
                'propertyName' => 'locations',
                'foreignTable' => 'tx_rexample_domain_model_location',
                'relationTable' => 'tx_rexample_location_event_mm',
                'relationTableLocalField' => 'uid_foreign',
                'relationTableForeignField' => 'uid_local',
                'foreignLabel' => 'title'
            ]),
        ]);

        $baseQuery = $seminarRepository->createQuery();
        $baseQuery->setQuerySettings((new Typo3QuerySettings())->setRespectStoragePage(false));
        $baseQuery->matching(
            $baseQuery->logicalOr([
                $baseQuery->greaterThan('uid', 0),
                $baseQuery->equals('seminar.title', 'Just to have a join'),
                $baseQuery->equals('topics.title', 'Just to have a join')
            ])
        );

        $list
            ->resetSettings()
            ->setBaseQuery($baseQuery)
            ->setFilters([]);

        $list
            ->resetSettings()
            ->setBaseQuery($baseQuery)
            ->setFilters([
                'title' => ['Event 1']
            ]);
        $result = $list->execute();
        self::assertEquals(1, count($result['items']));
        self::assertCount(7, $result['filterItems']['title']);
        self::assertEquals(2, $this->find($result['filterItems']['title'], function ($item) {
            return $item['identification'] === 'Event Last';
        })['count']);
        self::assertCount(4, $result['filterItems']['seminar']);

        $list
            ->resetSettings()
            ->setBaseQuery($baseQuery)
            ->setFilters([
                'title' => ['Event 2', 'Event 1'],
                'topic' => [1, 2, 3],
            ]);
        $result = $list->execute();
        self::assertEquals(2, count($result['items']));
        self::assertEquals(3, count($result['filterItems']['topic']));
        self::assertCount(1, array_filter($result['filterItems']['seminar'], function ($item) {
            return $item['count'] > 0;
        }));

    }

    /**
     * @param $array
     * @param $callback
     * @return mixed|null
     */
    protected function find($array, $callback)
    {
        foreach ($array as $element) {
            if ($callback($element)) {
                return $element;
            }
        }
        return null;
    }

}