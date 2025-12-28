<?php

namespace Hubleto\Framework;

/**
 * Methods to support locale in Hubleto project.
 */
class Locale extends Core implements Interfaces\LocaleInterface
{

  private array $locale = [];
  
  public function __construct()
  {
    parent::__construct();
    $this->locale = $this->config()->getAsArray('locale');
  }

  public function getTimezones(): array
  {
    return [
      'Africa/Abidjan',
      'Africa/Accra',
      'Africa/Addis_Ababa',
      'Africa/Algiers',
      'Africa/Asmara',
      'Africa/Bamako',
      'Africa/Bangui',
      'Africa/Banjul',
      'Africa/Bissau',
      'Africa/Blantyre',
      'Africa/Brazzaville',
      'Africa/Bujumbura',
      'Africa/Cairo',
      'Africa/Casablanca',
      'Africa/Ceuta',
      'Africa/Conakry',
      'Africa/Dakar',
      'Africa/Dar_es_Salaam',
      'Africa/Djibouti',
      'Africa/Douala',
      'Africa/El_Aaiun',
      'Africa/Freetown',
      'Africa/Gaborone',
      'Africa/Harare',
      'Africa/Johannesburg',
      'Africa/Juba',
      'Africa/Kampala',
      'Africa/Khartoum',
      'Africa/Kigali',
      'Africa/Kinshasa',
      'Africa/Lagos',
      'Africa/Libreville',
      'Africa/Lome',
      'Africa/Luanda',
      'Africa/Lubumbashi',
      'Africa/Lusaka',
      'Africa/Malabo',
      'Africa/Maputo',
      'Africa/Maseru',
      'Africa/Mbabane',
      'Africa/Mogadishu',
      'Africa/Monrovia',
      'Africa/Nairobi',
      'Africa/Ndjamena',
      'Africa/Niamey',
      'Africa/Nouakchott',
      'Africa/Ouagadougou',
      'Africa/Porto-Novo',
      'Africa/Sao_Tome',
      'Africa/Tripoli',
      'Africa/Tunis',
      'Africa/Windhoek',
      'America/Adak',
      'America/Anchorage',
      'America/Anguilla',
      'America/Antigua',
      'America/Araguaina',
      'America/Argentina/Buenos_Aires',
      'America/Argentina/Catamarca',
      'America/Argentina/Cordoba',
      'America/Argentina/Jujuy',
      'America/Argentina/La_Rioja',
      'America/Argentina/Mendoza',
      'America/Argentina/Rio_Gallegos',
      'America/Argentina/Salta',
      'America/Argentina/San_Juan',
      'America/Argentina/San_Luis',
      'America/Argentina/Tucuman',
      'America/Argentina/Ushuaia',
      'America/Aruba',
      'America/Asuncion',
      'America/Atikokan',
      'America/Bahia',
      'America/Bahia_Banderas',
      'America/Barbados',
      'America/Belem',
      'America/Belize',
      'America/Blanc-Sablon',
      'America/Boa_Vista',
      'America/Bogota',
      'America/Boise',
      'America/Cambridge_Bay',
      'America/Campo_Grande',
      'America/Cancun',
      'America/Caracas',
      'America/Cayenne',
      'America/Cayman',
      'America/Chicago',
      'America/Chihuahua',
      'America/Ciudad_Juarez',
      'America/Costa_Rica',
      'America/Coyhaique',
      'America/Creston',
      'America/Cuiaba',
      'America/Curacao',
      'America/Danmarkshavn',
      'America/Dawson',
      'America/Dawson_Creek',
      'America/Denver',
      'America/Detroit',
      'America/Dominica',
      'America/Edmonton',
      'America/Eirunepe',
      'America/El_Salvador',
      'America/Fort_Nelson',
      'America/Fortaleza',
      'America/Glace_Bay',
      'America/Goose_Bay',
      'America/Grand_Turk',
      'America/Grenada',
      'America/Guadeloupe',
      'America/Guatemala',
      'America/Guayaquil',
      'America/Guyana',
      'America/Halifax',
      'America/Havana',
      'America/Hermosillo',
      'America/Indiana/Indianapolis',
      'America/Indiana/Knox',
      'America/Indiana/Marengo',
      'America/Indiana/Petersburg',
      'America/Indiana/Tell_City',
      'America/Indiana/Vevay',
      'America/Indiana/Vincennes',
      'America/Indiana/Winamac',
      'America/Inuvik',
      'America/Iqaluit',
      'America/Jamaica',
      'America/Juneau',
      'America/Kentucky/Louisville',
      'America/Kentucky/Monticello',
      'America/Kralendijk',
      'America/La_Paz',
      'America/Lima',
      'America/Los_Angeles',
      'America/Lower_Princes',
      'America/Maceio',
      'America/Managua',
      'America/Manaus',
      'America/Marigot',
      'America/Martinique',
      'America/Matamoros',
      'America/Mazatlan',
      'America/Menominee',
      'America/Merida',
      'America/Metlakatla',
      'America/Mexico_City',
      'America/Miquelon',
      'America/Moncton',
      'America/Monterrey',
      'America/Montevideo',
      'America/Montserrat',
      'America/Nassau',
      'America/New_York',
      'America/Nome',
      'America/Noronha',
      'America/North_Dakota/Beulah',
      'America/North_Dakota/Center',
      'America/North_Dakota/New_Salem',
      'America/Nuuk',
      'America/Ojinaga',
      'America/Panama',
      'America/Paramaribo',
      'America/Phoenix',
      'America/Port-au-Prince',
      'America/Port_of_Spain',
      'America/Porto_Velho',
      'America/Puerto_Rico',
      'America/Punta_Arenas',
      'America/Rankin_Inlet',
      'America/Recife',
      'America/Regina',
      'America/Resolute',
      'America/Rio_Branco',
      'America/Santarem',
      'America/Santiago',
      'America/Santo_Domingo',
      'America/Sao_Paulo',
      'America/Scoresbysund',
      'America/Sitka',
      'America/St_Barthelemy',
      'America/St_Johns',
      'America/St_Kitts',
      'America/St_Lucia',
      'America/St_Thomas',
      'America/St_Vincent',
      'America/Swift_Current',
      'America/Tegucigalpa',
      'America/Thule',
      'America/Tijuana',
      'America/Toronto',
      'America/Tortola',
      'America/Vancouver',
      'America/Whitehorse',
      'America/Winnipeg',
      'America/Yakutat',
      'Antarctica/Casey',
      'Antarctica/Davis',
      'Antarctica/DumontDUrville',
      'Antarctica/Macquarie',
      'Antarctica/Mawson',
      'Antarctica/McMurdo',
      'Antarctica/Palmer',
      'Antarctica/Rothera',
      'Antarctica/Syowa',
      'Antarctica/Troll',
      'Antarctica/Vostok',
      'Arctic/Longyearbyen',
      'Asia/Aden',
      'Asia/Almaty',
      'Asia/Amman',
      'Asia/Anadyr',
      'Asia/Aqtau',
      'Asia/Aqtobe',
      'Asia/Ashgabat',
      'Asia/Atyrau',
      'Asia/Baghdad',
      'Asia/Bahrain',
      'Asia/Baku',
      'Asia/Bangkok',
      'Asia/Barnaul',
      'Asia/Beirut',
      'Asia/Bishkek',
      'Asia/Brunei',
      'Asia/Chita',
      'Asia/Colombo',
      'Asia/Damascus',
      'Asia/Dhaka',
      'Asia/Dili',
      'Asia/Dubai',
      'Asia/Dushanbe',
      'Asia/Famagusta',
      'Asia/Gaza',
      'Asia/Hebron',
      'Asia/Ho_Chi_Minh',
      'Asia/Hong_Kong',
      'Asia/Hovd',
      'Asia/Irkutsk',
      'Asia/Jakarta',
      'Asia/Jayapura',
      'Asia/Jerusalem',
      'Asia/Kabul',
      'Asia/Kamchatka',
      'Asia/Karachi',
      'Asia/Kathmandu',
      'Asia/Khandyga',
      'Asia/Kolkata',
      'Asia/Krasnoyarsk',
      'Asia/Kuala_Lumpur',
      'Asia/Kuching',
      'Asia/Kuwait',
      'Asia/Macau',
      'Asia/Magadan',
      'Asia/Makassar',
      'Asia/Manila',
      'Asia/Muscat',
      'Asia/Nicosia',
      'Asia/Novokuznetsk',
      'Asia/Novosibirsk',
      'Asia/Omsk',
      'Asia/Oral',
      'Asia/Phnom_Penh',
      'Asia/Pontianak',
      'Asia/Pyongyang',
      'Asia/Qatar',
      'Asia/Qostanay',
      'Asia/Qyzylorda',
      'Asia/Riyadh',
      'Asia/Sakhalin',
      'Asia/Samarkand',
      'Asia/Seoul',
      'Asia/Shanghai',
      'Asia/Singapore',
      'Asia/Srednekolymsk',
      'Asia/Taipei',
      'Asia/Tashkent',
      'Asia/Tbilisi',
      'Asia/Tehran',
      'Asia/Thimphu',
      'Asia/Tokyo',
      'Asia/Tomsk',
      'Asia/Ulaanbaatar',
      'Asia/Urumqi',
      'Asia/Ust-Nera',
      'Asia/Vientiane',
      'Asia/Vladivostok',
      'Asia/Yakutsk',
      'Asia/Yangon',
      'Asia/Yekaterinburg',
      'Asia/Yerevan',
      'Atlantic/Azores',
      'Atlantic/Bermuda',
      'Atlantic/Canary',
      'Atlantic/Cape_Verde',
      'Atlantic/Faroe',
      'Atlantic/Madeira',
      'Atlantic/Reykjavik',
      'Atlantic/South_Georgia',
      'Atlantic/St_Helena',
      'Atlantic/Stanley',
      'Atlantic/Azores',
      'Atlantic/Bermuda',
      'Atlantic/Canary',
      'Atlantic/Cape_Verde',
      'Atlantic/Faroe',
      'Atlantic/Madeira',
      'Atlantic/Reykjavik',
      'Atlantic/South_Georgia',
      'Atlantic/St_Helena',
      'Atlantic/Stanley',
      'Europe/Amsterdam',
      'Europe/Andorra',
      'Europe/Astrakhan',
      'Europe/Athens',
      'Europe/Belgrade',
      'Europe/Berlin',
      'Europe/Bratislava',
      'Europe/Brussels',
      'Europe/Bucharest',
      'Europe/Budapest',
      'Europe/Busingen',
      'Europe/Chisinau',
      'Europe/Copenhagen',
      'Europe/Dublin',
      'Europe/Gibraltar',
      'Europe/Guernsey',
      'Europe/Helsinki',
      'Europe/Isle_of_Man',
      'Europe/Istanbul',
      'Europe/Jersey',
      'Europe/Kaliningrad',
      'Europe/Kirov',
      'Europe/Kyiv',
      'Europe/Lisbon',
      'Europe/Ljubljana',
      'Europe/London',
      'Europe/Luxembourg',
      'Europe/Madrid',
      'Europe/Malta',
      'Europe/Mariehamn',
      'Europe/Minsk',
      'Europe/Monaco',
      'Europe/Moscow',
      'Europe/Oslo',
      'Europe/Paris',
      'Europe/Podgorica',
      'Europe/Prague',
      'Europe/Riga',
      'Europe/Rome',
      'Europe/Samara',
      'Europe/San_Marino',
      'Europe/Sarajevo',
      'Europe/Saratov',
      'Europe/Simferopol',
      'Europe/Skopje',
      'Europe/Sofia',
      'Europe/Stockholm',
      'Europe/Tallinn',
      'Europe/Tirane',
      'Europe/Ulyanovsk',
      'Europe/Vaduz',
      'Europe/Vatican',
      'Europe/Vienna',
      'Europe/Vilnius',
      'Europe/Volgograd',
      'Europe/Warsaw',
      'Europe/Zagreb',
      'Europe/Zurich',
      'Indian/Antananarivo',
      'Indian/Chagos',
      'Indian/Christmas',
      'Indian/Cocos',
      'Indian/Comoro',
      'Indian/Kerguelen',
      'Indian/Mahe',
      'Indian/Maldives',
      'Indian/Mauritius',
      'Indian/Mayotte',
      'Indian/Reunion',
      'Pacific/Apia',
      'Pacific/Auckland',
      'Pacific/Bougainville',
      'Pacific/Chatham',
      'Pacific/Chuuk',
      'Pacific/Easter',
      'Pacific/Efate',
      'Pacific/Fakaofo',
      'Pacific/Fiji',
      'Pacific/Funafuti',
      'Pacific/Galapagos',
      'Pacific/Gambier',
      'Pacific/Guadalcanal',
      'Pacific/Guam',
      'Pacific/Honolulu',
      'Pacific/Kanton',
      'Pacific/Kiritimati',
      'Pacific/Kosrae',
      'Pacific/Kwajalein',
      'Pacific/Majuro',
      'Pacific/Marquesas',
      'Pacific/Midway',
      'Pacific/Nauru',
      'Pacific/Niue',
      'Pacific/Norfolk',
      'Pacific/Noumea',
      'Pacific/Pago_Pago',
      'Pacific/Palau',
      'Pacific/Pitcairn',
      'Pacific/Pohnpei',
      'Pacific/Port_Moresby',
      'Pacific/Rarotonga',
      'Pacific/Saipan',
      'Pacific/Tahiti',
      'Pacific/Tarawa',
      'Pacific/Tongatapu',
      'Pacific/Wake',
      'Pacific/Wallis',
    ];
  }

  /**
   * [Description for getTimezone]
   *
   * @return string
   * 
   */
  public function getTimezone(): string
  {
    return $this->config()->getAsString('locale/timezone', 'Europe/London');
  }

  /**
   * [Description for getDateShortFormat]
   *
   * @return string
   * 
   */
  public function getDateShortFormat(): string
  {
    return $this->config()->getAsString('locale/dateShort', 'Y-m-d');
  }

  /**
   * [Description for getDateLongFormat]
   *
   * @return string
   * 
   */
  public function getDateLongFormat(): string
  {
    return $this->config()->getAsString('locale/dateLong', 'jS F Y');
  }

  /**
   * [Description for getDatetimeFormat]
   *
   * @return string
   * 
   */
  public function getDatetimeFormat(): string
  {
    return "Y-m-d H:i:s";
  }

  /**
   * [Description for getTimeFormat]
   *
   * @param bool $addSeconds
   * 
   * @return string
   * 
   */
  public function getTimeFormat(bool $addSeconds = true): string
  {
    return "H:i" . ($addSeconds ? ":s" : "");
  }

  /**
   * [Description for getDecimalsSeparator]
   *
   * @return string
   * 
   */
  public function getDecimalsSeparator(): string
  {
    return $this->config()->getAsString('locale/decimalsSeparator', '.');
  }

  /**
   * [Description for getThousandsSeparator]
   *
   * @return string
   * 
   */
  public function getThousandsSeparator(): string
  {
    return $this->config()->getAsString('locale/thousandsSeparator', ' ');
  }

  /**
   * [Description for getCurrencySymbol]
   *
   * @return string
   * 
   */
  public function getCurrencySymbol(): string
  {
    return $this->config()->getAsString('locale/currencySymbol', '€');
  }

  /**
   * [Description for getCurrencyIsoCode]
   *
   * @return string
   * 
   */
  public function getCurrencyIsoCode(): string
  {
    return $this->config()->getAsString('locale/currencyIsoCode', 'EUR');
  }

  /**
   * [Description for getAll]
   *
   * @param string $keyBy
   * 
   * @return array
   * 
   */
  public function getAll(string $keyBy = ""): array
  {
    return [
      "dateShortFormat" => $this->getDateShortFormat(),
      "dateLongFormat" => $this->getDateLongFormat(),
      "timeFormat" => $this->getTimeFormat(),
      "datetimeFormat" => $this->getDatetimeFormat(),
      "decimalsSeparator" => $this->getDecimalsSeparator(),
      "thousandsSeparator" => $this->getThousandsSeparator(),
      "currencySymbol" => $this->getCurrencySymbol(),
      "currencyIsoCode" => $this->getCurrencyIsoCode(),
    ];
  }

  /**
   * [Description for formatNumber]
   *
   * @param string|float $value
   * @param string $symbol
   * 
   * @return string
   * 
   */
  public function formatNumber(string|float $value, int $decimals): string
  {
    $decimalsSeparator = $this->getDecimalsSeparator();
    $thousandsSeparator = $this->getThousandsSeparator();
    return number_format((float) $value, $decimals, $decimalsSeparator, $thousandsSeparator);
  }

  /**
   * [Description for formatCurrency]
   *
   * @param string|float $value
   * @param string $symbol
   * 
   * @return string
   * 
   */
  public function formatCurrency(string|float $value, string $symbol = ''): string
  {
    if ($symbol == '') $symbol = $this->getCurrencySymbol();
    return $this->formatNumber($value, 2) . ' ' . $symbol;
  }

  /**
   * [Description for formatDateShort]
   *
   * @param string|int $dateOrTimestamp
   * 
   * @return string
   * 
   */
  public function formatDateShort(string|int $dateOrTimestamp): string
  {
    if (is_string($dateOrTimestamp)) $ts = strtotime($dateOrTimestamp);
    else $ts = $dateOrTimestamp;
    return date($this->getDateShortFormat(), $ts);
  }

  /**
   * [Description for formatDateLong]
   *
   * @param string|int $dateOrTimestamp
   * 
   * @return string
   * 
   */
  public function formatDateLong(string|int $dateOrTimestamp): string
  {
    if (is_string($dateOrTimestamp)) $ts = strtotime($dateOrTimestamp);
    else $ts = $dateOrTimestamp;
    return date($this->getDateLongFormat(), $ts);
  }

  /**
   * [Description for formatDatetime]
   *
   * @param string|int $datetimeOrTimestamp
   * 
   * @return string
   * 
   */
  public function formatDatetime(string|int $datetimeOrTimestamp): string
  {
    if (is_string($datetimeOrTimestamp)) $ts = strtotime($datetimeOrTimestamp);
    else $ts = $datetimeOrTimestamp;
    return date($this->getDatetimeFormat(), $ts);
  }

  /**
   * [Description for formatTime]
   *
   * @param string|int $timeOrTimestamp
   * @param bool $addSeconds
   * 
   * @return string
   * 
   */
  public function formatTime(string|int $timeOrTimestamp, bool $addSeconds = true): string
  {
    if (is_string($timeOrTimestamp)) $ts = strtotime($timeOrTimestamp);
    else $ts = $timeOrTimestamp;
    return date($this->getTimeFormat($addSeconds), $ts);
  }

}