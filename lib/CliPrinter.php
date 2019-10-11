<?php declare(strict_types=1);

namespace Lib;

/**
 * Class CliPrinter
 * @package App
 */
class CliPrinter
{
    /**
     * @param string $message
     */
    public function out(string $message): void
    {
        echo $message;
    }

    /**
     * Write a new line
     */
    public function newline(): void
    {
        $this->out("\n");
    }

    /**
     * @param string $message
     */
    public function display(string $message): void
    {
        $this->newline();
        $this->out($message);
        $this->newline();
        $this->newline();
    }
}
