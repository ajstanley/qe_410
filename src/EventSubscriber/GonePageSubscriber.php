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
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   */
  public function __construct(CurrentPathStack $currentPath, RendererInterface $renderer, ThemeManagerInterface $themeManager, ConfigFactoryInterface $configFactory) {
    $this->currentPath = $currentPath;
    $this->renderer = $renderer;
    $this->themeManager = $themeManager;
    $this->configFactory = $configFactory;
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
      $build = [
        '#template' => 'page__404',
        '#title' => 'Page Permanently Deleted',
        '#markup' => 'The content you are looking for has been permanently removed.',
      ];
      $response = new HtmlResponse($build, 410);
      $event->setResponse($response);
    }
  }

}
