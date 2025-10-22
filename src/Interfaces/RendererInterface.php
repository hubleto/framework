<?php

namespace Hubleto\Framework\Interfaces;

use Hubleto\Framework\Exceptions\Exception;

interface RendererInterface
{

  public function init(): void;
  public function getTwig(): \Twig\Environment;
  public function addNamespace(string $folder, string $namespace);
  public function renderView(string $view, array $vars = []): string;
  public function render(string $route = '', array $params = []): string;
  public function renderSuccess($return): string;
  public function renderWarning(Exception $exception, $isHtml = true): string;
  public function renderFatal(Exception $exception, $isHtml = true): string;
  public function renderHtmlFatal(Exception $exception): string;
  public function renderExceptionHtml($exception, array $args = []): string;
  public function renderHtmlWarning(Exception $exception): string;
  public function onBeforeRender(): void;
  public function onAfterRender(): void;

}