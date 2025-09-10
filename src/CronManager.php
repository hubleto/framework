<?php declare(strict_types=1);

namespace Hubleto\Framework;

/**
 * Default manager for scheduled jobs (cron) in Hubleto project.
 */
class CronManager extends Core implements Interfaces\CronManagerInterface
{

  /** @var array<\Hubleto\Erp\Cron> */
  protected array $enabledCrons = [];

  public function init(): void
  {
    $crons = @Helper::scanDirRecursively($this->env()->srcFolder . '/crons');
    foreach ($crons as $cron) {
      if (!\str_ends_with($cron, '.php')) continue;
      $cronClass = '\\Hubleto\\Erp\\Cron\\' . str_replace('/', '\\', $cron);
      $cronClass = str_replace('.php', '', $cronClass);
      $this->addCron($cronClass);
    }

    $crons = @Helper::scanDirRecursively($this->env()->projectFolder . '/src/crons');
    foreach ($crons as $cron) {
      if (!\str_ends_with($cron, '.php')) continue;
      $cronClass = '\\HubletoProject\\Cron\\' . str_replace('/', '\\', $cron);
      $cronClass = str_replace('.php', '', $cronClass);
      $this->addCron($cronClass);
    }
  }

  public function log(string $msg): void
  {
    $this->logger()->info($msg);
  }

  public function addCron(string $cronClass): void
  {
    if (is_subclass_of($cronClass, \Hubleto\Erp\Cron::class)) {
      $this->enabledCrons[$cronClass] = $this->getService($cronClass);
    }
  }

  public function getCrons(): array
  {
    return $this->enabledCrons;
  }

  public function run(): void
  {
    foreach ($this->getCrons() as $cronClass => $cron) {
      $schedule = explode(' ', $cron->schedulingPattern);

      $minNow = (int) date('i');
      $hourNow = (int) date('H');
      $dayNow = (int) date('d');
      $monthNow = (int) date('m');
      $dowNow = (int) (date('N') - 1);

      $minSchedule = trim($schedule[0]);
      $hourSchedule = trim($schedule[1]);
      $daySchedule = trim($schedule[2]);
      $monthSchedule = trim($schedule[3]);
      $dowSchedule = trim($schedule[4]);

      $minMatch = false;
      $hourMatch = false;
      $dayMatch = false;
      $monthMatch = false;
      $dowMatch = false;

      if ($minSchedule == '*') {
        $minMatch = true;
      } elseif (str_starts_with($minSchedule, '*/')) {
        $minMatch = $minNow % ((int) str_replace('*/', '', $minSchedule)) == 0;
      } else {
        $minMatch = $minNow == (int) $minSchedule;
      }

      if ($hourSchedule == '*') {
        $hourMatch = true;
      } elseif (str_starts_with($hourSchedule, '*/')) {
        $hourMatch = $hourNow % ((int) str_replace('*/', '', $hourSchedule)) == 0;
      } else {
        $hourMatch = $hourNow == (int) $hourSchedule;
      }

      if ($daySchedule == '*') {
        $dayMatch = true;
      } elseif (str_starts_with($daySchedule, '*/')) {
        $dayMatch = $dayNow % ((int) str_replace('*/', '', $daySchedule)) == 0;
      } else {
        $dayMatch = $dayNow == (int) $daySchedule;
      }

      if ($monthSchedule == '*') {
        $monthMatch = true;
      } elseif (str_starts_with($monthSchedule, '*/')) {
        $monthMatch = $monthNow % ((int) str_replace('*/', '', $monthSchedule)) == 0;
      } else {
        $monthMatch = $monthNow == (int) $monthSchedule;
      }

      if ($dowSchedule == '*') {
        $dowMatch = true;
      } elseif (str_starts_with($dowSchedule, '*/')) {
        $dowMatch = $dowNow % ((int) str_replace('*/', '', $dowSchedule)) == 0;
      } else {
        $dowMatch = $dowNow == (int) $dowSchedule;
      }

      if ($minMatch && $hourMatch && $dayMatch && $monthMatch && $dowMatch) {
        $this->log('Starting `' . $cronClass . '`.');
        try {
          $cron->run();
        } catch (\Throwable $e) {
          $this->log('Cron `' . $cronClass . '` failed.');
          $this->log('EXCEPTION: ' . $e->getMessage());
        }
      }
    }
  }

}
