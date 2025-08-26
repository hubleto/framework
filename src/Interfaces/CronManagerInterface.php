<?php declare(strict_types=1);

namespace Hubleto\Framework\Interfaces;

interface CronManagerInterface
{

  public function init(): void;
  public function log(string $msg): void;
  public function addCron(string $cronClass): void;
  public function getCrons(): array;
  public function run(): void;

}
