<?php

namespace Hubleto\Framework\Interfaces;

interface CoreInterface
{

  public static function getServiceStatic(string $service): mixed;

  public function setDebugLevel(int $level): void;
  public function getDebugLevel(): int;
  public function getService(string $service): mixed;
  public function env(): EnvInterface;
  public function authProvider(): AuthInterface;
  public function db(): DbInterface;
  public function appManager(): AppManagerInterface;
  public function router(): RouterInterface;
  public function hookManager(): HookManagerInterface;
  public function sessionManager(): SessionManagerInterface;
  public function permissionsManager(): PermissionsManagerInterface;
  public function cronManager(): CronManagerInterface;
  public function emailProvider(): EmailProviderInterface;
  public function config(): ConfigManagerInterface;
  public function logger(): LoggerInterface;
  public function locale(): LocaleInterface;
  public function renderer(): RendererInterface;
  public function translator(): TranslatorInterface;
  public function getModel(string $model): ModelInterface;
  public function getController(string $controller): ControllerInterface;
  public function translate(string $string, array $vars = [], string $contextInner = ''): string;

}