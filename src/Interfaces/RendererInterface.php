<?php

namespace Hubleto\Framework\Interfaces;

interface RendererInterface
{

  public function init(): void;
  public function getTwig(): \Twig\Environment;
  public function addNamespace(string $folder, string $namespace);
  public function renderView(string $view, array $vars = []): string;
  public function render(string $route = '', array $params = []): string;
  public function renderSuccess($return): string;
  public function renderWarning($message, $isHtml = true): string;
  public function renderFatal($message, $isHtml = true): string;
  public function renderHtmlFatal($message): string;
  public function renderExceptionHtml($exception, array $args = []): string;
  public function renderHtmlWarning($warning): string;
  public function onBeforeRender(): void;
  public function onAfterRender(): void;

}