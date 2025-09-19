<?php

namespace Hubleto\Framework\Interfaces;

interface TranslatorInterface
{
  // public function getParentService(): mixed;
  // public function setParentService(mixed $service): void;
  public function getDictionaryFilename(CoreInterface $service, string $language): string;
  public function addToDictionary(CoreInterface $service, string $language, string $contextInner, string $string): void;
  public function loadDictionary(CoreInterface $service, string $language): void;
  public function loadFullDictionary(CoreInterface $core, string $language): array;
  public function translate(CoreInterface $service, string $string, array $vars = []): string;
  // public function translateToLanguage(string $language, string $string, array $vars = []): string;

}