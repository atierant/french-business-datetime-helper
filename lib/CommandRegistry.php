<?php declare(strict_types=1);

namespace Lib;

use Exception;

/**
 * Class CommandRegistry
 * @package App
 */
class CommandRegistry
{
    protected $registry    = [];
    protected $controllers = [];

    /**
     * @param string            $name
     * @param CommandController $controller
     */
    public function registerController(string $name, CommandController $controller): void
    {
        $this->controllers = [$name => $controller];
    }

    /**
     * @param string   $name
     * @param callable $callable
     */
    public function registerCommand(string $name, callable $callable): void
    {
        $this->registry[$name] = $callable;
    }

    /**
     * @param string $command
     *
     * @return mixed|null
     */
    public function getController(string $command)
    {
        return isset($this->controllers[$command]) ? $this->controllers[$command] : null;
    }

    /**
     * @param string $command
     *
     * @return mixed|null
     */
    public function getCommand(string $command)
    {
        return isset($this->registry[$command]) ? $this->registry[$command] : null;
    }

    /**
     * @param string $name
     *
     * @return array|mixed|null
     * @throws Exception
     */
    public function getCallable(string $name)
    {
        $controller = $this->getController($name);
        if ($controller instanceof CommandController) {
            return [$controller, 'run'];
        }
        $command = $this->getCommand($name);
        if ($command === null) {
            throw new Exception("Command \"$name\" not found.");
        }

        return $command;
    }
}
