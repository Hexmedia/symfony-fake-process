<?php
/**
 * @author    Krystian Kuczek <krystian@hexmedia.pl>
 * @copyright 2013-2016 Hexmedia.pl
 * @license   @see LICENSE
 */

namespace Hexmedia\Symfony\FakeProcess;

use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process as BaseProcess;

/**
 * Class FakeProcess
 *
 * @package Hexmedia\Symfony\FakeProcess
 */
class FakeProcess extends BaseProcess
{
    private $commandline;

    private $callback;

    private $command;

    private $response;

    /**
     * @var array
     */
    private $commands;

    /**
     * @var array
     */
    private $commandsRuns = array();

    /**
     * Process constructor.
     *
     * @param string         $commandline
     * @param null|string    $cwd
     * @param array|null     $env
     * @param null|string    $input
     * @param float|int|null $timeout
     * @param array          $options
     */
    public function __construct($commandline, $cwd = null, array $env = null, $input = null, $timeout = 60, array $options = array())
    {
        $this->commandline = $commandline;
        $this->commands = array();

        parent::__construct($commandline, $cwd, $env, $input, $timeout, $options);
    }

    /**
     * @param string   $command
     * @param callable $callback
     * @param int      $exitCode
     */
    public function addCommand($command, $callback, $exitCode)
    {
        $this->commands[] = array('command' => $command, 'callback' => $callback, 'exitCode' => $exitCode);
    }

    /**
     * @param array $commands
     *
     * @return $this
     */
    public function setCommands(array $commands)
    {
        $this->commands = $commands;

        return $this;
    }

    /**
     * @param string $commandName
     *
     * @return callable
     */
    public function getCommand($commandName)
    {
        $command = '';
        $possible = array();

        foreach ($this->commands as $values) {
            $command = $values['command'];

            if (preg_match("#" . $command . "#", $commandName)) {
                $possible[] = $values;
            }
        }

        if (sizeof($possible) == 1) {
            return $possible[0];
        }

        if (sizeof($possible)) {
            $index = 0;
            if (isset($this->commandsRuns[$command])) {
                if (sizeof($possible) <= $this->commandsRuns[$command]) {
                    $this->commandsRuns[$command] = 0;
                }

                $index = $this->commandsRuns[$command];
            }

            return $possible[$index];
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function start(callable $callback = null)
    {
        if ($this->isRunning()) {
            throw new RuntimeException('Process is already running');
        }

        $this->callback = $this->buildCallback($callback);
        $commandline = $this->commandline;

        $this->command = $this->getCommand($commandline);

        if (!isset($this->commandsRuns[$this->command['command']])) {
            $this->commandsRuns[$this->command['command']] = 0;
        }

        $this->commandsRuns[$this->command['command']]++;

        if (false === $this->command) {
            throw new \Exception(sprintf('No command "%s"!', $commandline));
        }

        $this->updateStatus(false);
        $this->checkTimeout();
    }

    /**
     * {@inheritdoc}
     */
    public function wait($callback = null)
    {
        $commandCallback = $this->command['callback'];
        $exitCode = $this->command['exitCode'];

        $this->response = $commandCallback($this->commandline);

        return (int) $exitCode;
    }

    /**
     * {@inheritdoc}
     */
    protected function updateStatus($blocking)
    {
        //Do nothing:)
    }

    /**
     * @return string
     */
    public function getErrorOutput()
    {
        return '';
    }

    /**
     * @return int
     */
    public function getExitCode()
    {
        return $this->command['exitCode'];
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->response;
    }
}
