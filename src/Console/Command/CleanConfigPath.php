<?php
declare(strict_types=1);

namespace Team23\CleanupEav\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Team23\CleanupEav\Model\Config;

/**
 * Class CleanConfigPath
 *
 * Clean configuration paths, compare paths from `system.xml` files with paths in `core_config_data`.
 * Remove orphaned paths in database.
 *
 * @SuppressWarnings(PHPMD.LongVariableName)
 */
class CleanConfigPath extends Command
{
    /**
     * @var bool
     */
    private bool $isDryRun = true;

    /**
     * CleanConfigScope constructor
     *
     * @param EmulateAdminhtmlAreaProcessor $emulateAdminhtmlAreaProcessor
     * @param Config $configHandler
     * @param string|null $name
     */
    public function __construct(
        private readonly EmulateAdminhtmlAreaProcessor $emulateAdminhtmlAreaProcessor,
        private readonly Config $configHandler,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('eav:cleanup:config-path');
        $this->setDescription('Cleanup configuration path if they are not in system anymore.');
        $this->addOption('dry-run');
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isDryRun = $input->getOption('dry-run');
        if (!$isDryRun && $input->isInteractive()) {
            $output->writeln(
                '<comment>WARNING: This is not a dry run. If you want to do that, add --dry-run.</comment>'
            );

            $question = new ConfirmationQuestion(
                '<comment>Are you sure you want to continue? [No]</comment>',
                false
            );

            if (!$this->getHelper('question')->ask($input, $output, $question)) {
                $output->writeln(
                    '<error>Aborted.</error>'
                );
                return Cli::RETURN_FAILURE;
            }
        }

        if (!is_bool($isDryRun)) {
            $isDryRun = (bool)$isDryRun;
        }

        try {
            $this->isDryRun = $isDryRun;
            $paths = $this->emulateAdminhtmlAreaProcessor->process(function () {
                return $this->configHandler->cleanUpPaths($this->isDryRun);
            });

            foreach ($paths as $path) {
                $output->writeln(
                    "<info>{$path} is orphaned.</info>"
                );
            }

            $rowCount = count($paths);
            $actionName = !$isDryRun ? 'Removed' : 'Would remove';
            $output->writeln(
                "<info>{$actionName} {$rowCount} orphaned paths in configuration table.</info>"
            );
            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Cli::RETURN_FAILURE;
        }
    }
}
