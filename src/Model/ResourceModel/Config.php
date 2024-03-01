<?php
declare(strict_types=1);

namespace Team23\CleanupEav\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Store\Model\Store;

class Config
{
    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    /**
     * @var int
     */
    private int $rowCount = 0;

    /**
     * @var array
     */
    private array $rowResults = [];

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
     * Retrieve all deleted rows
     *
     * @return int
     */
    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    /**
     * Retrieve all deleted configuration paths
     *
     * @return array
     */
    public function getRowResults(): array
    {
        return $this->rowResults;
    }

    /**
     * Remove scope values which are identical to global values.
     *
     * Cleanup entries with the same value as the global (scope_id = 0) value.
     *
     * @param bool $isDryRun
     * @return void
     */
    public function removeScopeValues(bool $isDryRun = true): void
    {
        $this->rowCount = 0;
        $this->rowResults = [];
        $select = $this
            ->connection
            ->select()
            ->distinct(true)
            ->from(
                ['config_table' => $this->connection->getTableName('core_config_data')],
                ['path','value',]
            )
            ->where('config_table.scope_id = (?)', Store::DEFAULT_STORE_ID);

        foreach ($this->connection->fetchAll($select) as $row) {
            $count = (int)$this->connection->fetchOne(
                $this
                    ->connection
                    ->select()
                    ->from(
                        ['config_table' => $this->connection->getTableName('core_config_data')],
                        'COUNT(*)'
                    )
                    ->where('config_table.path = ?', $row['path'])
                    ->where('BINARY config_table.value = ?', $row['value'])
            );

            if ($count > 1) {
                $this->rowResults[] = sprintf(
                    "Config path %s with value %s has %d entries; deleting non-default values",
                    $row['path'],
                    $row['value'],
                    (int)$count
                );

                if (!$isDryRun) {
                    $this
                        ->connection
                        ->delete(
                            $this->connection->getTableName('core_config_data'),
                            [
                                'path = ?' => $row['path'],
                                'BINARY value = ?' => $row['value'],
                                'scope_id != ?' => Store::DEFAULT_STORE_ID
                            ]
                        );
                }
                $this->rowCount += ($count - 1);
            }
        }
    }

    /**
     * Retrieve all paths from database
     *
     * @return array
     */
    public function getAllPaths(): array
    {
        $select = $this
            ->connection
            ->select()
            ->distinct(true)
            ->from(
                ['config_table' => $this->connection->getTableName('core_config_data')],
                ['path',]
            );
        return $this->connection->fetchCol($select);
    }

    /**
     * Delete orphaned paths from database
     *
     * @param string[] $orphanedPaths
     */
    public function removeOrphanedPaths(array $orphanedPaths): void
    {
        $this
            ->connection
            ->delete(
                $this->connection->getTableName('core_config_data'),
                ['path IN (?)' => $orphanedPaths]
            );
    }
}
