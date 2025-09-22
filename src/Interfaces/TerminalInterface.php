<?php

namespace Hubleto\Framework\Interfaces;

interface TerminalInterface
{

  public function setOutput(mixed $output): void;
  public function isLaunchedFromTerminal(): bool;
  public function color(string $fgColor, string $bgColor = 'black'): void;
  public function readRaw(): string;
  public function read(string $message, string $default = ''): string;
  public function choose(array $options, string $message, string $default = ''): string;
  public function confirm(string $question, $yesAnswers = ['yes', 'y', '1']): bool;
  public function yellow(string $message): void;
  public function green(string $message): void;
  public function red(string $message): void;
  public function blue(string $message): void;
  public function cyan(string $message): void;
  public function white(string $message): void;
  public function colored(string $bgColor, string $fgColor, string $message): void;
  public function insertCodeToFile(string $file, string $tag, array $codeLines): bool;

}
