<?php
declare(strict_types=1);

namespace App\Command;

use App\Helper\BusinessDateTimeHelper;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\PackageBuilder\Console\Command\CommandNaming;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class WorkingDaysDeadlineController
 * @package App\Command
 */
class WorkingDaysDeadlineCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    protected function configure(): void
    {
        $this
            ->setName(CommandNaming::classToName(self::class))
            ->setDescription('WorkingDaysDeadline')
            ->setHelp($this->getCommandHelp())
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL,
                'Nombre représentant la limite de jours ouvrés admissibles'
            )
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'Date de départ à partir de laquelle compter',
                (new DateTime('now'))->format('Y-m-d')
            );
    }

    /**
     * This optional method is the first one executed for a command after configure()
     * and is useful to initialize properties based on the input arguments and options.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // SymfonyStyle is an optional feature that Symfony provides so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('fbdh-command');

        $limit =(int) $input->getArgument('limit');
        $date = new DateTime($input->getArgument('date'));

        $deadlineExceeded = (new BusinessDateTimeHelper())->deadlineExceeded($date, $limit);
        $this->io->success(
            sprintf(
                "Date {$date->format(DateTime::ATOM)} %sdépassée avec une contrainte de %d jours ouvrés",
                $deadlineExceeded ? '' : 'non ',
                $limit
            )
        );

        $event = $stopwatch->stop('fbdh-command');
        if ($output->isVerbose()) {
            $this->io->comment(sprintf('Elapsed time: %.2f ms / Consumed memory: %.2f MB', $event->getDuration(), $event->getMemory() / (1024 ** 2)));
        }

        return Command::SUCCESS;
    }

    /**
     * The command help is usually included in the configure() method, but when
     * it's too long, it's better to define a separate method to maintain the
     * code readability.
     */
    private function getCommandHelp(): string
    {
        return <<<'HELP'
The <info>%command.name%</info> command helps to compute an admissible delay considering french business days:
  <info>php %command.full_name%</info> <comment>limit date (format Y-m-d)</comment>
HELP;
    }
}
