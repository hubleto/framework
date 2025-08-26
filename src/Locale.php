<?php

namespace Hubleto\Framework;

class Locale extends CoreClass
{

  private array $locale = [];
  
  public function __construct(public \Hubleto\Framework\Loader $main) {
    parent::__construct($main);
    $this->locale = $this->getConfig()->getAsArray('locale');
  }

  public function getDateShortFormat(): string
  {
    return "Y-m-d";
  }

  public function getDateLongFormat(): string
  {
    return "jS F Y";
  }

  public function getDatetimeFormat(): string
  {
    return "Y-m-d H:i:s";
  }

  public function getTimeFormat(bool $addSeconds = true): string
  {
    return "H:i" . ($addSeconds ? ":s" : "");
  }

  public function getCurrencySymbol(): string
  {
    return "€";
  }

  public function getAll(string $keyBy = "") {
    return [
      "dateShortFormat" => $this->getDateShortFormat(),
      "dateLongFormat" => $this->getDateLongFormat(),
      "timeFormat" => $this->getTimeFormat(),
      "datetimeFormat" => $this->getDatetimeFormat(),
      "currencySymbol" => $this->getCurrencySymbol(),
    ];
  }

  public function formatCurrency(string|float $value, string $symbol = ''): string
  {
    if ($symbol == '') $symbol = $this->getCurrencySymbol();
    return number_format((float) $value, 2, ",", " ") . ' ' . $symbol;
  }

  public function formatDateShort(string|int $dateOrTimestamp): string
  {
    if (is_string($dateOrTimestamp)) $ts = strtotime($dateOrTimestamp);
    else $ts = $dateOrTimestamp;
    return $ts . '-' . date($this->getDateShortFormat(), $ts);
  }

  public function formatDateLong(string|int $dateOrTimestamp): string
  {
    if (is_string($dateOrTimestamp)) $ts = strtotime($dateOrTimestamp);
    else $ts = $dateOrTimestamp;
    return date($this->getDateLongFormat(), $ts);
  }

  public function formatDatetime(string|int $datetimeOrTimestamp): string
  {
    if (is_string($datetimeOrTimestamp)) $ts = strtotime($datetimeOrTimestamp);
    else $ts = $datetimeOrTimestamp;
    return date($this->getDatetimeFormat(), $ts);
  }

  public function formatTime(string|int $timeOrTimestamp, bool $addSeconds = true): string
  {
    if (is_string($timeOrTimestamp)) $ts = strtotime($timeOrTimestamp);
    else $ts = $timeOrTimestamp;
    return date($this->getTimeFormat($addSeconds), $ts);
  }

}