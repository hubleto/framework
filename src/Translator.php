<?php

namespace Hubleto\Framework;

use Hubleto\Framework\Interfaces\AppManagerInterface;

/**
 * Default translator for Hubleto project.
 */
class Translator extends Core implements Interfaces\TranslatorInterface
{

  public string $context = '';
  public string $dictionaryFilename = "Core-Loader";
  public array $dictionary = [];

  // public function __construct()
  // {
  //   $this->dictionary = [];
  // }

  public function getContext(): string
  {
    return $this->context;
  }

  public function setContext(string $context): void
  {
    $this->context = $context;
  }

  /**
  * @return array|array<string, array<string, string>>
  */
  public function loadDictionaryFromJsonFile(string $jsonFile): array
  {
    return (array) @json_decode((string) file_get_contents($jsonFile), true);
  }

  public function loadDictionaryForContext(string $language, string $contextFileRef): array
  {
    $dictionaryFilename = '';

    if ($contextFileRef == 'Hubleto\\Erp') {
      $dictionaryFilename = __DIR__ . '/../../lang/' . $language . '.json';
    } elseif (str_starts_with($contextFileRef, 'Hubleto\\App')) {
      $appClass = str_replace('/', '\\', $contextFileRef);

      $app = $this->appManager()->getApp($appClass);
      if ($app) {
        $dictionaryFilename = $app->srcFolder . '/Lang/' . $language . '.json';
      }
    }

    if (!empty($dictionaryFilename) && is_file($dictionaryFilename)) {
      $dictionary = (array) @json_decode((string) file_get_contents($dictionaryFilename), true);
      return $dictionary;
    } else {
      return [];
    }
  }

  public function getDictionaryFilename(string $context, string $language = ''): string
  {
    $dictionaryFile = '';

    if (empty($language)) $language = $this->config()->getAsString('language', 'en');
    if (empty($language)) $language = 'en';

    if (strlen($language) == 2) {
      $dictionaryFile = $this->env()->srcFolder . "/Lang/{$language}.json";
    }

    return $dictionaryFile;
  }

  public function addToDictionary(string $string, string $context, string $toLanguage): void
  {
    $dictionaryFile = $this->getDictionaryFilename($context, $toLanguage);
    $this->dictionary[$toLanguage][$context][$string] = '';

    if (is_file($dictionaryFile)) {
      file_put_contents(
        $dictionaryFile,
        json_encode(
          $this->dictionary[$toLanguage],
          JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        )
      );
    }
  }

  /**
  * @return array|array<string, array<string, string>>
  */
  public function loadDictionary(string $language = ""): array
  {
    if (empty($language)) {
      $language = $this->authProvider()->getUserLanguage();
    }

    if ($language == 'en') {
      return [];
    }

    $dictionary = [];

    foreach ($this->appManager()->getEnabledApps() as $app) {
      $appDict = $app->loadDictionary($language);
      foreach ($appDict as $key => $value) {
        $dictionary[$app->fullName][(string) $key] = $value;
      }
    }

    return $dictionary;
  }

  /**
  * @param array<string, string> $vars
  */
  public function translate(string $string, array $vars = []): string
  {
    if (empty($toLanguage)) {
      $toLanguage = $this->authProvider()->getUserLanguage();
    }

    if (strpos($this->context, '::')) {
      list($contextClass, $contextInner) = explode('::', $this->context);
    } else {
      $contextClass = '';
      $contextInner = $this->context;
    }

    if ($toLanguage == 'en') {
      $translated = $string;
    } else {
      // 27.8.2025: Docasne vypnute
      // if (empty($this->dictionary[$contextClass]) && class_exists($contextClass)) {
      //   $this->dictionary[$contextClass] = $contextClass::loadDictionary($toLanguage);
      // }

      $translated = '';

      // 27.8.2025: Docasne vypnute
      // if (!empty($this->dictionary[$contextClass][$contextInner][$string])) { // @phpstan-ignore-line
      //   $translated = (string) $this->dictionary[$contextClass][$contextInner][$string];
      // } elseif (class_exists($contextClass)) {
      //   $contextClass::addToDictionary($toLanguage, $contextInner, $string);
      // }

      if (empty($translated)) {
        $translated = 'translate(' . $this->context . '; ' . $string . ')';
      }
    }

    if (empty($translated)) {
      $translated = $string;
    }

    foreach ($vars as $varName => $varValue) {
      $translated = str_replace('{{ ' . $varName . ' }}', $varValue, $translated);
    }

    return $translated;
  }

}
