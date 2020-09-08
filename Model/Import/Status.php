<?php
/**
 * Status
 *
 * @copyright Copyright (c) 2016 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Model\Import;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class Status
{
    /**
     * Import status constants
     */
    const IMPORT_STATUS_DIR = 'import/mS3';
    const IMPORT_STATUS_READY = 'import_ready';
    const IMPORT_STATUS_BUSY = 'import_busy';
    const IMPORT_STATUS_FAILED = 'import_failed';

    /**
     * @var Filesystem\Directory\Write
     */
    protected $pubDirectory;

    /**
     * Status constructor.
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->pubDirectory = $filesystem->getDirectoryWrite(DirectoryList::PUB);
    }

    /**
     * Get status file path
     *
     * @param $filename
     * @return string
     */
    protected function getRelativeFilePath($filename)
    {
        return self::IMPORT_STATUS_DIR . '/' . $filename;
    }

    /**
     * Reset all status files
     */
    protected function resetStatuses()
    {
        $this->pubDirectory->delete($this->getRelativeFilePath(self::IMPORT_STATUS_READY));
        $this->pubDirectory->delete($this->getRelativeFilePath(self::IMPORT_STATUS_BUSY));
        $this->pubDirectory->delete($this->getRelativeFilePath(self::IMPORT_STATUS_FAILED));
    }

    /**
     * Check whether the status is valid to start import
     *
     * @return bool
     */
    public function isStatusValidToStart()
    {
        if ($this->pubDirectory->isExist($this->getRelativeFilePath(self::IMPORT_STATUS_READY))) {
            return true;
        }
        return false;
    }

    /**
     * Update status when import starts
     */
    public function updateStatusStart()
    {
        $this->resetStatuses();
        $this->pubDirectory->touch($this->getRelativeFilePath(self::IMPORT_STATUS_BUSY));
    }

    /**
     * Update status when import fails
     */
    public function updateStatusFailed($errorMessage = null)
    {
        $this->resetStatuses();
        if (!$errorMessage) {
            $this->pubDirectory->touch($this->getRelativeFilePath(self::IMPORT_STATUS_FAILED));
        } else {
            $this->pubDirectory->writeFile($this->getRelativeFilePath(self::IMPORT_STATUS_FAILED), $errorMessage);
        }
    }

    /**
     * Update status when import finishes
     */
    public function updateStatusFinish()
    {
        $this->resetStatuses();
    }
}
