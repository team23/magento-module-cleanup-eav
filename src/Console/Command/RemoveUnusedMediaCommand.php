<?php
declare(strict_types=1);

namespace Team23\CleanupEav\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Team23\CleanupEav\Model\Media;

/**
 * Class RemoveUnusedMediaCommand
 *
 * This command removes orphaned product images from disk and database by creating a difference between disk and
 * database tables.
 */
class RemoveUnusedMediaCommand extends Command
{
    /**
     * RemoveUnusedMediaCommand constructor
     *
     * @param Media $mediaHandler
     * @param string|null $name
     */
    public function __construct(
        private readonly Media $mediaHandler,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('eav:cleanup:media');
        $this->setDescription('Remove unused product images.');
        $this->addOption('dry-run');
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
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

        $this->mediaHandler->reset();
        $this->mediaHandler->execute($isDryRun);
        $this->printResult(
            $output,
            $isDryRun,
            $this->mediaHandler->getFileCount(),
            $this->mediaHandler->getFileSize(),
            count($this->mediaHandler->getFilesToRemoveFromDb())
        );
        return Cli::RETURN_SUCCESS;
    }

    /**
     * Print results of operation to CLI
     *
     * Provide human-readable results to CLI depending if it's a dry run or not.
     *
     * @param OutputInterface $output
     * @param bool $isDryRun
     * @param int $fileCount
     * @param string $fileSize
     * @param int $rowCount
     */
    private function printResult(
        OutputInterface $output,
        bool $isDryRun,
        int $fileCount,
        string $fileSize,
        int $rowCount
    ): void {
        $actionName = !$isDryRun ? 'Removed' : 'Would remove';
        $fileMsg = sprintf(
            "<info>%s %d unused images on disk with a total of %s MB.</info>",
            $actionName,
            $fileCount,
            $fileSize
        );
        $dbMsg = sprintf(
            "<info>%s %d orphaned images in database.</info>",
            $actionName,
            $rowCount
        );
        $output->writeln($fileMsg);
        $output->writeln($dbMsg);
    }
}
