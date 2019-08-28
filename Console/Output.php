<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Console;

use Staempfli\CommerceImport\Logger\CommerceImportLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use \Symfony\Component\Console\Output\ConsoleOutput;

class Output
{
    const MAX_LENGTH = 80;

    /**
     * @var array
     */
    protected $log = [];
    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutput
     */
    protected $output;
    /**
     * @var \Symfony\Component\Process\ProcessBuilder
     */
    protected $process;
    /**
     * @var \Symfony\Component\Console\Helper\ProgressBar
     */
    protected $progress;
    /**
     * @var CommerceImportLogger
     */
    protected $commerceImportLogger;

    public function __construct(
        CommerceImportLogger $commerceImportLogger
    ) {
        $this->commerceImportLogger = $commerceImportLogger;
    }

    /**
     * @param OutputInterface $output
     * @return ConsoleOutput
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @return ConsoleOutput
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param $message
     * @param bool $showStats
     */
    public function error($message, $showStats = false)
    {
        $this->getOutput()->writeln('');
        $this->message($message, 'error', $showStats);
        $this->commerceImportLogger->error($message);
    }

    /**
     * @param $message
     * @param bool $showStats
     */
    public function warning($message, $showStats = false)
    {
        $this->getOutput()->writeln('');
        $style = new OutputFormatterStyle('white', 'yellow', ['bold', 'blink']);
        $this->getOutput()->getFormatter()->setStyle('warning', $style);
        $this->message('WARNING: ' . $message, 'warning', $showStats);
        $this->commerceImportLogger->warning($message);
    }

    /**
     * @param $message
     * @param $background
     * @param string $foreground
     * @param bool $small
     */
    public function banner($message, $background, $foreground = 'white', $small = false)
    {
        $style = new OutputFormatterStyle($foreground, $background, ['bold']);
        $this->getOutput()->writeln('');
        $this->getOutput()->getFormatter()->setStyle('banner', $style);
        if (!$small) {
            $this->message($this->getCenteredText(' '), 'banner', false);
        }
        $this->message($this->getCenteredText($message), 'banner', false);
        if (!$small) {
            $this->message($this->getCenteredText(' '), 'banner', false);
        }
        $this->getOutput()->writeln('');
    }

    /**
     * @param $text
     * @param string $spacer
     * @param int $max
     * @return string
     */
    public function getCenteredText($text, $spacer = ' ', $max = self::MAX_LENGTH)
    {
        $placeholder = '';
        $numSpaces = round(($max - strlen($text)) / 2);
        for ($i=1; $i<=$numSpaces; $i++) {
            $placeholder .= $spacer;
        }

        return substr($placeholder . $text . $placeholder, 0, $max);
    }

    /**
     * @param $message
     */
    public function title($message)
    {
        $this->getOutput()->writeln('');
        $style = new OutputFormatterStyle('white', 'default', ['bold']);
        $this->getOutput()->getFormatter()->setStyle('title', $style);
        $this->message($message, 'title', true);
        $this->commerceImportLogger->info($message);
    }

    /**
     * @param $message
     * @param bool $showStats
     */
    public function info($message, $showStats = false)
    {
        $this->message($message, 'info', $showStats);
        $this->commerceImportLogger->info($message);
    }

    /**
     * @param $message
     * @param bool $showStats
     */
    public function comment($message, $showStats = false)
    {
        $this->message($message, 'comment', $showStats);
    }

    /**
     * @param $message
     * @param bool $showStats
     */
    public function plain($message, $showStats = false)
    {
        $this->message($message, null, $showStats);
    }

    /**
     * @param array $message
     * @param style|null $
     */
    public function section(array $message = [], $style = null)
    {
        if (is_array($message)) {
            $text = implode('', $message);
            $this->message($text, $style);
        }
    }

    public function table(array $header, array $rows)
    {
        $table = new Table($this->getOutput());
        $table->setHeaders($header);
        $table->setRows($rows);
        $table->render();
        $logMessage = "\n" . implode(' | ', $header);
        foreach ($rows as $rowData) {
            $logMessage .= "\n" . implode(' | ', $rowData);
        }
        $this->commerceImportLogger->info($logMessage);
        $this->commerceImportLogger->debug($logMessage);
    }

    /**
     * @param array $arguments
     * @return ProcessBuilder
     */
    public function process(array $arguments = [])
    {
        if (!$this->process) {
            $this->process = ProcessBuilder::create($arguments);
        }

        return $this->process;
    }

    /**
     * @param int $total
     * @return ProgressBar
     */
    public function progress(int $total = 0)
    {
        if (!$this->progress) {
            $this->progress = new ProgressBar($this->getOutput(), $total);
        }

        return $this->progress;
    }

    /**
     * @param int $total
     */
    public function startProgress(int $total)
    {
        $this->progress = null;
        $this->progress($total)->start();
    }

    /**
     * @param int $step
     */
    public function advanceProgress(int $step = 1)
    {
        $this->progress()->advance($step);
    }

    public function finishProgress()
    {
        $this->progress()->finish();
        $this->progress = null;
        $this->output->writeln('');
    }

    /**
     * @param $message
     * @param null $style
     * @param bool $showStats
     */
    public function message($message, $style = null, $showStats = false)
    {
        if ($showStats) {
            $this->getOutput()->writeln(
                sprintf(
                    '%s / memory: %s | peak: %s',
                    ($style) ? sprintf('<%s>%s</%s>', $style, $message, $style) : sprintf('%s', $message),
                    $this->byteFormat(memory_get_usage(false), 'MB', 2),
                    $this->byteFormat(memory_get_peak_usage(false), 'MB', 2)
                )
            );
        } else {
            if ($style) {
                $this->getOutput()->writeln(sprintf('<%s>%s</%s>', $style, $message, $style));
            } else {
                $this->getOutput()->writeln(sprintf('%s', $message));
            }
        }
        $this->commerceImportLogger->debug($message);
    }

    /**
     * @param $bytes
     * @param string $unit
     * @param int $decimals
     * @return string
     */
    public function byteFormat($bytes, $unit = 'MB', $decimals = 2)
    {
        $units = [
            'B' => 0,
            'KB' => 1,
            'MB' => 2,
            'GB' => 3,
            'TB' => 4,
            'PB' => 5,
            'EB' => 6,
            'ZB' => 7,
            'YB' => 8
        ];

        $value = 0;

        if ($bytes > 0) {
            if (!array_key_exists($unit, $units)) {
                $pow = floor(log($bytes)/log(1024));
                $unit = array_search($pow, $units);
            }
            $value = ($bytes/pow(1024, floor($units[$unit])));
        }

        if (!is_numeric($decimals) || $decimals < 0) {
            $decimals = 2;
        }

        return sprintf('%.' . $decimals . 'f '. $unit, $value);
    }
}
