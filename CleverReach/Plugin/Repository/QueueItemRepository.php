<?php

namespace CleverReach\Plugin\Repository;

use CleverReach\Plugin\ResourceModel\QueueItemEntity;
use CleverReach\Plugin\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use CleverReach\Plugin\IntegrationCore\Infrastructure\ORM\Interfaces\QueueItemRepository as QueueItemRepositoryInterface;
use CleverReach\Plugin\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use CleverReach\Plugin\IntegrationCore\Infrastructure\TaskExecution\Interfaces\Priority;
use CleverReach\Plugin\IntegrationCore\Infrastructure\TaskExecution\QueueItem;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class QueueItemRepository
 *
 * @property QueueItemEntity $resourceEntity
 */
class QueueItemRepository extends BaseRepository implements QueueItemRepositoryInterface
{
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;

    /**
     * Finds list of earliest queued queue items per queue. Following list of criteria for searching must be satisfied:
     *      - Queue must be without already running queue items
     *      - For one queue only one (oldest queued) item should be returned
     *
     * @param int $priority Queue item priority.
     * @param int $limit Result set limit. By default max 10 earliest queue items will be returned
     *
     * @return QueueItem[] Found queue item list
     *
     * @throws QueryFilterInvalidParamException
     */
    public function findOldestQueuedItems($priority, $limit = 10): array
    {
        if ($priority !== Priority::NORMAL) {
            return [];
        }

        $queuedItems = [];
        $entity = new $this->entityClass;

        try {
            $records = $this->resourceEntity->findOldestQueuedItems($entity, $limit);
            /** @var QueueItem[] $queuedItems */
            $queuedItems = $this->deserializeEntities($records);
        } catch (LocalizedException $e) {
            // In case of exception return empty result set.
        }

        return $queuedItems;
    }

    /**
     * Creates or updates given queue item. If queue item id is not set, new queue item will be created otherwise
     * update will be performed.
     *
     * @param QueueItem $queueItem Item to save
     * @param array $additionalWhere List of key/value pairs that must be satisfied upon saving queue item. Key is
     *  queue item property and value is condition value for that property. Example for MySql storage:
     *  $storage->save($queueItem, array('status' => 'queued')) should produce query
     *  UPDATE queue_storage_table SET .... WHERE .... AND status => 'queued'
     *
     * @return int Id of saved queue item
     *
     * @throws QueueItemSaveException if queue item could not be saved
     */
    public function saveWithCondition(QueueItem $queueItem, array $additionalWhere = []): int
    {
        return $this->resourceEntity->saveWithCondition($queueItem, $additionalWhere);
    }

    /**
     * Returns resource entity.
     *
     * @return string Resource entity class name.
     */
    protected function getResourceEntity(): string
    {
        return QueueItemEntity::class;
    }
}
