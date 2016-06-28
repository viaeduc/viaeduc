<?php

namespace Rpe\PumBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RpeTimezoneType extends AbstractType
{
    const TRANSLATION_DOMAIN = 'rpe';

    /**
     * 
     * @see https://github.com/ryanzor/timezone-dropdown/blob/master/index.php
     */ 
    protected $timezones = array(
        'Pacific/Auckland' => 'Ligne de changement de date',
        'Pacific/Midway' => 'Îles Midway => Samoa',
        'US/Hawaii' => 'Hawaii',
        'US/Alaska' => 'Alaska',
        'US/Pacific' => 'Pacific Time (US & Canada)',
        'America/Tijuana' => 'Tijuana => Baja California',
        'America/Phoenix' => 'Arizona',
        'America/Chihuahua' => 'Chihuahua => La Paz => Mazatlan',
        'US/Mountain' => 'Mountain Time (US & Canada)',
        'America/Cancun' => 'Central America',
        'US/Central' => 'Central Time (US & Canada)',
        'America/Mexico_City' => 'Guadalajara => Mexico City => Monterrey',
        'Canada/Saskatchewan' => 'Saskatchewan',
        'America/Lima' => 'Bogota => Lima => Quito => Rio Branco',
        'US/Eastern' => 'Eastern Time (US & Canada)',
        'US/East-Indiana' => 'Indiana (Est)',
        'Canada/Atlantic' => 'Atlantic Time (Canada)',
        'America/Caracas' => 'Caracas => La Paz',
        'America/Manaus' => 'Manaus',
        'America/Santiago' => 'Santiago',
        'Canada/Newfoundland' => 'Newfoundland',
        'America/Sao_Paulo' => 'Brésil',
        'America/Argentina/Buenos_Aires' => 'Buenos Aires => Georgetown',
        'America/Godthab' => 'Greenland',
        'America/Montevideo' => 'Montevideo',
        'Atlantic/South_Georgia' => 'Mid-Atlantic',
        'Atlantic/Cape_Verde' => 'Îles Cap Vert',
        'Atlantic/Azores' => 'Azores',
        'Africa/Casablanca' => 'Casablanca => Monrovia => Reykjavik',
        'UTC' => 'Greenwich Mean Time : Dublin => Edinburgh => Lisbone => Londres',
        'Europe/Amsterdam' => 'Amsterdam => Berlin => Bern => Rome => Stockholm => Vienne',
        'Europe/Belgrade' => 'Belgrade => Bratislava => Budapest => Ljubljana => Prague',
        'Europe/Brussels' => 'Bruxelles => Copenhague => Madrid => Paris',
        'Europe/Sarajevo' => 'Sarajevo => Skopje => Varsovie => Zagreb',
        'Africa/Windhoek' => 'West Central Africa',
        'Asia/Amman' => 'Amman',
        'Europe/Athens' => 'Athènes => Bucharest => Istanbul',
        'Asia/Beirut' => 'Beyrouth',
        'Africa/Cairo' => 'Le Caire',
        'Africa/Harare' => 'Harare => Pretoria',
        'Europe/Helsinki' => 'Helsinki => Kyiv => Riga => Sofia => Tallinn => Vilnius',
        'Asia/Jerusalem' => 'Jerusalem',
        'Europe/Minsk' => 'Minsk',
        'Africa/Windhoek' => 'Windhoek',
        'Asia/Kuwait' => 'Kuwait => Riyadh => Baghdad',
        'Europe/Moscow' => 'Moscow => St. Petersburg => Volgograd',
        'Africa/Nairobi' => 'Nairobi',
        'Asia/Tbilisi' => 'Tbilisi',
        'Asia/Tehran' => 'Téhéran',
        'Asia/Muscat' => 'Abu Dhabi => Muscat',
        'Asia/Baku' => 'Baku',
        'Asia/Yerevan' => 'Yerevan',
        'Asia/Kabul' => 'Kabul',
        'Asia/Yekaterinburg' => 'Yekaterinburg',
        'Asia/Karachi' => 'Islamabad => Karachi => Tashkent',
        'Asia/Kolkata' => 'Sri Jayawardenepura',
        'Asia/Kolkata' => 'Chennai => Kolkata => Mumbai => New Delhi',
        'Asia/Kathmandu' => 'Kathmandu',
        'Asia/Almaty' => 'Almaty => Novosibirsk',
        'Asia/Dhaka' => 'Astana => Dhaka',
        'Asia/Rangoon' => 'Yangon (Rangoon)',
        'Asia/Bangkok' => 'Bangkok => Hanoi => Jakarta',
        'Asia/Krasnoyarsk' => 'Krasnoyarsk',
        'Asia/Shanghai' => 'Beijing => Chongqing => Hong Kong => Urumqi',
        'Asia/Singapore' => 'Kuala Lumpur => Singapour',
        'Asia/Irkutsk' => 'Irkutsk => Ulaan Bataar',
        'Australia/Perth' => 'Perth',
        'Asia/Taipei' => 'Taipei',
        'Asia/Tokyo' => 'Osaka => Sapporo => Tokyo',
        'Asia/Seoul' => 'Seoul',
        'Asia/Yakutsk' => 'Yakutsk',
        'Australia/Adelaide' => 'Adelaide',
        'Australia/Darwin' => 'Darwin',
        'Australia/Brisbane' => 'Brisbane',
        'Australia/Sydney' => 'Canberra => Melbourne => Sydney',
        'Australia/Hobart' => 'Hobart',
        'Pacific/Guam' => 'Guam => Port Moresby',
        'Asia/Vladivostok' => 'Vladivostok',
        'Asia/Magadan' => 'Magadan => Îles Solomon => Nouvelle Calédonie',
        'Pacific/Auckland' => 'Auckland => Wellington',
        'Pacific/Fiji' => 'Fiji => Kamchatka => Îles Marshall',
        'Pacific/Tongatapu' => 'Nuku\'alofa',
    );

    /**
      * 
      * This get's the timezone offset based on the olson code.
      * In this code it is used to find the offset between the given olson code and UTC, but can be used to convert other differences
      * 
      * @param string $remote_tz TZ string
      * @param string $origin_tz TZ string, defaults to UTC
      * @return int offset in seconds
      */
    private function ln_get_timezone_offset($remote_tz, $origin_tz = 'UTC') 
    {
        $origin_dtz = new \DateTimeZone($origin_tz);
        $remote_dtz = new \DateTimeZone($remote_tz);
        $origin_dt = new \DateTime("now", $origin_dtz);
        $remote_dt = new \DateTime("now", $remote_dtz);
        $offset = $remote_dtz->getOffset($remote_dt) - $origin_dtz->getOffset($origin_dt);
        return $offset;
    }
     
    /**
     * Converts a timezone difference to be displayed as GMT +/-
     * 
     * @param string $timezone TZ time
     * @return string text with GMT
     */
    private function ln_get_timezone_offset_text($timezone)
    {
        $time = $this->ln_get_timezone_offset($timezone);
        $minutesOffset = $time/60;
        $hours = floor(($minutesOffset)/60);
        $minutes = abs($minutesOffset%60);
        $minutesFormatted = sprintf('%02d', $minutes);
        $plus = '';
        if($time >= 0){
            $plus = '+';
        }
        $GMToff = 'GMT '.$plus.$hours.':'.$minutesFormatted;
        return $GMToff;
    }

    public function getTimezonesChoices()
    {
        $timezonesChoices = array();
        foreach ($this->timezones as $tzIdentifier => $tzLabel) {
            $timezonesChoices[$tzIdentifier] = '('.$this->ln_get_timezone_offset_text($tzIdentifier).') '.$tzLabel;
        }
        return $timezonesChoices;
    }


    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => $this->getTimezonesChoices(),
            'label'   => 'common.field.fuseau_horaire',
            'translation_domain' => self::TRANSLATION_DOMAIN
        ));
    }

    public function getParent()
    {
        return 'choice';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'rpe_timezone';
    }

}
