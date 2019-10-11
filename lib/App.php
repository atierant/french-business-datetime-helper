<?php declare(strict_types=1);

namespace Lib;

/**
 * Class App
 */
class App
{
    /** @var CliPrinter */
    protected $printer;
    /** @var CommandRegistry */
    protected $registry;

    /**
     * App constructor.
     */
    public function __construct()
    {
        $this->printer = new CliPrinter();
        $this->registry = new CommandRegistry();
    }

    /**
     * @return CliPrinter
     */
    public function getPrinter()
    {
        return $this->printer;
    }

    /**
     * @param string            $name
     * @param CommandController $controller
     */
    public function registerController($name, CommandController $controller): void
    {
        $this->registry->registerController($name, $controller);
    }

    /**
     * @param string   $name
     * @param callable $callable
     */
    public function registerCommand(string $name, callable $callable): void
    {
        $this->registry->registerCommand($name, $callable);
    }

    /**
     * @param array  $argv
     * @param string $default
     */
    public function runCommand(array $argv = [], $default = 'help'): void
    {
        $name = $default;
        if (isset($argv[1])) {
            $name = $argv[1];
        }
        try {
            call_user_func($this->registry->getCallable($name), $argv);
        } catch (\Throwable $e) {
            $this->getPrinter()->display("ERROR: ".$e->getMessage());
        }
    }
}
