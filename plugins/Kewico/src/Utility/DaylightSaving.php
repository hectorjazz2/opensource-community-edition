<?php
declare(strict_types=1);

namespace Kewico\Utility;

/**
 * Daylight saving rules as kewico_php8 applied them (TmzoneHelper /
 * TmzoneComponent there): decided for the date being converted, not for
 * today, with Kewico's corrected rules (US since 2007, Chile, Brazil,
 * Australia, New Zealand).
 *
 * Ported unchanged, including two quirks of the old code, so dates show
 * exactly as in the old system: weekdays are looked up in the current
 * year, and February always has the current year's length. Both only
 * matter near a switch date in other years.
 */
class DaylightSaving
{
    /**
     * Is daylight saving active in this time zone at the given moment?
     *
     * @param int|string|null $timezoneid timezones.id
     * @param float|int|string $gmtOffset timezones.gmt_offset (hours)
     * @param int|null $year Year of the date to check; null = now
     * @param int|null $month Month
     * @param int|null $day Day
     * @param int|null $hour Hour (default 12)
     * @return bool
     */
    public static function isActive($timezoneid, $gmtOffset, ?int $year = null, ?int $month = null, ?int $day = null, ?int $hour = null): bool
    {
        $gmtOffset = (float)$gmtOffset;
        if ($year !== null && $month !== null && $day !== null) {
            $gmtMinute = 0;
            $gmtHour = $hour ?? 12;
            $gmtMonth = $month;
            $gmtDay = $day;
            $gmtYear = $year;
        } else {
            $gmtMinute = (int)gmdate('i');
            $gmtHour = (int)gmdate('H');
            $gmtMonth = (int)gmdate('m');
            $gmtDay = (int)gmdate('d');
            $gmtYear = (int)gmdate('Y');
        }
        $curYear = (int)date('Y', mktime((int)($gmtHour + $gmtOffset), $gmtMinute, 0, $gmtMonth, $gmtDay, $gmtYear));
        // The old code always passed month, day and hour on to the rules,
        // also when it checked "now".
        $m = $gmtMonth;
        $d = $gmtDay;
        $h = $gmtHour;

        switch ((int)$timezoneid) {
            // North America since 2007: second Sunday in March to first Sunday in November.
            case 4:  // Alaska
            case 5:  // Pacific Time (US & Canada); Tijuana
            case 8:  // Mountain Time (US & Canada)
            case 10: // Central Time (US & Canada)
            case 11: // Guadalajara, Mexico City, Monterrey
            case 14: // Eastern Time (US & Canada)
            case 16: // Atlantic Time (Canada)
            case 19: // Newfoundland
                return self::afterSecondDayInMonth($curYear, $curYear, 3, 'Sun', $gmtOffset, $m, $d, $h)
                    && self::beforeFirstDayInMonth($curYear, $curYear, 11, 'Sun', $gmtOffset, $m, $d, $h);

            case 7: // Chihuahua, La Paz, Mazatlan
                return self::afterFirstDayInMonth($curYear, $curYear, 5, 'Sun', $gmtOffset, $m, $d, $h)
                    && self::beforeLastDayInMonth($curYear, $curYear, 9, 'Sun', $gmtOffset, $m, $d, $h);

            case 18: // Santiago, Chile: first Sunday in September to first Sunday in April
                return (self::afterFirstDayInMonth($curYear, $curYear, 9, 'Sun', $gmtOffset, $m, $d, $h)
                        && self::beforeFirstDayInMonth($curYear, $curYear + 1, 4, 'Sun', $gmtOffset, $m, $d, $h))
                    || (self::afterFirstDayInMonth($curYear, $curYear - 1, 9, 'Sun', $gmtOffset, $m, $d, $h)
                        && self::beforeFirstDayInMonth($curYear, $curYear, 4, 'Sun', $gmtOffset, $m, $d, $h));

            case 20: // Brasilia: first Sunday in November to third Sunday in February
                return (self::afterFirstDayInMonth($curYear, $curYear, 11, 'Sun', $gmtOffset, $m, $d, $h)
                        && self::beforeThirdDayInMonth($curYear, $curYear + 1, 2, 'Sun', $gmtOffset, $m, $d, $h))
                    || (self::afterFirstDayInMonth($curYear, $curYear - 1, 11, 'Sun', $gmtOffset, $m, $d, $h)
                        && self::beforeThirdDayInMonth($curYear, $curYear, 2, 'Sun', $gmtOffset, $m, $d, $h));

            case 23: // Mid-Atlantic
                return self::afterLastDayInMonth($curYear, $curYear, 3, 'Sun', $gmtOffset, $m, $d, $h)
                    && self::beforeLastDayInMonth($curYear, $curYear, 9, 'Sun', $gmtOffset, $m, $d, $h);

            // EU, Russia and others: last Sunday in March to last Sunday in October.
            case 22: // Greenland
            case 24: // Azores
            case 27: // Dublin, Edinburgh, Lisbon, London
            case 28: // Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna
            case 29: // Belgrade, Bratislava, Budapest, Ljubljana, Prague
            case 30: // Brussels, Copenhagen, Madrid, Paris
            case 31: // Sarajevo, Skopje, Warsaw, Zagreb
            case 33: // Athens, Istanbul, Minsk
            case 34: // Bucharest
            case 37: // Helsinki, Kyiv, Riga, Sofia, Tallinn, Vilnius
            case 41: // Moscow, St. Petersburg, Volgograd
            case 47: // Ekaterinburg
            case 45: // Baku, Tbilisi, Yerevan
            case 51: // Almaty, Novosibirsk
            case 56: // Krasnoyarsk
            case 58: // Irkutsk, Ulaan Bataar
            case 64: // Yakutsk
            case 71: // Vladivostok
                return self::afterLastDayInMonth($curYear, $curYear, 3, 'Sun', $gmtOffset, $m, $d, $h)
                    && self::beforeLastDayInMonth($curYear, $curYear, 10, 'Sun', $gmtOffset, $m, $d, $h);

            case 35: // Cairo
                return self::afterLastDayInMonth($curYear, $curYear, 4, 'Fri', $gmtOffset, $m, $d, $h)
                    && self::beforeLastDayInMonth($curYear, $curYear, 9, 'Thu', $gmtOffset, $m, $d, $h);

            case 39: // Baghdad
                return self::afterFirstOfTheMonth($curYear, $curYear, 4, $gmtOffset, $m, $d, $h)
                    && self::beforeFirstOfTheMonth($curYear, $curYear, 10, $gmtOffset, $m, $d, $h);

            case 43: // Tehran (approximation, as in the old code)
                return self::afterLastDayInMonth($curYear, $curYear, 3, 'Sun', $gmtOffset, $m, $d, $h)
                    && self::beforeLastDayInMonth($curYear, $curYear, 9, 'Sun', $gmtOffset, $m, $d, $h);

            case 65: // Adelaide
            case 68: // Canberra, Melbourne, Sydney: last Sunday in October to last Sunday in March
                return (self::afterLastDayInMonth($curYear, $curYear, 10, 'Sun', $gmtOffset, $m, $d, $h)
                        && self::beforeLastDayInMonth($curYear, $curYear + 1, 3, 'Sun', $gmtOffset, $m, $d, $h))
                    || (self::afterLastDayInMonth($curYear, $curYear - 1, 10, 'Sun', $gmtOffset, $m, $d, $h)
                        && self::beforeLastDayInMonth($curYear, $curYear, 3, 'Sun', $gmtOffset, $m, $d, $h));

            case 70: // Hobart: first Sunday in October to last Sunday in March
                return (self::afterFirstDayInMonth($curYear, $curYear, 10, 'Sun', $gmtOffset, $m, $d, $h)
                        && self::beforeLastDayInMonth($curYear, $curYear + 1, 3, 'Sun', $gmtOffset, $m, $d, $h))
                    || (self::afterFirstDayInMonth($curYear, $curYear - 1, 10, 'Sun', $gmtOffset, $m, $d, $h)
                        && self::beforeLastDayInMonth($curYear, $curYear, 3, 'Sun', $gmtOffset, $m, $d, $h));

            case 73: // Auckland, Wellington: first Sunday in October to third Sunday in March
                return (self::afterFirstDayInMonth($curYear, $curYear, 10, 'Sun', $gmtOffset, $m, $d, $h)
                        && self::beforeThirdDayInMonth($curYear, $curYear + 1, 3, 'Sun', $gmtOffset, $m, $d, $h))
                    || (self::afterFirstDayInMonth($curYear, $curYear - 1, 10, 'Sun', $gmtOffset, $m, $d, $h)
                        && self::beforeThirdDayInMonth($curYear, $curYear, 3, 'Sun', $gmtOffset, $m, $d, $h));
        }

        return false;
    }

    /**
     * Year, month, day and hour of a 'Y-m-d H:i:s' / 'Y-m-d' value.
     *
     * @param mixed $date Date string or DateTimeInterface
     * @return array{0: int, 1: int, 2: int, 3: int}|null
     */
    public static function parts($date): ?array
    {
        if ($date instanceof \DateTimeInterface) {
            $date = $date->format('Y-m-d H:i:s');
        }
        if (!is_string($date) || !preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})(?:[ T](\d{1,2}))?/', $date, $m)) {
            return null;
        }

        return [(int)$m[1], (int)$m[2], (int)$m[3], isset($m[4]) ? (int)$m[4] : 12];
    }

    // --- Rules, as in kewico_php8 ---------------------------------------

    private static function currentStamp(int $curYear, float $gmtOffset, ?int $month, ?int $day, ?int $hourValue, bool $addOffset): int
    {
        if ($month !== null && $day !== null) {
            return mktime((int)$hourValue, 0, 0, $month, $day, $curYear);
        }
        $hour = (int)gmdate('H') + ($addOffset ? $gmtOffset : 0);

        return mktime((int)$hour, 0, 0, (int)gmdate('m'), (int)gmdate('d'), $curYear);
    }

    private static function nthWeekday(int $month, string $weekday, int $n): int
    {
        $count = 0;
        for ($i = 1; $i < 7 * $n + 1; $i++) {
            if (date('D', mktime(0, 0, 0, $month, $i)) === $weekday && ++$count === $n) {
                return $i;
            }
        }

        return 1;
    }

    private static function lastWeekday(int $month, string $weekday): int
    {
        $days = self::getDaysInMonth($month);
        for ($i = $days; $i > $days - 8; $i--) {
            if (date('D', mktime(0, 0, 0, $month, $i)) === $weekday) {
                return $i;
            }
        }

        return $days;
    }

    private static function afterFirstDayInMonth(int $curYear, int $year, int $month, string $weekday, float $gmtOffset, ?int $cm, ?int $cd, ?int $ch): bool
    {
        $cur = self::currentStamp($curYear, $gmtOffset, $cm, $cd, 0, true);

        return $cur >= mktime(2, 0, 0, $month, self::nthWeekday($month, $weekday, 1), $year);
    }

    private static function beforeLastDayInMonth(int $curYear, int $year, int $month, string $weekday, float $gmtOffset, ?int $cm, ?int $cd, ?int $ch): bool
    {
        $cur = self::currentStamp($curYear, $gmtOffset, $cm, $cd, (int)(($ch ?? 12) + $gmtOffset), true);

        return $cur < mktime(2, 0, 0, $month, self::lastWeekday($month, $weekday), $year);
    }

    private static function afterLastDayInMonth(int $curYear, int $year, int $month, string $weekday, float $gmtOffset, ?int $cm, ?int $cd, ?int $ch): bool
    {
        // All EU countries switch at 1 am GMT, so no offset here.
        $cur = self::currentStamp($curYear, $gmtOffset, $cm, $cd, $ch ?? 12, false);

        return $cur >= mktime(1, 0, 0, $month, self::lastWeekday($month, $weekday), $year);
    }

    private static function afterFirstOfTheMonth(int $curYear, int $year, int $month, float $gmtOffset, ?int $cm, ?int $cd, ?int $ch): bool
    {
        $cur = self::currentStamp($curYear, $gmtOffset, $cm, $cd, 0, true);

        return $cur >= mktime(3, 0, 0, $month, 1, $year);
    }

    private static function beforeFirstOfTheMonth(int $curYear, int $year, int $month, float $gmtOffset, ?int $cm, ?int $cd, ?int $ch): bool
    {
        $cur = self::currentStamp($curYear, $gmtOffset, $cm, $cd, 0, true);

        return $cur < mktime(3, 0, 0, $month, 1, $year);
    }

    private static function beforeThirdDayInMonth(int $curYear, int $year, int $month, string $weekday, float $gmtOffset, ?int $cm, ?int $cd, ?int $ch): bool
    {
        $cur = self::currentStamp($curYear, $gmtOffset, $cm, $cd, 0, true);

        return $cur < mktime(2, 0, 0, $month, self::nthWeekday($month, $weekday, 3), $year);
    }

    private static function afterSecondDayInMonth(int $curYear, int $year, int $month, string $weekday, float $gmtOffset, ?int $cm, ?int $cd, ?int $ch): bool
    {
        $cur = self::currentStamp($curYear, $gmtOffset, $cm, $cd, 0, true);

        return $cur >= mktime(2, 0, 0, $month, self::nthWeekday($month, $weekday, 2), $year);
    }

    private static function beforeFirstDayInMonth(int $curYear, int $year, int $month, string $weekday, float $gmtOffset, ?int $cm, ?int $cd, ?int $ch): bool
    {
        $cur = self::currentStamp($curYear, $gmtOffset, $cm, $cd, 0, true);

        return $cur < mktime(2, 0, 0, $month, self::nthWeekday($month, $weekday, 1), $year);
    }

    private static function getDaysInMonth(int $month): int
    {
        switch ($month) {
            case 2:
                return date('L') ? 29 : 28;
            case 1:
            case 3:
            case 5:
            case 7:
            case 8:
            case 10:
            case 12:
                return 31;
        }

        return 30;
    }
}
