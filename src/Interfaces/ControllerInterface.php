<?php

namespace Hubleto\Framework\Interfaces;

interface ControllerInterface
{

  public function validateInputs(): bool;
  public function preInit(): void;
  public function init(): void;
  public function postInit(): void;
  public function run(): mixed;
  public function renderString(): string;
  public function renderJson(): array;
  public function prepareViewParams();
  public function prepareView(): void;
  public function setView(string $view): void;
  public function getView(): string;
  public function getViewParams(): array;
  public function render(): string;

}

