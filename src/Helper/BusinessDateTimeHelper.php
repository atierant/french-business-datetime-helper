<?php

declare(strict_types=1);

namespace App\Helper;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;

/**
 * Class DateTimeHelper
 */
class BusinessDateTimeHelper
{
    public const SATURDAY = 6;
    public const SUNDAY = 0;

    /**
     * Retourne touts les jours fériés d'une année donnée pour la France
     *
     * @param int|null    $year
     * @param string|null $format
     *
     * @return DateTime[]|int[]|string[]
     */
    public function getFrenchHolidays(?int $year = null, ?string $format = DateTimeInterface::class)
    {
        if ($year === null) {
            $year = intval(date('Y'));
        }

        // Récupération de la date de pâques de l'année donnée
        // Dimanche de Pâques
        $easterDate = $this->getEasterDateTime($year);
        // Jour du mois sans les zéros initiaux
        $easterDay = date('j', $easterDate->getTimestamp());
        // Mois sans les zéros initiaux
        $easterMonth = date('n', $easterDate->getTimestamp());
        // Année sur 4 chiffres
        $easterYear = date('Y', $easterDate->getTimestamp());

        $holidays = [
            // These days have a fixed date
            "Jour de l'An" => mktime(0, 0, 0, 1, 1, $year),
            "Fête du Travail" => mktime(0, 0, 0, 5, 1, $year),
            "Fête de la Victoire 45" => mktime(0, 0, 0, 5, 8, $year),
            "Fête Nationale" => mktime(0, 0, 0, 7, 14, $year),
            "Assomption" => mktime(0, 0, 0, 8, 15, $year),
            "Toussaint" => mktime(0, 0, 0, 11, 1, $year),
            "Armistice" => mktime(0, 0, 0, 11, 11, $year),
            "Noël" => mktime(0, 0, 0, 12, 25, $year),
            "Lundi de Pâques" => mktime(0, 0, 0, (int) $easterMonth, (int) $easterDay + 1, (int) $easterYear),
            "Jeudi de l'Ascension" => mktime(0, 0, 0, (int) $easterMonth, (int) $easterDay + 39, (int) $easterYear),
            "Pentecôte" => mktime(0, 0, 0, (int) $easterMonth, (int) $easterDay + 49, (int) $easterYear),
            "Lundi de Pentecôte" => mktime(0, 0, 0, (int) $easterMonth, (int) $easterDay + 50, (int) $easterYear),
        ];
        asort($holidays);

        $holidays = array_map(
            function ($holiday) use ($format) {
                switch ($format) {
                    case 'timestamp':
                        return $holiday;
                    case 'readable':
                        // Note : J'aurais préféré utiliser la constante depuis DateTimeInterface,
                        // mais elle n'y a été introduite qu'en php 7.2
                        $date = DateTime::createFromFormat('U', (string) $holiday, new DateTimeZone('UTC'));

                        return $date instanceof DateTimeInterface ? $date->format(DateTime::COOKIE) : $date;
                    case DateTimeInterface::class:
                    default:
                        return DateTime::createFromFormat('U', (string) $holiday, new DateTimeZone('UTC'));
                }
            },
            $holidays
        );

        return $format == 'timestamp' ? array_values($holidays) : $holidays;
    }

    /**
     * Détermine si le jour fourni est un jour non ouvré
     *
     * @param DateTime $date
     *
     * @return bool
     */
    public function isHoliday(DateTime $date)
    {
        // Le 9 à minuit en France
        // Récupération du timestamp pour une date donnée à minuit
        /** @var DateTime $date */
        $date = (clone $date);
        $timestamp = $date->getTimestamp();

        // Si le jour est un Dimanche (0) ou un samedi (6), ou un jour férié
        return in_array($timestamp, $this->getFrenchHolidays((int) date("Y", $timestamp), 'timestamp'))
            || in_array(date('w', $timestamp), [self::SATURDAY, self::SUNDAY]);
    }

    /**
     * Retourne le prochain jour ouvré pour une date donnée
     *
     * @param DateTimeInterface $date Date initiale
     *
     * @return DateTime
     */
    public function getNextBusinessDay(DateTimeInterface $date)
    {
        // Nombre de jours à ajouter : va être incrémentée chaque fois que l'on tombera
        // sur un jour non ouvré ou férié
        do {
            $date = $date->add(new DateInterval('P1D'));
        } while ($this->isHoliday($date));

        return $date;
    }

    /**
     * Retourne le jour ouvré limite pour une date donnée
     *
     * @param DateTime $date             Date initiale
     * @param int      $workingDaysLimit Limite de jours ouvrés
     *
     * @return DateTime
     */
    public function getDeadline(DateTime $date, int $workingDaysLimit)
    {
        if (0 > $workingDaysLimit) {
            throw new InvalidArgumentException(
                "La valeur fournie du nb de jours ouvrés pour la modification n'est pas acceptable"
            );
        }

//        dump(sprintf('On part de cette date qu'on convertit en UTC : %s', $date->format(DateTime::RFC7231)));
        $date = (clone $date)->modify('midnight');
//        dump(sprintf('On considère ce jour à minuit : %s', $date->format(DateTime::ATOM)));


        /**
         * @var DateTime|DateTimeImmutable $date
         * Attention Pour la calcul des X jours ouvrés, la date courante est exclue.
         * On commence par le jour ouvré suivant à minuit
         */
        $nextDay = $this->getNextBusinessDay($date);

        // Traitement du cas où l'opérateur n'aurait pas le droit de modifier
        if (0 === $workingDaysLimit) {
            return $nextDay;
        }

        $workingDaysLimit--;
//        dump(sprintf('+1 jour ouvré suivant : %s, jours ouvrés restant : %d', $nextDay->format(DateTime::RFC7231), $workingDaysLimit));
        unset($date);

        // Si la limite est > 1, chercher le jour ouvré suivant
        while ($workingDaysLimit > 0) {
            $nextDay = $this->getNextBusinessDay($nextDay);
            $workingDaysLimit--;
//            dump(sprintf('+1 jour ouvré suivant : %s, jours ouvrés restant : %d', $nextDay->format(DateTime::RFC7231), $workingDaysLimit));
        };

//        dump(sprintf('Fin de boucle, nextDay vaut : %s', $nextDay->format(DateTime::RFC7231)));

        // Finit ensuite par prendre le lendemain du dernier jour trouvé
        $nextDay = $nextDay->add(new DateInterval('P1D'));
//        dump(sprintf('Le lendemain minuit du nextDay vaut alors : %s', $nextDay->format(DateTime::RFC7231)));

        // Sortir au lendemain du dernier jour à minuit, que ça soit Samedi, Dimanche ou férié
        return $nextDay->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * Vérifie si on est encore dans les délais de jours ouvrés pour une date donnée
     *
     * @param DateTime $date             Date de départ
     * @param int      $workingDaysLimit Limite de jours ouvrés
     *
     * @return bool
     * @throws Exception
     */
    public function deadlineExceeded(DateTime $date, int $workingDaysLimit): bool
    {
        // $this->getWorkingDaysDeadline retourne la date limite pour un nombre de jours ouvrés admissibles
        $deadline = $this->getDeadline($date, $workingDaysLimit);

        // On est dans les temps si la deadline est postérieure à la date du jour à minuit ('today')
        $today = new DateTimeImmutable('now');

        return $today > $deadline;
    }

    /**
     * La fonction easter_date() repose sur les fonctions de la bibliothèque C time du système,
     * plutôt que sur les fonctions date et time internes de PHP.
     * Aussi, la fonction easter_date() utilise la variable d'environnement TZ pour déterminer
     * le fuseau horaire à utiliser, plutôt que le fuseau horaire par défaut de PHP,
     * ce qui peut conduire à un comportement non désiré lors de l'utilisation de cette fonction
     * avec d'autres fonctions date de PHP.
     *
     * Comme contournement, vous pouvez utiliser la fonction easter_days()
     * avec les classes DateTime et DateInterval pour calculer le jour de Pâques
     * dans votre fuseau horaire PHP
     * @see https://www.php.net/manual/fr/function.easter-date.php
     *
     * @param int $year
     *
     * @return DateTime
     * @throws Exception
     */
    public function getEasterDateTime(int $year)
    {
        $base = new DateTime("$year-03-21");
        $days = easter_days($year);

        return $base->add(new DateInterval("P{$days}D"));
    }

    /**
     * Permet de transformer un datetime en chaine lisible humainement au format par défaut
     * "jourEnLettres jour moisEnLettres annee".
     * Pour installer la locale fr sur son système :
     * apt-get install language-pack-fr language-pack-fr-base manpages
     *
     * @see https://openclassrooms.com/forum/sujet/avoir-la-date-en-francais-grace-a-un-datetime-29453
     *
     * @param DateTimeInterface $date
     * @param string|null       $locale
     * @param string|null       $format
     *
     * @return string
     */
    public function formatAsFrenchDate(
        DateTimeInterface $date,
        ?string $locale = 'fr_FR.UTF-8',
        ?string $format = "%A %d %B %Y"
    ): string {
        // --- La setlocale() fonctionnne pour strftime mais pas pour DateTime->format()
        $return = setlocale(LC_TIME, $locale);

        return ucwords(strftime($format, $date->getTimestamp()));
    }
}
