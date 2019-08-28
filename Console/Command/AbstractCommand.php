<?php
/**
 * AbstractCommand
 *
 * @copyright Copyright (c) 2016 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Console\Command;

use Staempfli\CommerceImport\Console\Output as ConsoleOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Console\Cli;

class AbstractCommand extends Command
{
    const OPTION_OUTPUT_ONLY_ERRORS = 'output-only-errors';
    /**
     * @var InputInterface
     */
    protected $input;
    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var ConsoleOutput
     */
    private $consoleOutput;

    /**
     * AbstractCommand constructor.
     * @param ConsoleOutput $consoleOutput
     * @param null $name
     */
    public function __construct(
        ConsoleOutput $consoleOutput,
        $name = null
    ) {
        parent::__construct($name);
        $this->setDefaultOptions();
        $this->consoleOutput = $consoleOutput;
    }

    /**
     * Set default options available for all mS3 commands
     */
    protected function setDefaultOptions()
    {
        $this->addOption(
            self::OPTION_OUTPUT_ONLY_ERRORS,
            null,
            InputOption::VALUE_NONE,
            'Display only Errors output'
        );
    }

    /**
     * Set Console output configuration depending on input options
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function setConsoleOutputConfiguration(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption(self::OPTION_OUTPUT_ONLY_ERRORS)) {
            $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        }
    }

    /**
     * Set configuration and logging level according to options
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->setConsoleOutputConfiguration($input, $output);
        parent::initialize($input, $output);
        $this->input = $input;
        $this->output = $output;
        $this->getConsoleOutput()->setOutput($output);
    }

    /**
     * Enclose run within try catch to display errors when option only-errors active
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Throwable
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        try {
            return parent::run($input, $output);
        } catch (\Throwable $e) {
            if ($e instanceof \RuntimeException) {
                throw $e;
            }
            $this->finishWithError($input, $output, $e);
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param \Throwable $throwable
     * @SuppressWarnings(PHPMD.ExitExpression) // https://phpmd.org/rules/index.html
     */
    protected function finishWithError(InputInterface $input, OutputInterface $output, \Throwable $throwable)
    {
        $this->updateVerbosityForError($input, $output);
        $error = $throwable->getMessage() . ' in ' . $throwable->getFile() . ':' . $throwable->getLine();
        $trace = $throwable->getTraceAsString();
        $this->getConsoleOutput()->error($error . PHP_EOL . $trace);
        exit(Cli::RETURN_FAILURE); //@codingStandardsIgnoreLine
    }

    protected function updateVerbosityForError(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption(self::OPTION_OUTPUT_ONLY_ERRORS)) {
            $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        }
    }

    /**
     * @return ConsoleOutput
     */
    protected function getConsoleOutput()
    {
        return $this->consoleOutput;
    }
}
