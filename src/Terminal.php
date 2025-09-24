<?php

namespace Hubleto\Framework;

class Terminal implements Interfaces\TerminalInterface
{

  public mixed $output = null;

  public function setOutput(mixed $output): void
  {
    $this->output = $output;
  }

  public function isLaunchedFromTerminal(): bool
  {
    return (php_sapi_name() === 'cli');
  }

  public function echo(string $string): void
  {
    if ($this->isLaunchedFromTerminal()) fwrite($this->output ?? STDOUT, $string);
    else echo $string;
  }

  /**
   * Print special strings setting a specified color
   *
   * @param string $fgColor
   * @param string $bgColor
   * 
   * @return void
   * 
   */
  public function color(string $fgColor, string $bgColor = 'black'): void
  {
    if (php_sapi_name() !== 'cli') {
      return;
    }

    $bgSequences = [
      'black' => "\033[40m",
      'red' => "\033[41m",
      'green' => "\033[42m",
      'yellow' => "\033[43m",
      'blue' => "\033[44m",
      'purple' => "\033[45m",
      'cyan' => "\033[46m",
      'white' => "\033[47m",
    ];

    $this->echo($bgSequences[$bgColor] ?? '');

    $fgSequences = [
      'black' => "\033[30m",
      'red' => "\033[31m",
      'green' => "\033[32m",
      'yellow' => "\033[33m",
      'blue' => "\033[34m",
      'purple' => "\033[35m",
      'cyan' => "\033[36m",
      'white' => "\033[37m",
    ];

    $this->echo($fgSequences[$fgColor] ?? '');
  }

  /**
   * Read input from terminal/console
   *
   * @return string
   * 
   */
  public function readRaw(): string
  {
    $clih = fopen("php://stdin", "r");
    $input = fgets($clih);
    $input = trim($input);
    return $input;
  }

  /**
   * Read input from terminal/console and return $default is none is entered.
   *
   * @param string $message
   * @param string $default
   * 
   * @return string
   * 
   */
  public function read(string $message, string $default = ''): string
  {
    $this->yellow($message . (empty($default) ? '' : ' (press Enter for \'' . $default . '\')') . ': ');

    $input = $this->readRaw();
    if (empty($input)) {
      $input = $default;
    }

    $this->white('  -> ' . $input . "\n");

    return $input;
  }

  /**
   * Get user selection from pre-defined options using terminal/console.
   *
   * @param array $options
   * @param string $message
   * @param string $default
   * 
   * @return string
   * 
   */
  public function choose(array $options, string $message, string $default = ''): string
  {
    $this->yellow($message . "\n");
    foreach ($options as $key => $option) {
      $this->white(' ' . (string) $key . ' = ' . (string) $option . "\n");
    }
    $this->yellow('Select one of the options, provide a value' . (empty($default) ? '' : ' or press Enter for \'' . $default . '\'') . ': ');

    $input = $this->readRaw();
    if (is_numeric($input)) {
      $input = (string) ($options[$input] ?? '');
    }
    if (empty($input)) {
      $input = $default;
    }

    $this->white('  -> ' . $input . "\n");

    return $input;
  }

  /**
   * Ask for user confirmation
   *
   * @param string $question
   * @param array $yesAnswers Possible answers representing confirmation.
   * 
   * @return bool
   * 
   */
  public function confirm(string $question, $yesAnswers = ['yes', 'y', '1']): bool
  {
    $answer = $this->read($question);
    return in_array(strtolower($answer), $yesAnswers);
  }

  /**
   * Print message in terminal in yellow color
   *
   * @param string $message
   * 
   * @return void
   * 
   */
  public function yellow(string $message): void
  {
    $this->color('yellow');
    $this->echo($message);
    $this->color('white');
  }

  /**
   * Print message in terminal in green color
   *
   * @param string $message
   * 
   * @return void
   * 
   */
  public function green(string $message): void
  {
    $this->color('green');
    $this->echo($message);
    $this->color('white');
  }

  /**
   * Print message in terminal in red color
   *
   * @param string $message
   * 
   * @return void
   * 
   */
  public function red(string $message): void
  {
    $this->color('red');
    $this->echo($message);
    $this->color('white');
  }

  /**
   * Print message in terminal in blue color
   *
   * @param string $message
   * 
   * @return void
   * 
   */
  public function blue(string $message): void
  {
    $this->color('blue');
    $this->echo($message);
    $this->color('white');
  }

  /**
   * Print message in terminal in cyan color
   *
   * @param string $message
   * 
   * @return void
   * 
   */
  public function cyan(string $message): void
  {
    $this->color('cyan');
    $this->echo($message);
    $this->color('white');
  }

  /**
   * Print message in terminal in white color
   *
   * @param string $message
   * 
   * @return void
   * 
   */
  public function white(string $message): void
  {
    $this->color('white');
    $this->echo($message);
    $this->color('white');
  }

  /**
   * Print message in terminal in specified color
   *
   * @param string $bgColor
   * @param string $fgColor
   * @param string $message
   * 
   * @return void
   * 
   */
  public function colored(string $bgColor, string $fgColor, string $message): void
  {
    $this->color($fgColor, $bgColor);
    $this->echo($message);
    $this->color('white', 'black');
    $this->echo($message);
  }

  /**
   * [Description for insertCodeToFile]
   *
   * @param string $file
   * @param string $tag
   * @param array $codeLines
   * 
   * @return bool
   * 
   */
  public function insertCodeToFile(string $file, string $tag, array $codeLines): bool
  {
    $inserted = false;

    if (!is_file($file)) {
      return false;
    }

    $lines = file($file);
    $newLines = [];
    foreach ($lines as $line) {
      $newLines[] = $line;
      if (str_starts_with(trim($line), $tag)) {
        $identSize = strlen($line) - strlen(ltrim($line));
        foreach ($codeLines as $codeLine) {
          $newLines[] = str_repeat(' ', $identSize) . trim($codeLine) . "\n";
        }
        $inserted = true;
      }
    }

    if ($inserted) {
      file_put_contents($file, join("", $newLines));
      $this->yellow("Code inserted into '{$file}' under '{$tag}'.\n");
    }

    return $inserted;
  }

}
