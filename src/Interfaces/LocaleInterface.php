<?php

namespace Hubleto\Framework\Interfaces;

interface LocaleInterface
{

  public function getTimezone(): string;
  public function getDateShortFormat(): string;
  public function getDateLongFormat(): string;
  public function getDatetimeFormat(): string;
  public function getTimeFormat(bool $addSeconds = true): string;
  public function getCurrencySymbol(): string;
  public function getCurrencyIsoCode(): string;
  public function getAll(string $keyBy = ""): array;
  public function formatCurrency(string|float $value, string $symbol = ''): string;
  public function formatDateShort(string|int $dateOrTimestamp): string;
  public function formatDateLong(string|int $dateOrTimestamp): string;
  public function formatDatetime(string|int $datetimeOrTimestamp): string;
  public function formatTime(string|int $timeOrTimestamp, bool $addSeconds = true): string;

}