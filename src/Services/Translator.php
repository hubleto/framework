<?php

namespace Hubleto\Framework\Services;

use Hubleto\Framework\Core;
use Hubleto\Framework\Interfaces\TranslatorInterface;
use Hubleto\Framework\Interfaces\CoreInterface;

/**
 * Default translator for Hubleto project.
 */
class Translator implements TranslatorInterface
{

  public Core $service;
  public array $dictionary = [];

  /** @var array<string, string> [translation context of an app => its Lang folder] */
  private array $appLangFolders = [];
  /** Number of installed apps when $appLangFolders was built, -1 = not built yet. */
  private int $appCountOfLangFolders = -1;

  /**
   * [Description for getDictionaryFilename]
   *
   * @param string $context
   * @param string $language
   * 
   * @return string
   * 
   */
  public function getDictionaryFilename(CoreInterface $core, string $language, string $context): string
  {
    $dictionaryFile = '';

    if (strlen($language) == 2) {
      $file = $context;
      $file = strtolower(strtr($file, '\\/', '--'));
      $dictionaryFile = $core->env()->srcFolder . "/../lang/{$language}/{$file}.json";

      // External and custom apps have their dictionaries in their own Lang folder.
      $appLangFolder = $this->getAppLangFolders($core)[$file] ?? '';
      if ($appLangFolder != '') {
        $dictionaryFile = $appLangFolder . "/{$language}.json";
      }
    }

    return $dictionaryFile;
  }

  /**
   * Lang folders of the installed external and custom apps, e.g. <app>/Lang/sk.json.
   * Apps of hubleto/erp have their dictionaries in hubleto/erp/lang.
   *
   * @return array<string, string> [translation context of the app => its Lang folder]
   */
  private function getAppLangFolders(CoreInterface $core): array
  {
    // The list is kept for the request, the translator asks for it for every context.
    // During the installation apps are added one by one, so it is built again when their count changes.
    $installedApps = $core->appManager()->getInstalledApps();
    if (count($installedApps) == $this->appCountOfLangFolders) return $this->appLangFolders;

    $this->appLangFolders = [];
    foreach ($installedApps as $app) {
      $isExternalApp = str_starts_with($app->namespace, 'Hubleto\\App\\External\\');
      $isCustomApp = str_starts_with($app->namespace, 'Hubleto\\App\\Custom\\');
      if ($isExternalApp || $isCustomApp) {
        $this->appLangFolders[$app->translationContext] = $app->srcFolder . '/Lang';
      }
    }
    $this->appCountOfLangFolders = count($installedApps);

    return $this->appLangFolders;
  }

  /**
   * [Description for addToDictionary]
   *
   * @param CoreInterface $service
   * @param string $language
   * @param string $string
   * 
   * @return void
   * 
   */
  public function addToDictionary(CoreInterface $core, string $language, string $context, string $contextInner, string $string): void
  {

    $debugTranslations = $core->config()->getAsBool('debugTranslations');

    if (!$debugTranslations) return;

    $dictionaryFile = $this->getDictionaryFilename($core, $language, $context);
    $dictionary = $this->loadFullDictionary($core, $language);
    $dictionary[$context][$contextInner][$string] = '';

    if (is_file($dictionaryFile)) {
      @file_put_contents(
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
   * @param CoreInterface $core
   * @param string $language
   * 
   * @return void
   * 
   */
  public function loadDictionary(CoreInterface $core, string $language, string $context): void
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
   * @param CoreInterface $core
   * @param string $language
   * 
   * @return array
   * 
   */
  public function loadFullDictionary(CoreInterface $core, string $language): array
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

      // External and custom apps have their dictionaries in their own Lang folder.
      foreach ($this->getAppLangFolders($core) as $appContext => $appLangFolder) {
        $appDictionaryFile = $appLangFolder . "/{$language}.json";
        if (!is_file($appDictionaryFile)) continue;

        $dictionary[$appContext] = json_decode((string) file_get_contents($appDictionaryFile), true);
      }
    }

    return $dictionary;
  }

  /**
   * [Description for translate]
   *
   * @param CoreInterface $service
   * @param string $string
   * @param array $vars
   * 
   * @return string
   * 
   */
  public function translate(CoreInterface $service, string $string, array $vars = [], string $context = ''): string
  {
  
    $language = $service->authProvider()->getUserLanguage();
    if (empty($language) || strlen($language) !== 2) $language = 'en';

    if (!empty($context)) {
      if (strpos($context, ':') === false) { // $context is given but contains only $contextInner
        $contextInner = $context;
        $context = '';
      } else { // $context is given and contains also $contextInner
        list($context, $contextInner) = explode(':', $context);
      }
    }

    if (empty($context)) $context = $service->translationContext;
    if (empty($contextInner)) $contextInner = $service->translationContextInner;

    $debugTranslations = $service->config()->getAsBool('debugTranslations');

    $translated = '';
    if ($language != 'en' || $debugTranslations) {
      try {
        $this->loadDictionary($service, $language, $context);

        if ($debugTranslations) {
          $languages = $service->locale()->getAvailableLanguages();
          foreach ($languages as $tmpLanguage => $tmpLanguageData) {
            if ($tmpLanguage == 'en') continue;
            $this->loadDictionary($service, $tmpLanguage, $context);
            if (!isset($this->dictionary[$tmpLanguage][$context][$contextInner][$string])) {
              $this->addToDictionary($service, $tmpLanguage, $context, $contextInner, $string);
            }
          }
        }

        if (isset($this->dictionary[$language][$context][$contextInner][$string])) {
          $translated = (string) $this->dictionary[$language][$context][$contextInner][$string];
          if ($translated == '' && $debugTranslations) $translated = '** ' . $string . ' **';
        } else if ($debugTranslations) {
          $translated = 't(' . $context . ':' . $contextInner . '; ' . $string . ')';
        }
      } catch (\Throwable $e) {
        $translated = $e->getMessage() . $e->getTraceAsString();
      }
    }

    if (empty($translated)) $translated = $string;

    foreach ($vars as $varName => $varValue) {
      $translated = str_replace('{{ ' . $varName . ' }}', $varValue, $translated);
    }

    return $translated;
  }

}
