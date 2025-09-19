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
   * @param string $context
   * @param string $language
   * 
   * @return string
   * 
   */
  public function getDictionaryFilename(Interfaces\CoreInterface $core, string $language, string $context): string
  {
    $dictionaryFile = '';

    if (strlen($language) == 2) {
      $file = $context;
      $file = strtolower(strtr($file, '\\/', '--'));
      $dictionaryFile = $core->env()->srcFolder . "/../lang/{$language}/{$file}.json";
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
  public function addToDictionary(Interfaces\CoreInterface $core, string $language, string $context, string $contextInner, string $string): void
  {

    $dictionaryFile = $this->getDictionaryFilename($core, $language, $context);
    $dictionary = $this->loadFullDictionary($core, $language);
    $dictionary[$context][$contextInner][$string] = '';
    // var_dump($dictionaryFile);
    // var_dump(array_keys($dictionary));
    // var_dump($dictionary[$context][$contextInner]);exit;

    if (is_file($dictionaryFile)) {
      file_put_contents(
        $dictionaryFile,
        json_encode(
          $dictionary[$context],
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
  public function loadDictionary(Interfaces\CoreInterface $core, string $language, string $context): void
  {
    if ($language == 'en') return;
    if (!empty($this->dictionary[$language][$context])) return;

    $dictFilename = $this->getDictionaryFilename($core, $language, $context);
    if (is_file($dictFilename)) {
      $this->dictionary[$language][$context] = (array) @json_decode((string) file_get_contents($dictFilename), true);
    }
  }

  /**
   * [Description for loadFullDictionary]
   *
   * @param Interfaces\CoreInterface $core
   * @param string $language
   * 
   * @return array
   * 
   */
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
            $dictionary[substr($file, 0, -5)] = json_decode(file_get_contents($folder . '/' . $file), true);
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
      $this->loadDictionary($service, $context, $language);

      if (isset($this->dictionary[$language][$context][$contextInner][$string])) {
        $translated = (string) $this->dictionary[$language][$context][$contextInner][$string];
        if ($translated == '') $translated = '{/ ' . $string . ' /}';
      } else {
        // $this->addToDictionary($service, $language, $context, $contextInner, $string);
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
