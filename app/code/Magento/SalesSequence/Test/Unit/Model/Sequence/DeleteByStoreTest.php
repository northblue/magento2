<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesSequence\Test\Unit\Model\Sequence;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SalesSequence\Model\Meta;
use Magento\SalesSequence\Model\MetaFactory;
use Magento\SalesSequence\Model\ResourceModel\Meta as ResourceMeta;
use Magento\SalesSequence\Model\ResourceModel\Meta\Ids as ResourceMetaIds;
use Magento\SalesSequence\Model\ResourceModel\Profile\Ids as ResourceProfileIds;
use Magento\SalesSequence\Model\Sequence\DeleteByStore;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class DeleteByStoreTest
 */
class DeleteByStoreTest extends TestCase
{
    /**
     * @var DeleteByStore
     */
    private $deleteByStore;

    /**
     * @var ResourceMeta | MockObject
     */
    private $resourceSequenceMeta;

    /**
     * @var ResourceMetaIds | MockObject
     */
    private $resourceSequenceMetaIds;

    /**
     * @var ResourceProfileIds | MockObject
     */
    private $resourceSequenceProfileIds;

    /**
     * @var Meta | MockObject
     */
    private $meta;

    /**
     * @var MetaFactory | MockObject
     */
    private $metaFactory;

    /**
     * @var AdapterInterface | MockObject
     */
    private $connectionMock;

    /**
     * @var ResourceConnection | MockObject
     */
    private $resourceMock;

    /**
     * @var StoreInterface | MockObject
     */
    private $store;

    protected function setUp()
    {
        $this->connectionMock = $this->getMockForAbstractClass(
            AdapterInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['delete']
        );
        $this->resourceSequenceMeta = $this->createPartialMock(
            ResourceMeta::class,
            ['load', 'delete']
        );
        $this->resourceSequenceMetaIds = $this->createPartialMock(
            ResourceMetaIds::class,
            ['getByStoreId']
        );
        $this->resourceSequenceProfileIds = $this->createPartialMock(
            ResourceProfileIds::class,
            ['getByMetadataIds']
        );
        $this->meta = $this->createPartialMock(
            Meta::class,
            ['getSequenceTable']
        );
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->metaFactory = $this->createPartialMock(MetaFactory::class, ['create']);
        $this->metaFactory->expects($this->any())->method('create')->willReturn($this->meta);
        $this->store = $this->getMockForAbstractClass(
            StoreInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getId']
        );

        $helper = new ObjectManager($this);
        $this->deleteByStore = $helper->getObject(
            DeleteByStore::class,
            [
                'resourceMetadataIds' => $this->resourceSequenceMetaIds,
                'resourceMetadata' => $this->resourceSequenceMeta,
                'resourceProfileIds' => $this->resourceSequenceProfileIds,
                'metaFactory' => $this->metaFactory,
                'appResource' => $this->resourceMock,
            ]
        );
    }

    public function testExecute()
    {
        $storeId = 1;
        $metadataIds = [1, 2];
        $profileIds = [10, 11];
        $tableName = 'sales_sequence_profile';
        $this->store->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);
        $this->resourceSequenceMetaIds->expects($this->once())
            ->method('getByStoreId')
            ->with($storeId)
            ->willReturn($metadataIds);
        $this->resourceSequenceProfileIds->expects($this->once())
            ->method('getByMetadataIds')
            ->with($metadataIds)
            ->willReturn($profileIds);
        $this->resourceMock->expects($this->once())
            ->method('getTableName')
            ->with($tableName)
            ->willReturn($tableName);
        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->once())
            ->method('delete')
            ->with($tableName, ['profile_id IN (?)' => $profileIds])
            ->willReturn(2);
        $this->resourceSequenceMeta->expects($this->any())
            ->method('load')
            ->willReturn($this->meta);
        $this->connectionMock->expects($this->any())
            ->method('dropTable')
            ->willReturn(true);
        $this->resourceSequenceMeta->expects($this->any())
            ->method('delete')
            ->willReturn($this->resourceSequenceMeta);
        $this->deleteByStore->execute($this->store);
    }
}
