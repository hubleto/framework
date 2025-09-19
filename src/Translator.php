<?php

namespace Hubleto\Framework;

/**
 * Default translator for Hubleto project.
 */
class Translator implements Interfaces\TranslatorInterface
{

  public Core $service;
  public array $dictionary = [];

  /**
   * [Description for getDictionaryFilename]
   *
   * @param Interfaces\CoreInterface $service
   * @param string $language
   * 
   * @return string
   * 
   */
  public function getDictionaryFilename(Interfaces\CoreInterface $service, string $language): string
  {
    $dictionaryFile = '';

    if (strlen($language) == 2) {
      $file = $service->translationContext;
      $file = strtolower(strtr($file, '\\/', '--'));
      $dictionaryFile = $service->env()->srcFolder . "/../lang/{$language}/{$file}.json";
    }

    return $dictionaryFile;
  }

  /**
   * [Description for addToDictionary]
   *
   * @param Interfaces\CoreInterface $service
   * @param string $language
   * @param string $string
   * 
   * @return void
   * 
   */
  public function addToDictionary(Interfaces\CoreInterface $service, string $language, string $contextInner, string $string): void
  {
return;
    $dictionaryFile = $this->getDictionaryFilename($service, $language);
    $this->dictionary[$language][$contextInner][$string] = '';

    if (is_file($dictionaryFile)) {
      file_put_contents(
        $dictionaryFile,
        json_encode(
          $this->dictionary[$language],
          JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        )
      );
    }
  }

  /**
   * [Description for loadDictionary]
   *
   * @param Interfaces\CoreInterface $service
   * @param string $language
   * 
   * @return void
   * 
   */
  public function loadDictionary(Interfaces\CoreInterface $service, string $language): void
  {
    $context = $service->translationContext;

    if ($language == 'en') return;
    if (!empty($this->dictionary[$language][$context])) return;

    $dictFilename = $this->getDictionaryFilename($service, $language);
    if (is_file($dictFilename)) {
      $this->dictionary[$language][$context] = (array) @json_decode((string) file_get_contents($dictFilename), true);
    }
  }

  public function loadFullDictionary(Interfaces\CoreInterface $core, string $language): array
  {
    $dictionary = [];

    if (strlen($language) == 2) {
      $folder = $core->env()->srcFolder . "/../lang/{$language}";
      if (is_dir($folder)) {
        $files = scandir($folder);
        foreach ($files as $file) {
          if (in_array($file, ['.', '..'])) continue;
          if (substr($file, -5) !== '.json') continue;
          try {
            $dictionary[substr($file, 0, -5)] = json_decode(file_get_contents($folder . '/' . $file));
          } catch (\Throwable $e) {
            //
          }
        }
      }
    }

    return $dictionary;
  }

  /**
   * [Description for translate]
   *
   * @param Interfaces\CoreInterface $service
   * @param string $string
   * @param array $vars
   * 
   * @return string
   * 
   */
  public function translate(Interfaces\CoreInterface $service, string $string, array $vars = []): string
  {
  
    $language = $service->authProvider()->getUserLanguage();
    if (empty($language) || strlen($language) !== 2) $language = 'en';
    if ($language == 'en') return $string;

    $context = $service->translationContext;
    $contextInner = $service->translationContextInner;

    $translated = 't(' . $context . ':' . $contextInner . '; ' . $string . ')';

    try {
      $this->loadDictionary($service, $language);

      if (!empty($this->dictionary[$language][$context][$contextInner][$string])) {
        $translated = (string) $this->dictionary[$language][$context][$contextInner][$string];
      } else {
        $this->addToDictionary($service, $language, $contextInner, $string);
      }
    } catch (\Throwable $e) {
      $translated = $e->getMessage() . $e->getTraceAsString();
    }

    foreach ($vars as $varName => $varValue) {
      $translated = str_replace('{{ ' . $varName . ' }}', $varValue, $translated);
    }

    return $translated;
  }

}
