<?php

namespace Hubleto\Framework\Interfaces;

interface TranslatorInterface
{
  // public function getParentService(): mixed;
  // public function setParentService(mixed $service): void;
  public function getDictionaryFilename(CoreInterface $core, string $language, string $context): string;
  public function addToDictionary(CoreInterface $core, string $language, string $context, string $contextInner, string $string);
  public function loadDictionary(CoreInterface $core, string $language, string $context): void;
  public function loadFullDictionary(CoreInterface $core, string $language): array;
  public function translate(CoreInterface $service, string $string, array $vars = [], string $contextInner = ''): string;
  // public function translateToLanguage(string $language, string $string, array $vars = []): string;

}