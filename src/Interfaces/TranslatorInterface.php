<?php

namespace Hubleto\Framework\Interfaces;

interface TranslatorInterface
{
  public function getContext(): string;
  public function setContext(string $context): void;
  public function getDictionaryFilename(string $context, string $language = ''): string;
  public function addToDictionary(string $string, string $context, string $toLanguage): void;
  public function loadDictionary(string $language = ""): array;
  public function translate(string $string, array $vars = []): string;

}