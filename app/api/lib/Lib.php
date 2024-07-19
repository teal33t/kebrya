<?php
// Lib.php
namespace Jyotish;

use Jyotish\Base\Data;
use Jyotish\Base\Locality;
use Jyotish\Base\Analysis;
use Jyotish\Ganita\Method\Swetest;
use Jyotish\Dasha\Dasha;
use Jyotish\Panchanga\AngaDefiner;
use Jyotish\Graha\Lagna;
use Jyotish\Yoga\Yoga;
use Jyotish\Bala\AshtakaVarga;
use Jyotish\Bala\GrahaBala;
use Jyotish\Bala\RashiBala;
use Jyotish\Graha\Graha;
use Carbon\Carbon;

use \Datetime;
use \DateTimeZone;
use \DateInterval;

use Jyotish\DateConvertor;
use Jyotish\MoonPhase;




class Lib
{
    const SWETEST_PATH = '/root/kebrya/app/api/swetest/src';
    public $grahas;
    public $lagnas;

    public function __construct()
    {
        $replaceGrahas = function ($string, $grahas) {
            $outputString = '';
            for ($i = 0; $i < strlen($string); $i += 2) {
                $substring = substr($string, $i, 2);
                if (array_key_exists($substring, $grahas)) {
                    $outputString .= $grahas[$substring];
                } else {
                    $outputString .= $substring;
                }
            }
            return $outputString;
        };

        $this->grahas = [
            'Sy' => 'Su',
            'Ch' => 'Mo',
            'Ma' => 'Ma',
            'Bu' => 'Me',
            'Gu' => 'Ju',
            'Sk' => 'Ve',
            'Sa' => 'Sa',
            'Ra' => 'Ra',
            'Ke' => 'Ke'
        ];

        $this->lagnas = [
            1 => 'Aries',
            2 => 'Taurus',
            3 => 'Gemini',
            4 => 'Cancer',
            5 => 'Leo',
            6 => 'Virgo',
            7 => 'Libra',
            8 => 'Scorpio',
            9 => 'Sagittarius',
            10 => 'Capricorn',
            11 => 'Aquarius',
            12 => 'Pisces'
        ];
    }

    public function calculator($params = [])
    {
        if (empty($params)) {
            return $this->calculateChartForNowTehran();
        }

        $latitude = $params['latitude'] ?? null;
        $longitude = $params['longitude'] ?? null;
        $year = $params['year'] ?? null;
        $month = $params['month'] ?? null;
        $day = $params['day'] ?? null;
        $hour = $params['hour'] ?? null;
        $min = $params['min'] ?? null;
        $sec = $params['sec'] ?? null;
        $convert_from = $params['convert_from'] ?? 'gregorian';
        $convert_to = $params['convert_to'] ?? 'gregorian';
        $time_zone = $params['time_zone'] ?? '+03:30';
        $dst_hour = $params['dst_hour'] ?? 0;
        $dst_min = $params['dst_min'] ?? 0;
        $t_year = $params['t_year'] ?? null;
        $t_month = $params['t_month'] ?? null;
        $t_day = $params['t_day'] ?? null;
        $t_hour = $params['t_hour'] ?? null;
        $t_min = $params['t_min'] ?? null;
        $t_sec = $params['t_sec'] ?? null;
        $nesting = $params['nesting'] ?? 0;
        $varga = $params['varga'] ?? ["D1"];
        $infolevel = $params['infolevel'] ?? [];

        return $this->calculateChart(
            $latitude,
            $longitude,
            $year,
            $month,
            $day,
            $hour,
            $min,
            $sec,
            $convert_from,
            $convert_to,
            $time_zone,
            $dst_hour,
            $dst_min,
            $t_year,
            $t_month,
            $t_day,
            $t_hour,
            $t_min,
            $t_sec,
            $varga,
            $nesting,
            $infolevel
        );
    }

    public function calculateTransit(
        &$vargaData,
        $latitude,
        $longitude,
        $timezone,
        $t_year = null,
        $t_month = null,
        $t_day = null,
        $t_hour = null,
        $t_min = null,
        $t_sec = 0,
        $t_date_type = 'gregorian',
        $dst_hour = 0,
        $dst_min = 0
    ) {
        // If transit date is provided, use it; otherwise, use current date and time
        if ($t_year && $t_month && $t_day && $t_hour && $t_min) {
            if ($t_date_type == 'jalali') {
                // Convert Jalali to Gregorian
                $g_date = DateConvertor::JalaliToGregorian("$t_year-$t_month-$t_day", 'YYYY-MM-DD', true);
                $t_year = $g_date[0];
                $t_month = $g_date[1];
                $t_day = $g_date[2];
            }
            $datetime = sprintf("%s-%s-%s %s:%s:%s", $t_year, $t_month, $t_day, $t_hour, $t_min, $t_sec);
        } else {
            $now = new DateTime('now', new DateTimeZone($timezone));
            $datetime = $now->format('Y-m-d H:i:s');
        }

        // Create DateTime object and apply DST adjustments
        $transit_date_time = new DateTime($datetime, new DateTimeZone($timezone));
        $transit_date_time->modify("-{$dst_hour} hours");
        $transit_date_time->modify("-{$dst_min} minutes");

        // Calculate transit chart
        $locality = $this->createLocality($longitude, $latitude, 0);
        $ganita = new Swetest(["swetest" => self::SWETEST_PATH]);
        $data = new Data($transit_date_time, $locality, $ganita);
        $data->calcVargaData(['D1']);
        $data->calcParams();
        $analysis = new Analysis($data);
        $transitData = $analysis->getVargaData('D1');

        // Process transit data

        $transitData['graha'] = $this->representGrahas($transitData['graha']);
        $transitData['bhava'] = $this->representBhavas($transitData['bhava']);

        // Add transit data to vargaData
        $vargaData['transit'] = $transitData;

        // Add transit datetime information
        $vargaData['transit']['user']['date'] = [
            'year' => (int) $transit_date_time->format('Y'),
            'month' => (int) $transit_date_time->format('m'),
            'day' => (int) $transit_date_time->format('d'),
        ];


        // Add transit datetime information
        $vargaData['transit']['user']['time'] = [
            'hour' => (int) $transit_date_time->format('H'),
            'min' => (int) $transit_date_time->format('i'),
            'sec' => (int) $transit_date_time->format('s'),
            'timezone' => $timezone
        ];

        // If original date was Jalali, convert back for display
        if ($t_date_type == 'jalali') {
            $j_date = DateConvertor::GregorianToJalali($transit_date_time->format('Y-m-d'), 'YYYY-MM-DD', true);
            $vargaData['transit']['user']['date']['year'] = $j_date[0];
            $vargaData['transit']['user']['date']['month'] = $j_date[1];
            $vargaData['transit']['user']['date']['day'] = $j_date[2];
        }
        # merge garaha and bhava
        foreach ($transitData['graha'] as $bhava_key => $bhava_value) {
            $rashi = $bhava_value['rashi'];
            $bhava_grahas = [];

            foreach ($transitData['bhava'] as $graha_key => $graha_value) {
                if ($graha_value['rashi'] == $rashi) {
                    $bhava_grahas[$graha_key] = $graha_value;
                }
            }

            $vargaData['transit']['houses'][$bhava_key]['graha'] = $bhava_grahas;
        }

        ### CLEAR UNNECESSARY DATA
        unset($vargaData['transit']['user']['datetime']);
        unset($vargaData['transit']['user']['timezone']);
        unset($vargaData['transit']['user']['longitude']);
        unset($vargaData['transit']['user']['latitude']);
        unset($vargaData['transit']['user']['altitude']);
    }

    public function calculateChartForNowTehran()
    {
        $now = Carbon::now(new DateTimeZone('Asia/Tehran'));
        $latitude = '35.708309';
        $longitude = '51.380730';
        $year = $now->year;
        $month = $now->month;
        $day = $now->day;
        $hour = $now->hour;
        $min = $now->minute;
        $sec = $now->second;
        $convert_from = 'gregorian';
        $convert_to = 'jalali';
        $time_zone = '+03:30';
        $dst_hour = 0;
        $dst_min = 0;
        $nesting = 2;
        $varga = ["D1"];
        $infolevel = [];

        return $this->calculateChart(
            $latitude,
            $longitude,
            $year,
            $month,
            $day,
            $hour,
            $min,
            $sec,
            $convert_from,
            $convert_to,
            $time_zone,
            $dst_hour,
            $dst_min,
            null,
            null,
            null,
            null,
            null,
            null,
            $varga,
            $nesting,
            $infolevel
        );
    }


    public function convertToJalali($date)
    {
        $time = strtotime($date);
        $year = date("Y", $time);
        $month = date("m", $time);
        $day = date("d", $time);
        $jalaliDate = DateConvertor::GregorianToJalali("$year-$month-$day", 'YYYY-MM-DD', true);
        return $jalaliDate;
        // return `{$jalaliDate[0]}-{$jalaliDate[1]}-{$jalaliDate[2]}`;
    }

    /*
     * $infolevel = ["basic", ]
     *
     */
    public function calculateChart(
        $latitude,
        $longitude,
        $year,
        $month,
        $day,
        $hour,
        $min,
        $sec,
        $convert_from = 'gregorian',
        $convert_to = 'jalali',
        $time_zone = '+03:30',
        $dst_hour = 0,
        $dst_min = 0,
        $t_year = null,
        $t_month = null,
        $t_day = null,
        $t_hour = null,
        $t_min = null,
        $t_sec = null,
        $varga = [
            "D1",
            "D2",
            "D3",
            "D4",
            "D7",
            "D9",
            "D10",
            "D12",
            "D16",
            "D20",
            "D24",
            "D27",
            "D30",
            "D40",
            "D45",
            "D60",
        ],
        $nesting = 4,
        array $infolevel = ["basic", "ashtakavarga", "grahabala", "rashibala", "yogas", "panchanga", "transit"]
    ) {
        $gregorianDate = $this->convertDateToGregorian($year, $month, $day, $hour, $min, $sec, $convert_from);

        $locality = $this->createLocality($longitude, $latitude, 0);
        $date = $this->createDateTime(
            $gregorianDate->year,
            $gregorianDate->month,
            $gregorianDate->day,
            $gregorianDate->hour,
            $gregorianDate->minute,
            $gregorianDate->second,
            $time_zone,
            $dst_hour,
            $dst_min
        );

        $ganita = new Swetest(["swetest" => self::SWETEST_PATH]);
        $data = new Data($date, $locality, $ganita);

        $vargas = $varga;
        $this->calculateVargaData($data, $vargas);
        $analysis = new Analysis($data);
        $vargaData = $analysis->getVargaData('D1');

        if (in_array('yogas', $infolevel)) {
            // Yoga calculations
            $data->calcYoga([Yoga::TYPE_MAHAPURUSHA, Yoga::TYPE_DHANA, Yoga::TYPE_RAJA, Yoga::TYPE_NABHASHA, Yoga::TYPE_PARIVARTHANA, Yoga::TYPE_SANNYASA]);
        }

        if (in_array('ashtakavarga', $infolevel)) {
            // AshtakaVarga calculation
            $ashtakaVarga = new AshtakaVarga($data);
            $vargaData['ashtakavarga'] = $ashtakaVarga->getBhinnAshtakavarga();

        }
        if (in_array('grahabala', $infolevel)) {
            // GrahaBala calculation
            $grahaBala = new GrahaBala($data);
            $vargaData['grahabala'] = $grahaBala->getBala();

        }
        if (in_array('rashibala', $infolevel)) {
            // RashiBala calculation
            $rashiBala = new RashiBala($data);
            $vargaData['rashibala'] = $rashiBala->getBala();
        }


        $angaDefiner = new AngaDefiner($data);

        foreach ($vargaData['graha'] as $grahaKey => $value) {
            $nakshatra = $angaDefiner->getNakshatra(false, false, $grahaKey);
            $vargaData['graha'][$grahaKey]['nakshatra'] = $nakshatra;


            if (in_array('basic', $infolevel)) {
                $Graha = Graha::getInstance($grahaKey)->setEnvironment($data);
                $vargaData['graha'][$grahaKey]['astangata'] = $Graha->isAstangata(); // combustion
                $vargaData['graha'][$grahaKey]['rashiAvastha'] = $Graha->getRashiAvastha(); // dignity
                $vargaData['graha'][$grahaKey]['vargottama'] = $Graha->isVargottama(); // Vargottama
                $vargaData['graha'][$grahaKey]['yuddha'] = $Graha->isYuddha(); // graha is in planetary war
            }


            if (in_array('panchanga', $infolevel)) {
                $Graha = Graha::getInstance($grahaKey)->setEnvironment($data);
                $vargaData['graha'][$grahaKey]['astangata'] = $Graha->isAstangata(); // combustion
                $vargaData['graha'][$grahaKey]['rashiAvastha'] = $Graha->getRashiAvastha(); // dignity
                $vargaData['graha'][$grahaKey]['vargottama'] = $Graha->isVargottama(); // Vargottama
                $vargaData['graha'][$grahaKey]['yuddha'] = $Graha->isYuddha(); // graha is in planetary war
                $vargaData['graha'][$grahaKey]['gocharastha'] = $Graha->isGocharastha(); // gocharastha
                $vargaData['graha'][$grahaKey]['bhavaCharacter'] = $Graha->getBhavaCharacter(); // Bhava Character
                $vargaData['graha'][$grahaKey]['tempRelation'] = $Graha->getTempRelation(); // Get tatkalika (temporary) relations
                $vargaData['graha'][$grahaKey]['relation'] = $Graha->getRelation(); // Get summary relations
                $vargaData['graha'][$grahaKey]['yogakaraka'] = $Graha->isYogakaraka(); // yogakaraka
                $vargaData['graha'][$grahaKey]['mrityu'] = $Graha->isMrityu(); // graha is in mrityu bhaga
                $vargaData['graha'][$grahaKey]['pushkaraNavamsha'] = $Graha->isPushkara(Graha::PUSHKARA_NAVAMSHA); // graha is in pushkara navamsha
                $vargaData['graha'][$grahaKey]['pushkaraBhaga'] = $Graha->isPushkara(Graha::PUSHKARA_BHAGA); // graha is in pushkara bhaga
                $vargaData['graha'][$grahaKey]['avastha'] = $Graha->getAvastha(); // Get avastha of graha
                $vargaData['graha'][$grahaKey]['dispositor'] = $Graha->getDispositor(); // Get ruler of the bhava, where graha is positioned
            }
        }

        $nakshatra = $angaDefiner->getNakshatra(false, false, Lagna::KEY_LG);
        $vargaData['lagna'][Lagna::KEY_LG]['nakshatra'] = $nakshatra;

        if ($nesting) {
            // Dasha calculation
            $data->calcDasha(Dasha::TYPE_VIMSHOTTARI, null, ['nesting' => $nesting]);
            $dasha = $data->getData();

            if ($convert_to == 'jalali') {
                $dasha['dasha']['vimshottari'] = $this->convertBulkToJalali($dasha['dasha']['vimshottari']);
            } elseif ($convert_to == 'hijri') {
                $dasha['dasha']['vimshottari'] = $this->convertBulkToHijri($dasha['dasha']['vimshottari']);
            } else {
                $dasha['dasha']['vimshottari'] = $this->refactorDashaGrahas($dasha['dasha']['vimshottari']);
            }

            if (in_array('panchanga', $infolevel)) {
                $vargaData['panchanga'] = $dasha['panchanga'];
            }

            $vargaData['dasha'] = $dasha['dasha']['vimshottari'];
        }


        if ($convert_to == 'jalali') {
            if ($convert_from == 'jalali') {
                $vargaData['user'] = [
                    'date' => [
                        // 'datetime' => "`$year-$month-$day $hour:$min:$sec`",
                        'year' => $year,
                        'month' => $month,
                        'day' => $day
                    ],
                    'time' => [
                        'timezone' => $time_zone,
                        'hour' => $gregorianDate->hour,
                        'min' => $gregorianDate->minute,
                        'sec' => $gregorianDate->second,
                    ],
                ];
            }
            if ($convert_from == 'gregorian') {
                $vargaData['user'] = [
                    'date' => [
                        // 'datetime' => $gregorianDate->format('Y-m-d H:i:s'),
                        'year' => $gregorianDate->year,
                        'month' => $gregorianDate->month,
                        'day' => $gregorianDate->day,
                    ],
                    'time' => [
                        'timezone' => $time_zone,
                        'hour' => $gregorianDate->hour,
                        'min' => $gregorianDate->minute,
                        'sec' => $gregorianDate->second,
                    ],
                ];
            }
        }

        if ($convert_from == 'gregorian' && $convert_to == 'gregorian') {
            $vargaData['user'] = [
                'date' => [
                    // 'datetime' => $gregorianDate->format('Y-m-d H:i:s'),
                    'year' => $gregorianDate->year,
                    'month' => $gregorianDate->month,
                    'day' => $gregorianDate->day,
                ],
                'time' => [
                    'timezone' => $time_zone,
                    'hour' => $gregorianDate->hour,
                    'min' => $gregorianDate->minute,
                    'sec' => $gregorianDate->second,
                ],
            ];
        }


        $vargaData['user']['location'] = ['lat' => $latitude, 'lon' => $longitude];
        $vargaData['user']['location']['dms'] = ['lat' => $this->degToDms($latitude), 'lon' => $this->degToDms($longitude)];

        $vargaData['graha'] = $this->representGrahas($vargaData['graha']);
        $graha = $vargaData['graha'];
        $bhava = $vargaData['bhava'];


        if (in_array('transit', $infolevel)) {
            $this->calculateTransit(
                $vargaData,
                $latitude,
                $longitude,
                $time_zone,
                $t_year,
                $t_month,
                $t_day,
                $t_hour,
                $t_min,
                $t_sec,
                $convert_from,
                $dst_hour,
                $dst_min
            );
        }

        # merge garaha and bhava
        foreach ($bhava as $bhava_key => $bhava_value) {
            $rashi = $bhava_value['rashi'];
            $bhava_grahas = [];

            foreach ($graha as $graha_key => $graha_value) {
                if ($graha_value['rashi'] == $rashi) {
                    $bhava_grahas[$graha_key] = $graha_value;
                }
            }

            $vargaData['houses'][$bhava_key]['graha'] = $bhava_grahas;
        }

        return $vargaData;
    }

    private function convertDateToGregorian($year, $month, $day, $hour, $min, $sec, $from)
    {
        if ($from == 'jalali') {
            $gregorianDate = DateConvertor::JalaliToGregorian("$year-$month-$day");
            return DateConvertor::Carbonize($gregorianDate . " $hour:$min:$sec");
        } elseif ($from == 'hijri') {
            $gregorianDate = DateConvertor::HijriToGregorian("$year-$month-$day");
            return DateConvertor::Carbonize($gregorianDate . " $hour:$min:$sec");
        } else {
            return Carbon::create($year, $month, $day, $hour, $min, $sec);
        }
    }

    private function convertResponseDates($vargaData, $convert_to)
    {
        foreach ($vargaData['dasha'] as $key => $value) {
            if (is_array($value)) {
                $vargaData['dasha'][$key] = $this->convertResponseDates($value, $convert_to);
            } elseif ($key == 'start' || $key == 'end') {
                $vargaData['dasha'][$key] = $this->convertDateTo($value, $convert_to);
            }
        }

        return $vargaData;
    }

    private function convertDateTo($date, $to)
    {
        $carbonDate = Carbon::parse($date);

        if ($to == 'jalali') {
            return DateConvertor::GregorianToJalali($carbonDate->format('Y-m-d H:i:s'));
        } elseif ($to == 'hijri') {
            return DateConvertor::GregorianToHijri($carbonDate->format('Y-m-d H:i:s'));
        } else {
            return $carbonDate->format('Y-m-d H:i:s');
        }
    }

    private function createLocality($latitude, $longitude, $altitude)
    {
        return new Locality([
            'longitude' => $longitude,
            'latitude' => $latitude,
            'altitude' => $altitude
        ]);
    }

    private function createDateTime($year, $month, $day, $hour, $min, $sec, $time_zone, $dst_hour, $dst_min)
    {
        $datetime = sprintf("%s-%s-%s %s:%s:%s%s", $year, $month, $day, $hour, $min, $sec, $time_zone);
        $date = new DateTime($datetime);

        $date->modify(sprintf("-%s hours", $dst_hour));
        $date->modify(sprintf("-%s minutes", $dst_min));

        return $date;
    }

    private function calculateVargaData($data, $vargas)
    {
        $data->calcVargaData($vargas);
        $data->calcParams();
    }


    private function degToDms($degree)
    {
        $d = intval($degree);
        $m = intval(($degree - $d) * 60);
        $s = ($degree - $d - $m / 60) * 3600;

        return ['deg' => $d, 'min' => $m, 'sec' => $s];
    }

    public function replaceGrahaStrings($input)
    {
        $search = array_keys($this->grahas);
        $replace = array_values($this->grahas);
        return str_replace($search, $replace, $input);
    }

    private function converDashaKeyToEng($inputString)
    {
        $outputString = '';
        for ($i = 0; $i < strlen($inputString); $i += 2) {
            $substring = substr($inputString, $i, 2);
            $outputString .= $this->grahas[$substring];
        }
        return $outputString;
    }



    private function refactorDashaGrahas($dasha)
    {
        foreach ($dasha as $_key => $_value) {
            if ($_key == "periods") {
                foreach ($this->grahas as $pkey => $value) {
                    // $key = $this->grahas[$pkey];
                    $val = $dasha['periods'][$pkey];
                    $dasha_key = $dasha['periods'][$pkey]['key'];
                    unset($dasha['periods'][$pkey]);
                    $dasha['periods'][$this->grahas[$pkey]] = $this->refactorDashaGrahas($val);
                    $dasha['periods'][$this->grahas[$pkey]]['key'] = $this->converDashaKeyToEng($dasha_key);
                }
            }
        }
        return $dasha;
    }


    public function calculateMoonPhaseChart($now_data)
    {
        $timestamp = mktime(
            $now_data['user']['time']['hour'],
            $now_data['user']['time']['min'],
            $now_data['user']['time']['sec'],
            $now_data['user']['date']['month'],
            $now_data['user']['date']['day'],
            $now_data['user']['date']['year']
        );

        $moonPhase = new MoonPhase($timestamp);

        // Calculate the percentage of the moon's visible surface that is illuminated
        $percentage = $moonPhase->getIllumination() * 100;

        // Get the phase name
        $label = $moonPhase->getPhaseName();


        return array(
            "phase" => $moonPhase->getPhase(),
            "percentage" => round($percentage, 2),
            "label" => $label,
            "age" => round($moonPhase->getAge(), 2),
            "distance" => round($moonPhase->getDistance()),
            "diameter" => round($moonPhase->getDiameter(), 4),
            "sun_distance" => round($moonPhase->getSunDistance()),
            "sun_diameter" => round($moonPhase->getSunDiameter(), 4),
            "current_new_moon" => date('Y-m-d H:i', $moonPhase->getPhaseNewMoon()),
            "current_full_moon" => date('Y-m-d H:i', $moonPhase->getPhaseFullMoon()),
            "current_first_quarter" => date('Y-m-d H:i', $moonPhase->getPhaseFirstQuarter()),
            "current_last_quarter" => date('Y-m-d H:i', $moonPhase->getPhaseLastQuarter()),

            "next_new_moon" => date('Y-m-d H:i', $moonPhase->getPhaseNextNewMoon()),
            "next_full_moon" => date('Y-m-d H:i', $moonPhase->getPhaseNextFullMoon()),
            "next_first_quarter" => date('Y-m-d H:i', $moonPhase->getPhaseNextFirstQuarter()),
            "next_last_quarter" => date('Y-m-d H:i', $moonPhase->getPhaseNextLastQuarter()),
        );
    }


    private function convertBulkToJalali($dasha)
    {
        foreach ($dasha as $_key => $_value) {
            if ($_key == 'start' || $_key == 'end') {
                $time = strtotime($_value);
                $year = date("Y", $time);
                $month = date("m", $time);
                $day = date("d", $time);
                $hour = date("H", $time);
                $min = date("i", $time);
                $jalaliDate = DateConvertor::GregorianToJalali("$year-$month-$day", 'YYYY-MM-DD', true);

                if ($hour && $min) {
                    $dasha[$_key] = sprintf('%04d-%02d-%02d %02d:%02d', (int) $jalaliDate[0], (int) $jalaliDate[1], (int) $jalaliDate[2], $hour, $min);
                } else {
                    $dasha[$_key] = sprintf('%04d-%02d-%02d %02d:%02d', (int) $jalaliDate[0], (int) $jalaliDate[1], (int) $jalaliDate[2], $hour, $min);
                }
            }
            if ($_key == "periods") {
                foreach ($this->grahas as $pkey => $value) {
                    $key = $this->grahas[$pkey];
                    $val = $dasha['periods'][$pkey];
                    unset($dasha['periods'][$pkey]);
                    $dasha['periods'][$key] = $this->convertBulkToJalali($val);
                    $dasha['periods'][$key]['key'] = $this->replaceGrahaStrings($dasha['periods'][$key]['key']);
                }
            }
        }

        return $dasha;
    }

    private function convertBulkToHijri($dasha)
    {
        foreach ($dasha as $_key => $_value) {
            if ($_key == 'start' || $_key == 'end') {
                $time = strtotime($_value);
                $year = date("Y", $time);
                $month = date("m", $time);
                $day = date("d", $time);
                $hour = date("H", $time);
                $min = date("i", $time);
                $hijriDate = DateConvertor::GregorianToHijri("$year-$month-$day");
                $dasha[$_key] = sprintf('%04d-%02d-%02d %02d:%02d', $hijriDate['year'], $hijriDate['month'], $hijriDate['day'], $hour, $min);
            }
            if ($_key == "periods") {
                foreach ($this->grahas as $pkey => $value) {
                    $dasha['periods'][$value] = $this->convertBulkToHijri($dasha['periods'][$pkey]);
                }
            }
        }

        return $dasha;
    }

    public function lagnaToText($rashi_number)
    {
        return $this->lagnas[$rashi_number] ?? 'Unknown';
    }

    public function representGrahas($data)
    {
        foreach ($this->grahas as $_key => $_value) {
            @$data[$_key][sprintf('latitude_dms', $_key)] = $this->degToDms($data[$_key]['latitude']);
            $data[$_key][sprintf('longitude_dms', $_key)] = $this->degToDms($data[$_key]['longitude']);
            $data[$_key][sprintf('speed_dms', $_key)] = $this->degToDms($data[$_key]['speed']);
            $data[$_key][sprintf('degree_dms', $_key)] = $this->degToDms($data[$_key]['degree']);
            $data[$_key]['rashi_name'] = $this->lagnaToText($data[$_key]['rashi']);
        }
        $data1 = array_combine($this->grahas, array_values($data));
        return $data1;
    }


    public function representVargas($data)
    {
        foreach ($data as $key => $value) {
            if (isset($value['bhava'])) {
                foreach ($value['bhava'] as $_key => $_value) {
                    $data[$key]['bhava'][$_key]['longitude_dms'] = $this->degToDms($_value['longitude']);
                    $data[$key]['bhava'][$_key]['degree_dms'] = $this->degToDms($_value['degree']);
                    $data[$key]['bhava'][$_key]['rashi_name'] = $this->lagnaToText($_value['rashi']);
                }
            }
            if (isset($value['graha'])) {
                $data[$key]['graha'] = $this->representGrahas($value['graha']);
            }
            if (isset($value['lagna'])) {
                $data[$key]['lagna']['Lg']['longitude_dms'] = $this->degToDms($value['lagna']['Lg']['longitude']);
                $data[$key]['lagna']['Lg']['degree_dms'] = $this->degToDms($value['lagna']['Lg']['degree']);
                $data[$key]['lagna']['Lg']['rashi_name'] = $this->lagnaToText($value['lagna']['Lg']['rashi']);
                $data[$key]['lagna']['MLg']['longitude_dms'] = $this->degToDms($value['lagna']['MLg']['longitude']);
                $data[$key]['lagna']['MLg']['degree_dms'] = $this->degToDms($value['lagna']['MLg']['degree']);
                $data[$key]['lagna']['MLg']['rashi_name'] = $this->lagnaToText($value['lagna']['MLg']['rashi']);
            }
        }
        return $data;
    }

    public function representLagna($data)
    {
        $data['Lg']['longitude_dms'] = $this->degToDms($data['Lg']['longitude']);
        $data['Lg']['degree_dms'] = $this->degToDms($data['Lg']['degree']);
        $data['MLg']['longitude_dms'] = $this->degToDms($data['Lg']['longitude']);
        $data['MLg']['degree_dms'] = $this->degToDms($data['Lg']['degree']);
        $data['Lg']['rashi_name'] = $this->lagnaToText($data['Lg']['rashi']);
        $data['MLg']['rashi_name'] = $this->lagnaToText($data['MLg']['rashi']);

        return $data;
    }

    public function representBhavas($data)
    {
        foreach ($data as $key => $value) {

            $data[$key]['longitude_dms'] = $this->degToDms($value['longitude']);
            $data[$key]['degree_dms'] = $this->degToDms($value['degree']);
            $data[$key]['ascension_dms'] = $this->degToDms($value['ascension']);
            $data[$key]['rashi_name'] = $value['rashi'];
        }
        return $data;
    }


}
