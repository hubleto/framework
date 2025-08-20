<?php

namespace Hubleto\Framework;

class Translator implements Interfaces\TranslatorInterface
{

  public string $dictionaryFilename = "Core-Loader";
  public array $dictionary = [];

  public function __construct(public \Hubleto\Framework\Loader $main)
  {
    $this->dictionary = [];
  }

  public function getDictionaryFilename(string $context, string $language = ''): string
  {
    $dictionaryFile = '';

    if (empty($language)) $language = $this->main->config->getAsString('language', 'en');
    if (empty($language)) $language = 'en';

    if (strlen($language) == 2) {
      $dictionaryFile = $this->main->srcFolder . "/Lang/{$language}.json";
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
  public function loadDictionaryFromJsonFile(string $jsonFile): array
  {
    return (array) @json_decode((string) file_get_contents($jsonFile), true);
  }

  /**
  * @return array|array<string, array<string, string>>
  */
  public function loadDictionary(string $language = ""): array
  {
    if (empty($language)) {
      $language = $this->main->getLanguage();
    }
    if ($language == 'en') {
      return [];
    }

    $dictionary = [];

    if (strlen($language) == 2) {
      $dictFilename = __DIR__ . '/../../lang/' . $language . '.json';
      if (is_file($dictFilename)) {
        $dictionary['HubletoMain\\Loader'] = $this->loadDictionaryFromJsonFile($dictFilename);
      }
    }

    if (isset($this->main->apps)) {
      foreach ($this->main->apps->getEnabledApps() as $app) {
        $appDict = $app->loadDictionary($language);
        foreach ($appDict as $key => $value) {
          $dictionary[$app->fullName][(string) $key] = $value;
        }
      }
    }

    $dictionary['Hubleto\\Framework\\Core\\Loader'] = Loader::loadDictionary($language);

    return $dictionary;
  }

  public function loadDictionaryForContext(string $language, string $contextFileRef): array
  {
    $dictionaryFilename = '';

    if ($contextFileRef == 'HubletoMain') {
      $dictionaryFilename = __DIR__ . '/../../lang/' . $language . '.json';
    } elseif (str_starts_with($contextFileRef, 'HubletoApp')) {
      $appClass = str_replace('/', '\\', $contextFileRef);

      $app = $this->main->apps->getAppInstance($appClass);
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

  /**
  * @param array<string, string> $vars
  */
  public function translate(string $string, array $vars = [], string $context = "Hubleto\\Framework\\Loader::root", string $toLanguage = ""): string
  {
    if (empty($toLanguage)) {
      $toLanguage = $this->main->getLanguage();
    }
    if (strpos($context, '::')) {
      list($contextClass, $contextInner) = explode('::', $context);
    } else {
      $contextClass = '';
      $contextInner = $context;
    }

    if ($toLanguage == 'en') {
      $translated = $string;
    } else {
      if (empty($this->dictionary[$contextClass]) && class_exists($contextClass)) {
        $this->dictionary[$contextClass] = $contextClass::loadDictionary($toLanguage);
      }

      $translated = '';

      if (!empty($this->dictionary[$contextClass][$contextInner][$string])) { // @phpstan-ignore-line
        $translated = (string) $this->dictionary[$contextClass][$contextInner][$string];
      } elseif (class_exists($contextClass)) {
        $contextClass::addToDictionary($toLanguage, $contextInner, $string);
      }

      if (empty($translated)) {
        $translated = 'translate(' . $context . '; ' . $string . ')';
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
