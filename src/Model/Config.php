<?php
declare(strict_types=1);

namespace Team23\CleanupEav\Model;

use Team23\CleanupEav\Model\Facade\PathProcessorFacadeFactory;
use Team23\CleanupEav\Model\ResourceModel\Config as ConfigResource;

/**
 * @SuppressWarnings(PHPMD.LongVariableName)
 */
class Config
{
    /**
     * @var string[]
     */
    private array $excludedCorePaths = [
        'design/email/footer_template',
        'design/email/header_template',
        'design/email/logo_alt',
        'design/email/logo_height',
        'design/email/logo_width',
        'design/footer/absolute_footer',
        'design/footer/copyright',
        'design/footer/report_bugs',
        'design/head/default_description',
        'design/head/default_keywords',
        'design/head/default_title',
        'design/head/demonotice',
        'design/head/includes',
        'design/head/title_prefix',
        'design/head/title_suffix',
        'design/header/logo_alt',
        'design/header/logo_height',
        'design/header/logo_width',
        'design/header/translate_title',
        'design/header/welcome',
        'design/pagination/anchor_text_for_next',
        'design/pagination/anchor_text_for_previous',
        'design/pagination/pagination_frame',
        'design/pagination/pagination_frame_skip',
        'design/search_engine_robots/custom_instructions',
        'design/search_engine_robots/default_robots',
        'design/theme/theme_id',
        'design/watermark/image_imageOpacity',
        'design/watermark/image_position',
        'design/watermark/image_size',
        'design/watermark/small_image_imageOpacity',
        'design/watermark/small_image_position',
        'design/watermark/small_image_size',
        'design/watermark/thumbnail_imageOpacity',
        'design/watermark/thumbnail_position',
        'design/watermark/thumbnail_size',
    ];

    /**
     * Config constructor
     *
     * @param PathProcessorFacadeFactory $pathProcessorFacadeFactory
     * @param ConfigResource $configResource
     * @param array|string[] $excludedPaths
     */
    public function __construct(
        private readonly PathProcessorFacadeFactory $pathProcessorFacadeFactory,
        private readonly ConfigResource $configResource,
        private readonly array $excludedPaths = []
    ) {
    }

    /**
     * Cleanup configuration paths
     *
     * @param bool $isDryRun
     * @return array
     */
    public function cleanUpPaths(bool $isDryRun = true): array
    {
        $orphanedPaths = [];
        $excludedPaths = array_merge($this->excludedCorePaths, $this->excludedPaths);
        foreach ($this->configResource->getAllPaths() as $path) {
            $pathProcessor = $this->pathProcessorFacadeFactory->create();
            if (!$pathProcessor->process($path) && !in_array($path, $excludedPaths)) {
                $orphanedPaths[] = $path;
            }
        }

        if ($orphanedPaths !== [] && !$isDryRun) {
            $this->configResource->removeOrphanedPaths($orphanedPaths);
        }
        return $orphanedPaths;
    }

    /**
     * Remove scope values which are identical to global values.
     *
     * Cleanup entries with the same value as the global (scope_id = 0) value.
     *
     * @param bool $isDryRun
     * @return void
     */
    public function execute(bool $isDryRun = true): void
    {
        $this->configResource->removeScopeValues($isDryRun);
    }

    /**
     * Retrieve delete configuration rows
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->configResource->getRowCount();
    }

    /**
     * Retrieve deleted configuration entries
     *
     * @return array
     */
    public function getResults(): array
    {
        return $this->configResource->getRowResults();
    }
}
