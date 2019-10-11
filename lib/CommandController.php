<?php declare(strict_types=1);

namespace Lib;

/**
 * Class CommandController
 * @package App
 */
abstract class CommandController
{
    /** @var App */
    protected $app;

    /**
     * CommandController constructor.
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * @return App
     */
    protected function getApp(): App
    {
        return $this->app;
    }

    /**
     * @param array $argv
     *
     * @return mixed
     */
    abstract public function run(array $argv);
}
