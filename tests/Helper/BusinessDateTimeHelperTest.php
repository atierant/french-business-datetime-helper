<?php declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\BusinessDateTimeHelper;
use DateInterval;
use DateTime;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Class DateTimeHelperTest
 */
class BusinessDateTimeHelperTest extends TestCase
{
    /* @var BusinessDateTimeHelper */
    private $helper;

    /**
     * Initialisation du helper
     */
    public function setUp(): void
    {
        $this->helper = new BusinessDateTimeHelper();
        // Set the default timezone to use.
        date_default_timezone_set('UTC');
    }

    /**
     * Retourne un lot de dates pertinentes (jours normaux, fériés, week-ends)
     *
     * @return array
     * @throws Exception
     */
    public function holidaysProvider()
    {
        $date = function ($string) {
            return new DateTime($string);
        };

        // Inutile de tester tous les samedis et dimanche de l'histoire
        // de la même manière, tester tous les samedis et dimanches d'une année
        // ne prouve pas que les tests passeront pour les années suivantes.
        return [
            'Un jour quelconque en 2019' => [$date('2019-09-09'), false],  // test témoin, le 09-09-2019 n'est pas férié
            'Un jour quelconque de Septembre' => [$date('first monday of september'), false],  // test témoin
            'Un jour quelconque de Février' => [$date('first tuesday of february'), false],  // test témoin
            'Un jour quelconque de Mars' => [$date('first thursday of march'), false],  // test témoin

            'Samedi dernier' => [$date('next saturday'), true],
            'Samedi prochain' => [$date('last saturday'), true],
            'Dimanche dernier' => [$date('next saturday'), true],
            'Dimanche prochain' => [$date('last saturday'), true],

            'Jour de l\'an' => [$date('2019-01-01'), true],
            'Fête du Travail' => [$date('2019-05-01'), true],
            'Fête de la Victoire 45' => [$date('2019-05-08'), true],
            'Fête Nationale' => [$date('2019-07-14'), true],
            'Assomption' => [$date('2019-08-15'), true],
            'Toussaint' => [$date('2019-11-1'), true],
            'Armistice' => [$date('2019-11-11'), true],
            'Noël' => [$date('2019-12-25'), true],

            'Pâques' => [$date('2019-04-21'), true],
            'Lundi de Pâques' => [$date('2019-04-22'), true],
            'Jeudi de l\'Ascension' => [$date('2019-05-30'), true],
            'Pentecôte' => [$date('2019-06-09'), true],
            'Lundi de Pentecôte' => [$date('2019-06-10'), true],
        ];
    }

    /**
     * @dataProvider holidaysProvider
     *
     * @param DateTime $day
     * @param boolean  $isHoliday Détermine si le jour attendu doit être férié ou non
     */
    public function testIsHoliday(DateTime $day, $isHoliday): void
    {
        self::assertEquals(
            $this->helper->isHoliday($day),
            $isHoliday,
            "{$day->format(DateTime::ATOM)} n'est pas férié."
        );
    }

    /**
     * Correspondances du prochain jour ouvré pour une date donnée
     *
     * @return array
     */
    public function nextBusinessDayProvider()
    {
        return [
            'Premier Vendredi 2019, à 00:00 en UTC' => ['2019-01-05T00:00:00+00:00', '2019-01-07T00:00:00+00:00'],
            'Premier Samedi 2019' => ['2019-01-05T00:00:00+00:00', '2019-01-07T00:00:00+00:00'],
            'Premier Dimanche 2019' => ['2019-01-06T00:00:00+00:00', '2019-01-07T00:00:00+00:00'],
            'Premier Lundi 2019' => ['2019-01-07T00:00:00+00:00', '2019-01-08T00:00:00+00:00'],
            'Second Mardi 2019' => ['2019-01-08T00:00:00+00:00', '2019-01-09T00:00:00+00:00'],
            'Vendredi avant Pâques 2019' => ['2019-04-19T00:00:00+00:00', '2019-04-23T00:00:00+00:00'],
            'Lundi de Pâques 2019' => ['2019-04-22T00:00:00+00:00', '2019-04-23T00:00:00+00:00'],
            'Veille de Noël 2019' => ['2019-12-24T00:00:00+00:00', '2019-12-26T00:00:00+00:00'],
            'Noël 2019' => ['2019-12-25T00:00:00+00:00', '2019-12-26T00:00:00+00:00'],
        ];
    }

    /**
     * Teste le retour du prochain jour ouvré pour une date donnée
     * @dataProvider nextBusinessDayProvider
     *
     * @param string $day      Jour de l'année
     * @param string $expected Jour ouvré suivant attendu
     *
     * @throws Exception
     */
    public function testGetNextBusinessDay(string $day, string $expected): void
    {
        self::assertEquals(
            $expected,
            $actual = $this->helper->getNextBusinessDay(new DateTime($day))->format(DateTime::ATOM),
            "Le prochain jour ouvré $actual n'est pas celui attendu $expected"
        );
    }

    /**
     * Fournit des correspondances métier entre un jour donné, une limite de jours ouvrés, et un attendu
     * @return array [attendu, actuel, limite]
     *
     * @throws Exception
     */
    public static function deadlineGMTProvider()
    {
        $dataset = [];

        // Premier Lundi 2019 (07/01)
        $day = new DateTime('2019-01-07T12:00:00+00:00');
        // Lundi 07/01. A 2 jours, on tombe au Jeudi 10/01 à minuit, à partir de là on bloque.
        $dataset['Lundi 07/01. A 2 jours.'] = [new DateTime('2019-01-10T00:00:00+00:00'), $day, 2];
        // Lundi 07/01. A 3 jours, on tombe au Vendredi 11/01 à minuit, à partir de là on bloque.
        $dataset['Lundi 07/01. A 3 jours.'] = [new DateTime('2019-01-11T00:00:00+00:00'), $day, 3];
        // Lundi 07/01. A 4 jours, on tombe au Samedi 12/01 à minuit, à partir de là on bloque.
        $dataset['Lundi 07/01. A 4 jours.'] = [new DateTime('2019-01-12T00:00:00+00:00'), $day, 4];
        // Lundi 07/01. A 5 jours, on tombe au Mardi 15/01 à minuit, à partir de là on bloque.
        $dataset['Lundi 07/01. A 5 jours.'] = [new DateTime('2019-01-15T00:00:00+00:00'), $day, 5];

        // Pâques 2019 (Dimanche 21 Avril 2019)
        $easter2019 = (new BusinessDateTimeHelper())->getEasterDateTime(2019);
        // Le Vendredi qui précède Pâques 2019 est le Vendredi 19 Avril 2019
        $interval = new DateInterval('P2D');
        // Inversion pour le retrouver, 2 jours avant le Dimanche de Pâques
        $interval->invert = 1;
        $day = $easter2019->add($interval);
        // Vendredi 19/04. A 5 jours, on tombe au Mardi 30/04 à minuit, à partir de là on bloque.
        $dataset['Vendredi 19/04. A 5 jours'] = [new DateTime('2019-04-30T00:00:00+00:00'), $day, 5];
        // Vendredi 19/04. A 6 jours, on tombe au Jeudi 02/05 à minuit, à partir de là on bloque.
        $dataset['Vendredi 19/04. A 6 jours'] = [new DateTime('2019-05-01T00:00:00+00:00'), $day, 6];

        // Jeudi 06/06/2019
        $day = new DateTime('2019-06-06T12:00:00+00:00');
        // Jeudi 06/06. A 2 jours, on tombe au Mercredi 12/06 à minuit, à partir de là on bloque.
        $dataset['Jeudi 06/06. A 2 jours'] = [new DateTime('2019-06-12T00:00:00+00:00'), $day, 2];

        // Mardi 24/12/2019
        $day = new DateTime('2019-12-24T12:00:00+00:00');
        // Mardi 24/12. A 0 jours, on tombe au Jeudi 26/12 à minuit, à partir de là on bloque.
        $dataset['Mardi 24/12. A 0 jours'] = [new DateTime('2019-12-26T00:00:00+00:00'), $day, 0];
        // Mardi 24/12. A 1 jours, on tombe au Vendredi 27/12 à minuit, à partir de là on bloque.
        $dataset['Mardi 24/12. A 1 jours'] = [new DateTime('2019-12-27T00:00:00+00:00'), $day, 1];
        // Mardi 24/12. A 2 jours, on tombe au Lundi 30/12 à minuit, à partir de là on bloque.
        $dataset['Mardi 24/12. A 2 jours'] = [new DateTime('2019-12-28T00:00:00+00:00'), $day, 2];
        // Mardi 24/12. A 11 jours, on tombe au Lundi 13/01/2020 à minuit, à partir de là on bloque.
        $dataset['Mardi 24/12. A 11 jours'] = [new DateTime('2020-01-11T00:00:00+00:00'), $day, 11];

        return $dataset;
    }

    /**
     * Vérifie le jour ouvré limite pour une date donnée
     *
     * @dataProvider deadlineGMTProvider
     *
     * @param DateTime $expected Date attendue
     * @param DateTime $day      Date de départ
     * @param int      $limit    Limite de jours ouvrés à tester
     */
    public function testGetDeadlineGMT(DateTime $expected, DateTime $day, int $limit): void
    {
        $result = $this->helper->getDeadline($day, $limit);
        self::assertEquals(
            $expected->format(DateTime::ATOM),
            $result->format(DateTime::ATOM),
            sprintf(
                "Pour le %s pour une limite fixée à %d la date obtenue %s ne correspond pas à l'attendue %s",
                $day->format('d-m-Y'),
                $limit,
                $result->format(DateTime::ATOM),
                $expected->format(DateTime::ATOM)
            )
        );
    }

    /**
     * Doit prendre en compte :
     * - Cas sans jours fériés
     * - Cas avec weekend
     * - Cas avec jours fériés
     * - Cas avec timezones différentes
     */
    public function deadlineProviderWithDST()
    {
        return [
            'Lundi 09 septembre 2019 en France avec deux jours de délai' => [
                '2019-09-09T12:45:00+02:00',
                '2019-09-12T00:00:00+02:00',
            ],
            'Vendredi 13 septembre 2019 en France avec deux jours de délai' => [
                '2019-09-13T16:00:00+02:00',
                '2019-09-18T00:00:00+02:00',
            ],
        ];
    }

    /**
     * Vérifie le jour ouvré limite pour une date donnée
     * @dataProvider deadlineProviderWithDST
     *
     * @param string $day
     * @param string $expected
     *
     * @throws Exception
     */
    public function testGetDeadlineWithDST(string $day, string $expected): void
    {
        $day = new DateTime($day);
        $expected = new DateTime($expected);

        $result = $this->helper->getDeadline($day, 2);

        self::assertEquals($expected, $result);
    }

    /**
     * Vérifie le jour limite pour une date donnée
     * @expectedException InvalidArgumentException
     */
    public function testFailingGetDeadline(): void
    {
        $this->helper->getDeadline(new DateTime(), -1);
    }

    public function testDeadlineExceeded(): void
    {
        // On revient à il y a une semaine
        $rewind = 7;
        $interval = new DateInterval(sprintf('P%dD', $rewind));
        $interval->invert = 1;
        $today = new DateTime();
        $sevenDaysAgo = $today->add($interval);
        self::assertTrue($this->helper->deadlineExceeded($sevenDaysAgo, 2));
        self::assertFalse($this->helper->deadlineExceeded($sevenDaysAgo, 20));
    }
}
