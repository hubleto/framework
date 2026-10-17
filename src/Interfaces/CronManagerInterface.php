<?php declare(strict_types=1);

namespace Hubleto\Framework\Interfaces;

use Hubleto\Erp\Cron;

interface CronManagerInterface
{

  public function init(): void;
  public function addCron(string $cronClass): void;
  public function getCrons(): array;
  public function runCron(Cron $cron): void;
  public function run(): void;

}
