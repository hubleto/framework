<?php

namespace Hubleto\Framework;

/**
 * Core implementation of Hubleto Framework controller
 * 
 * 'Controller' is fundamendal class for generating HTML content of each call. Controllers can
 * be rendered using Twig template or using custom render() method.
 * 
 */
class Controller {

  const RETURN_TYPE_VIEW = 0;
  const RETURN_TYPE_JSON = 1;
  const RETURN_TYPE_STRING = 2;
  const RETURN_TYPE_NONE = 3;

  /**
   * Shorthand for "global table prefix"
   */
  protected string $gtp = "";

  /**
   * DEPRECATED Array of parameters (arguments) passed to the controller
   */
  public array $params = [];

  /**
   * TRUE/FALSE array with permissions for the user role
   */
  public static array $permissionsByUserRole = [];
  
  /**
   * If set to FALSE, the rendered content of controller is available to public
   */
  public bool $requiresUserAuthentication = TRUE;

  /**
   * If set to TRUE, the default desktop will not be added to the rendered content
   */
  public bool $hideDefaultDesktop = FALSE;

  /**
   * If set to FALSE, the controller will not be rendered in CLI
   */
  public static bool $cliSAPIEnabled = TRUE;

  /**
   * If set to FALSE, the controller will not be rendered in WEB
   */
  public static bool $webSAPIEnabled = TRUE;

  public array $dictionary = [];
  protected array $viewParams = [];

  public string $name = "";
  public string $shortName = "";
  public string $fullName = "";
  public string $permission = "";
  public string $view = "";

  public int $returnType = self::RETURN_TYPE_VIEW;

  public object $renderer;
  public string $translationContext = '';

  function __construct(public Loader $main, array $params = [])
  {
    $reflection = new \ReflectionClass($this);

    $this->name = str_replace("\\", "/", str_replace("Hubleto\\Framework\\", "", get_class($this)));

    if (isset($this->main->twig)) {
      $this->renderer = $this->main->twig;
    }

    $this->permission = $this->shortName;

    $this->fullName = str_replace("\\", "/", $reflection->getName());

    $tmp = explode("/", $this->fullName);
    $this->shortName = end($tmp);

    if (empty($this->translationContext)) {
      $this->translationContext = trim(str_replace('/', '\\', $this->fullName), '\\');
    }
  }

  public function prepareParams(): array
  {
    return [];
  }

  /**
    * Validates inputs ($this->main->params) used for the TWIG template.
    *
    * return bool True if inputs are valid, otherwise false.
    */
  public function validateInputs(): bool
  {
    return TRUE;
  }

  /**
   * 1st phase of controller's initialization phase.
   *
   * @throws Exception Should throw an exception on error.
   */
  public function preInit(): void
  {
    //
  }

  /**
   * 2nd phase of controller's initialization phase.
   *
   * @throws Exception Should throw an exception on error.
   */
  public function init(): void
  {
    //
  }

  /**
   * 3rd phase of controller's initialization phase.
   *
   * @throws Exception Should throw an exception on error.
   */
  public function postInit(): void
  {
    //
  }

  public function run(): mixed
  {
    return null;
  }

  public function renderString(): string
  {
    return '';
  }

  /**
   * If the controller shall only return JSON, this method must be overriden.
   *
   * @return array Array to be returned as a JSON.
   */
  public function renderJson(): ?array
  {
    return null;
  }

  /**
   * If the controller shall return the HTML of the view, this method must be overriden.
   *
   * @return array View to be used to render the HTML.
   */
  public function prepareViewParams()
  {
    $this->viewParams = $this->main->getUrlParams();
  }

  public function prepareView(): void
  {
    $this->main->translationContext = $this->translationContext;
    $this->viewParams = $this->main->getUrlParams();
    $this->viewParams['main'] = $this->main;
  }
  
  /**
   * Shorthand for core translate() function. Uses own language dictionary.
   *
   * @param  string $string String to be translated
   * @param  string $context Context where the string is used
   * @param  string $toLanguage Output language
   * @return string Translated string.
   */
  public function translate(string $string, array $vars = []): string
  {
    return $this->main->translate($string, $vars, $this->translationContext);
  }

  public function setView(string $view): void
  {
    $this->view = $view;
  }

  public function setRenderer(Object $renderer): void
  {
    $this->renderer = $renderer;
  }

  public function getView(): string
  {
    return $this->view;
  }

  public function getViewParams(): array
  {
    return $this->viewParams;
  }

  public function render(array $params): string
  {
    return '';
  }

}

