<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Console\Command;

use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Console\Cli;
use Staempfli\CommerceImport\Console\Output as ConsoleOutput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\DeploymentConfig\Writer;
use Staempfli\CommerceImport\Setup\ConfigOptionsList as CommerceImportSetupConfig;

/**
 * Class ConfigDatabaseCommand
 * @package Staempfli\CommerceImport\Console\Command
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) // https://phpmd.org/rules/index.html
 */
class DatabaseConfigCommand extends AbstractCommand
{
    const ARG_HOST = 'host';
    const ARG_DBNAME = 'dbname';
    const ARG_USERNAME = 'username';
    const ARG_PASSWORD = 'password';

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var array
     */
    protected $connections = [];

    /**
     * @var array
     */
    protected $resources = [];
    /**
     * @var Writer
     */
    private $writer;
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * DatabaseConfigCommand constructor.
     * @param ConsoleOutput $consoleOutput
     * @param Writer $writer
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        Writer $writer,
        DeploymentConfig $deploymentConfig,
        ConsoleOutput $consoleOutput
    ) {
        parent::__construct($consoleOutput);
        $this->writer = $writer;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('ms3:database:config')
            ->setDescription('Configure Import Database');

        $this->addOption(
            self::ARG_HOST,
            null,
            InputOption::VALUE_REQUIRED,
            'Hostname/IP'
        );

        $this->addOption(
            self::ARG_DBNAME,
            null,
            InputOption::VALUE_REQUIRED,
            'Database Name'
        );

        $this->addOption(
            self::ARG_USERNAME,
            null,
            InputOption::VALUE_REQUIRED,
            'Username'
        );

        $this->addOption(
            self::ARG_PASSWORD,
            null,
            InputOption::VALUE_REQUIRED,
            'Password'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->connections = $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS);
        $this->resources = $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_RESOURCE);

        try {
            $this->updateDatabaseConfiguration();
        } catch (\Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $this->getConsoleOutput()->error($message);
        }
    }

    /**
     * @param $argument
     * @return mixed|string
     * @throws \Exception
     */
    protected function getInput($argument)
    {
        if (!($result = $this->input->getOption($argument))) {
            /* @var $helper \Symfony\Component\Console\Helper\QuestionHelper */
            $helper = $this->getHelper('question');
            $question = new Question($this->getDefinition()->getOption($argument)->getDescription() . ': ');

            $result = $helper->ask($this->input, $this->output, $question);
            if (!$result) {
                throw new \Exception('Invalid input');
            }
        }

        return $result;
    }

    /**
     * @param string $message
     * @return bool
     */
    protected function askConfirmation($message = 'No message defined!')
    {
        /* @var $helper \Symfony\Component\Console\Helper\QuestionHelper */
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion($message . ' ', false);

        if (!$helper->ask($this->input, $this->output, $question) && $this->input->isInteractive()) {
            return false;
        }
        return true;
    }

    /**
     * @return int
     * @throws \Exception
     */
    protected function updateDatabaseConfiguration()
    {
        $this->getConsoleOutput()->message(
            '<comment>Warning, this will modify the configuration in</comment> <info>app/etc/env.php</info>'
        );
        $this->getConsoleOutput()->comment('Please make a backup before you continue!');

        if (!$this->askConfirmation('<info>Are you sure you want to continue?[y/N]</info>')) {
            return Cli::RETURN_FAILURE;
        }

        $this->getConsoleOutput()->info('ms3Commerce Database configuration');

        $host = $this->getInput(self::ARG_HOST);
        $dbName = $this->getInput(self::ARG_DBNAME);
        $username = $this->getInput(self::ARG_USERNAME);
        $password = $this->getInput(self::ARG_PASSWORD);

        $this->resources[CommerceImportSetupConfig::DB_CONNECTION_SETUP] = [
            'connection' => CommerceImportSetupConfig::DB_CONNECTION_NAME
        ];

        $this->connections[CommerceImportSetupConfig::DB_CONNECTION_NAME] = [
            'host' => $host,
            'dbname' => $dbName,
            'username' => $username,
            'password' => $password,
            'active' => 1
        ];

        if (!$this->askConfirmation(
            '<info>Are you sure you want save the changes in the configuration file?[y/N]</info>'
        )) {
            return Cli::RETURN_FAILURE;
        }
        try {
            $this->writer
                ->saveConfig(
                    [ConfigFilePool::APP_ENV  => [ConfigOptionsListConstants::CONFIG_PATH_RESOURCE => $this->resources]]
                );
            $this->writer
                ->saveConfig(
                    [ConfigFilePool::APP_ENV  =>
                        [ConfigOptionsListConstants::CONFIG_PATH_DB =>
                            ['connection' => $this->connections]
                        ]
                    ]
                );
        } catch (\Exception $e) {
            $this->getConsoleOutput()->error('Unable to update the configuration');
            return Cli::RETURN_FAILURE;
        }
        $this->getConsoleOutput()->info('Configuration update was successful');
        $this->getConsoleOutput()->info('Please clear all caches and the var/generation directory!');
        return Cli::RETURN_SUCCESS;
    }
}
