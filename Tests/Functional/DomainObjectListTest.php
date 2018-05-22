<?php

namespace TYPO3\CMS\Core\Tests\Functional;

use RozbehSharahi\Rest3\FilterList\DomainObjectList;
use RozbehSharahi\Rest3\FilterList\Filter\DomainObjectAttributeFilter;
use RozbehSharahi\Rest3\FilterList\Filter\DomainObjectHasOneFilter;
use RozbehSharahi\Rest3\Tests\Functional\FunctionalTestBase;
use RozbehSharahi\Rexample\Domain\Repository\EventRepository;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;

class DomainObjectListTest extends FunctionalTestBase
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

        /** @var RepositoryInterface $seminarRepository */
        $seminarRepository = $this->getObjectManager()->get(EventRepository::class);

        /** @var DomainObjectList $list */
        $list = $this->getObjectManager()->get(DomainObjectList::class, [
            'title' => $this->getObjectManager()->get(
                DomainObjectAttributeFilter::class,
                'title'
            ),
            'seminar' => $this->getObjectManager()->get(
                DomainObjectHasOneFilter::class,
                'seminar',
                'tx_rexample_domain_model_seminar',
                'title'
            ),
        ]);

        $baseQuery = $seminarRepository->createQuery();
        $baseQuery->setQuerySettings((new Typo3QuerySettings())->setRespectStoragePage(false));
        $baseQuery->matching(
            $baseQuery->logicalOr([
                $baseQuery->equals('seminar.title', 'Just to have a join'),
                $baseQuery->greaterThan('uid', 0)
            ])
        );

        $list
            ->resetSettings()
            ->setBaseQuery($baseQuery)
            ->setPage(1)
            ->setPageSize(50)
            ->setFilters([
                'title' => ['Event 1']
            ]);
        $result = $list->execute();
        self::assertEquals(1, count($result->getItems()));
        self::assertCount(7, $result->getFilterItems()['title']);
        self::assertEquals(2, $this->find($result->getFilterItems()['title'], function ($item) {
            return $item['identification'] === 'Event Last';
        })['count']);
        self::assertCount(4, $result->getFilterItems()['seminar']);

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