<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Console\Command;

use Magento\Framework\Archive;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Backup\Filesystem\Iterator\File as BackupFile;
use Staempfli\CommerceImport\Console\Output as ConsoleOutput;
use Staempfli\CommerceImport\Model\ReaderFactory;
use Staempfli\CommerceImport\Model\Reader;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DatabaseImportCommand
 * @package Staempfli\CommerceImport\Console\Command
 */
class DatabaseImportCommand extends AbstractCommand
{
    const OPTION_FILE = 'file';
    /**
     * @var File
     */
    private $file;
    /**
     * @var Archive
     */
    private $archive;
    /**
     * @var Reader
     */
    private $reader;
    /**
     * @var ReaderFactory
     */
    private $readerFactory;

    /**
     * DatabaseImportCommand constructor.
     * @param File $file
     * @param Archive $archive
     * @param ReaderFactory $readerFactory
     * @param ConsoleOutput $consoleOutput
     */
    public function __construct(
        File $file,
        Archive $archive,
        ReaderFactory $readerFactory,
        ConsoleOutput $consoleOutput
    ) {
        parent::__construct($consoleOutput);
        $this->file = $file;
        $this->archive = $archive;
        $this->readerFactory = $readerFactory;
    }

    protected function configure()
    {
        $this->setName('ms3:database:import')
            ->setDescription('Import SQL Dump into the import database')
            ->addOption(
                self::OPTION_FILE,
                null,
                InputOption::VALUE_REQUIRED,
                'Define file for import'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output) //@codingStandardsIgnoreLine
    {
        $file = $input->getOption(self::OPTION_FILE);
        if (!$file) {
            throw new \Exception('Please define file to import: --file=');
        }

        $filePath = $this->file->getRealPath($file);
        if (!$filePath
            || !$this->file->isReadable($filePath)
        ) {
            throw new \Exception(sprintf('Cannot read file: %s', $filePath));
        }

        // Check if the dump is compressed
        if ($this->archive->isArchive($filePath)) {
            $filePath = $this->archive->unpack($filePath);
        }

        $this->dropExistingTables();
        $this->importFromFile($filePath);
    }

    /**
     * @param $filePath
     * @codingStandardsIgnore
     */
    protected function importFromFile($filePath)
    {
        set_time_limit(0); //@codingStandardsIgnoreLine
        ignore_user_abort(true);
        $source = new BackupFile($filePath);
        foreach ($source as $statement) {
            $this->getReader()->getConnection()->query($statement);
        }
        $this->file->deleteFile($filePath);
    }

    protected function dropExistingTables()
    {
        $tables = $this->getReader()->showTables();
        foreach (array_keys($tables) as $table) {
            $this->getReader()->getConnection()->dropTable($table);
        }
    }

    /**
     * @return \Staempfli\CommerceImport\Model\Reader
     */
    private function getReader()
    {
        if (!$this->reader) {
            $this->reader = $this->readerFactory->create();
        }
        return $this->reader;
    }
}
