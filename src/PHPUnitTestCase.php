<?php

namespace Hubleto\Framework;

class PHPUnitTestCase extends \PHPUnit\Framework\TestCase
{

  public function _app(): Core
  {
    return Loader::getGlobalApp();
  }

  protected function _setTerminalColor(string $color): void
  {
    if (!empty($color)) {
      $colorSequences = [
        'default' => "\033[0m",
        'black' => "\033[30m",
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'purple' => "\033[35m",
        'cyan' => "\033[36m",
        'white' => "\033[37m",
      ];

      fwrite(STDOUT, $colorSequences[$color] ?? "\033[0m");
    }
  }

  protected function _echo(string $message, string $color = 'yellow'): void
  {
    $this->_setTerminalColor($color);
    fwrite(STDOUT, $message);
    $this->_setTerminalColor('default');
  }

  protected function _warning(string $message): void
  {
    $this->_echo($message, 'yellow');
  }

  protected function _info(string $message): void
  {
    $this->_echo($message, 'blue');
  }

  protected function _error(string $message): void
  {
    $this->_echo($message, 'red');
  }

  protected function _replaceVarsInRoute(string $route, array $vars = []): string
  {
    foreach ($vars as $varName => $varValue) {
      if (
        strpos($route, '{{ ' . $varName . ' }}') !== false
        && !is_array($varValue)
      ) {
        $route = str_replace('{{ ' . $varName . ' }}', (string) $varValue, $route);
      }
    }

    return $route;
  }

  protected function _testRouteDoesNotContainError(string $route, array $vars = []): string
  {
    $this->_echo("  _testRoute(" . $route . ")\n");
    $route = $this->_replaceVarsInRoute($route, $vars);
    $html = $this->_app()->renderer()->render($route);
    $this->assertStringNotContainsStringIgnoringCase('error', $html, $route . ' does not contain DOCTYPE tag.');    
    return $html;
  }

  /**
   * [Description for _callApi]
   *
   * @param string $route
   * @param array $vars
   * 
   * @return void
   * 
   */
  protected function _callApi(string $route, array $vars = []): array
  {
    $route = $this->_replaceVarsInRoute($route, $vars);
    $output = $this->_app()->renderer()->render($route, $vars);
    $this->assertJson($output, $route . ' with ' . json_encode($vars) . ' does not render JSON. It returns: ' . $output); 

    $outputArray = (array) @json_decode($output, true);

    if (isset($outputArray['status'])) {
      if (!in_array($outputArray['status'], ['success', 'ok', 'fail', 'error'])) {
        $this->assertTrue(false, 'API returned invalid status `' . $outputArray['status'] . '`.' . "\n");
      }

      if (in_array($outputArray['status'], ['fail', 'error'])) {
        if (empty($outputArray['message'])) {
          $this->assertTrue(false, 'API returned status `' . $outputArray['status'] . '` but did not return any message.' . "\n");
        }

        $this->_warning('CallAPI to route `' . $route . '` with ' . json_encode($vars) . ' returned status `' . $outputArray['status'] . '` with message `' . $outputArray['message'] . '`.' . "\n");
      }
    }

    return $outputArray;
  }

  /**
   * [Description for _callView]
   *
   * @param string $route
   * @param array $vars
   * 
   * @return void
   * 
   */
  protected function _callView(string $route, array $vars = []): array
  {
    $this->_info('_callView(`' . $route . '`, ' . json_encode($vars) . ')' . "\n");
    $route = $this->_replaceVarsInRoute($route, $vars);
    $output = $this->_app()->renderer()->render($route, $vars);

    $returnedView = $this->_app()->renderer()->lastRenderedView;
    $returnedViewParams = $this->_app()->renderer()->lastRenderedContentParams['viewParams'] ?? [];

    $this->_info('  Sets view to `' . $returnedView . "`.\n");
    $this->_info('  Sets viewParams to ' . json_encode($returnedViewParams) . "\n");

    return [
      'view' => $returnedView,
      'viewParams' => $returnedViewParams,
    ];
  }

}