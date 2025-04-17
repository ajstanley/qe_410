<?php

namespace Drupal\qe_410\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\MainContent\HtmlRenderer;

/**
 * Adds a 410 response to chosen pages.
 */
class GonePageSubscriber implements EventSubscriberInterface {

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The main content renderer.
   *
   * @var \Drupal\Core\Render\MainContent\HtmlRenderer
   */
  protected $mainContentRenderer;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;
  /**
   * Constructs a new GonePageSubscriber object.
   *
   * @param \Drupal\Core\Path\CurrentPathStack $currentPath
   *   The current path service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\Core\Render\MainContent\HtmlRenderer $mainContentRenderer
   *   The main content renderer for HTML output.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match service for determining the current route.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory service for logging events.
   */
  public function __construct(
    CurrentPathStack $currentPath,
    RendererInterface $renderer,
    ThemeManagerInterface $themeManager,
    ConfigFactoryInterface $configFactory,
    HtmlRenderer $mainContentRenderer,
    RouteMatchInterface $routeMatch,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->currentPath = $currentPath;
    $this->renderer = $renderer;
    $this->themeManager = $themeManager;
    $this->configFactory = $configFactory;
    $this->mainContentRenderer = $mainContentRenderer;
    $this->routeMatch = $routeMatch;
    $this->logger = $loggerFactory->get('qe_410');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onRequest', 30],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function onRequest(RequestEvent $event) {
    $path = $this->currentPath->getPath();
    $fields = $this->configFactory->get('qe_410.settings')->get('fields') ?? [];
    if (in_array($path, $fields)) {
      $request = $event->getRequest();
      $content = [
        '#theme' => 'qe_410_page_content',
        '#gone_title' => 'Page Permanently Deleted',
        '#gone_message' => 'The content you are looking for has been permanently removed.',
      ];

      $main_content = [
        '#type' => 'page',
        '#title' => 'Gone',
        'content' => $content,
      ];

      $response = $this->mainContentRenderer->renderResponse($main_content, $request, $this->routeMatch);
      $response->setStatusCode(410);

      $event->setResponse($response);
    }
  }

}
