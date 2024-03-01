<?php
declare(strict_types=1);

namespace Team23\CleanupEav\Model\Facade;

use Magento\Config\Model\Config\PathValidator;
use Magento\Framework\Exception\ValidatorException;

/**
 * Class PathProcessorFacade
 *
 * Facade for use as a factory instance. This will allow us to use the pathValidator within an adminhtml scope.
 */
class PathProcessorFacade
{
    /**
     * PathProcessorFacade constructor
     *
     * @param PathValidator $pathValidator
     */
    public function __construct(
        private readonly PathValidator $pathValidator
    ) {
    }

    /**
     * Processed path validation
     *
     * @param string $path
     * @return bool
     */
    public function process(string $path): bool
    {
        try {
            $this->pathValidator->validate($path);
        } catch (ValidatorException) {
            return false;
        }
        return true;
    }
}
