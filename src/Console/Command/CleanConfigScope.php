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
 * Class CleanConfigScope
 *
 * Remove values from scopes if they are identical to global.
 */
class CleanConfigScope extends Command
{
    /**
     * CleanConfigScope constructor
     *
     * @param Config $configHandler
     * @param string|null $name
     */
    public function __construct(
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
        $this->setName('eav:cleanup:config-scope');
        $this->setDescription('Cleanup configuration scope if they are identical.');
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

        $this->configHandler->execute($isDryRun);
        foreach ($this->configHandler->getResults() as $row) {
            $output->writeln(
                "<info>{$row}</info>"
            );
        }

        $rowCount = $this->configHandler->getCount();
        $actionName = !$isDryRun ? 'Removed' : 'Would remove';
        $output->writeln(
            "<info>{$actionName} {$rowCount} scope entries in database which are identical to global values.</info>"
        );
        return Cli::RETURN_SUCCESS;
    }
}
