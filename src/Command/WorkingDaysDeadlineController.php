<?php declare(strict_types=1);

namespace App\Command;

use App\Helper\BusinessDateTimeHelper;
use DateTime;
use Exception;
use Lib\CommandController;

/**
 * Class WorkingDaysDeadlineController
 * @package App\Command
 */
class WorkingDaysDeadlineController extends CommandController
{
    /**
     * @param array $argv
     *
     * @return mixed|void
     */
    public function run(array $argv)
    {
        $limit = (int) $argv[2] ?? null;
        if (!$limit) {
            throw new Exception('Veuillez saisir un nombre représentant la limite de jours ouvrés admissibles');
        }
        $date = isset($argv[3]) ? new DateTime($argv[3]) : new DateTime();
        $deadlineExceeded = (new BusinessDateTimeHelper())->deadlineExceeded($date, $limit);
        $this->getApp()->getPrinter()->display(
            sprintf(
                "Date {$date->format(DateTime::ATOM)} %sdépassée avec une contrainte de %d jours ouvrés",
                $deadlineExceeded ? '' : 'non ',
                $limit
            )
        );

        return;
    }
}
