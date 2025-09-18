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
    return "Y-m-d";
  }

  /**
   * [Description for getDateLongFormat]
   *
   * @return string
   * 
   */
  public function getDateLongFormat(): string
  {
    return "jS F Y";
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
   * [Description for getCurrencySymbol]
   *
   * @return string
   * 
   */
  public function getCurrencySymbol(): string
  {
    $symbol = $this->config()->getAsString('locale/currency/symbol');
    return empty($symbol) ? "€" : $symbol;
  }

  /**
   * [Description for getCurrencyIsoCode]
   *
   * @return string
   * 
   */
  public function getCurrencyIsoCode(): string
  {
    $isoCode = $this->config()->getAsString('locale/currency/isoCode');
    return empty($isoCode) ? "EUR" : $isoCode;
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
      "currencySymbol" => $this->getCurrencySymbol(),
      "currencyIsoCode" => $this->getCurrencyIsoCode(),
    ];
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
    return number_format((float) $value, 2, ",", " ") . ' ' . $symbol;
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
    return $ts . '-' . date($this->getDateShortFormat(), $ts);
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