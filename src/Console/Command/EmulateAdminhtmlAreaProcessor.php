<?php
declare(strict_types=1);

namespace Team23\CleanupEav\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Config\ScopeInterface;

/**
 * Class EmulateAdminhtmlAreaProcessor
 *
 * Emulates adminhtml scope and area for command line tasks.
 */
class EmulateAdminhtmlAreaProcessor
{
    /**
     * EmulateAdminhtmlAreaProcessor constructor
     *
     * @param State $state
     * @param ScopeInterface $scope
     */
    public function __construct(
        private readonly State $state,
        private readonly ScopeInterface $scope
    ) {
    }

    /**
     * Emulates callback inside adminhtml area code adminhtml scope.
     *
     * @param callable $callback
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function process(callable $callback, array $params = []): mixed
    {
        $currentScope = $this->scope->getCurrentScope();
        try {
            return $this->state->emulateAreaCode(
                Area::AREA_ADMINHTML,
                function () use ($callback, $params) {
                    $this->scope->setCurrentScope(Area::AREA_ADMINHTML);
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                    return call_user_func_array($callback, $params);
                }
            );
        } finally {
            $this->scope->setCurrentScope($currentScope);
        }
    }
}
