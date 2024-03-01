<?php
declare(strict_types=1);

namespace Team23\CleanupEav\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class Media
 *
 * Provide database operations on product media/images.
 */
class Media
{
    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    /**
     * Media constructor
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * Retrieve product images from database
     *
     * Load all product images from database, filter out non-images.
     *
     * @return string[]
     */
    public function getImages(): array
    {
        $select = $this
            ->connection
            ->select()
            ->from(
                ['media_table' => $this->connection->getTableName('catalog_product_entity_media_gallery')],
                ['value',]
            );
        return $this->connection->fetchCol($select);
    }

    /**
     * Remove images from database
     *
     * Remove rows from database depending on values in image array, they must exactly
     * match (e.g. `/1/0/1090323-2.jpeg`). This operation could take a while, depending on
     * the affected rows to delete.
     *
     * @param string[] $images
     * @return void
     */
    public function deleteImages(array $images): void
    {
        $this
            ->connection
            ->delete(
                $this->connection->getTableName('catalog_product_entity_media_gallery'),
                ['value IN (?)' => $images]
            );
    }

    /**
     * Cleanup media gallery table
     *
     * Remove entries with no value in relation table (orphaned entries).
     *
     * @return void
     */
    public function deleteOrphanedGalleryEntitiesWithoutValues(): void
    {
        $valueTable = $this->connection->getTableName('catalog_product_entity_media_gallery_value');
        $valueIds = $this
            ->connection
            ->fetchCol(
                $this->connection
                    ->select()
                    ->from(
                        ['value_table' => $valueTable],
                        ['value_id',]
                    )
            );
        if ($valueIds !== []) {
            $this
                ->connection
                ->delete(
                    $this->connection->getTableName('catalog_product_entity_media_gallery'),
                    ['value_id NOT IN (?)' => $valueIds]
                );
        }
    }
}
