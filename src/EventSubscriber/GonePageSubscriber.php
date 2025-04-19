<?php

namespace Drupal\qe_410\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;

/**
 * Subscribes to requests for content that has been permanently deleted.
 */
class GonePageSubscriber implements EventSubscriberInterface {

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The main content renderer.
   *
   * @var \Drupal\Core\Render\MainContent\MainContentRendererInterface
   */
  protected $mainContentRenderer;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new GonePageSubscriber object.
   *
   * @param \Drupal\Core\Path\CurrentPathStack $currentPath
   *   The current path service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Render\MainContent\MainContentRendererInterface $mainContentRenderer
   *   The main content renderer.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(
    CurrentPathStack $currentPath,
    ConfigFactoryInterface $configFactory,
    MainContentRendererInterface $mainContentRenderer,
    RouteMatchInterface $routeMatch,
  ) {
    $this->currentPath = $currentPath;
    $this->configFactory = $configFactory;
    $this->mainContentRenderer = $mainContentRenderer;
    $this->routeMatch = $routeMatch;
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
   * Handles request events to display a 410 Gone page when needed.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event) {
    $path = $this->currentPath->getPath();
    $fields = $this->configFactory->get('qe_410.settings')->get('fields') ?? [];

    if (in_array($path, $fields)) {
      // Define the render array for the custom 410 message.
      $full_url = "<strong>{$event->getRequest()->getUri()}</strong>";

      $build = [
        '#markup' => "<div class='message-box'><h2>This Page Is No Longer Available (410 Gone)</h2><p>{$full_url} has been permanently removed.<br />

But don't worry, you can head back to our homepage at <a href='https://qe2foundation.ca/'>QE2Foundation.ca</a> to find what you're looking for!</p></div>",
        '#type' => 'markup',
        '#cache' => ['max-age' => 0],
      ];

      // Render the response using Drupal theming.
      $response = $this->mainContentRenderer->renderResponse(
        $build,
        $event->getRequest(),
        $this->routeMatch
      );

      // Set the appropriate HTTP status code.
      $response->setStatusCode(410);

      // Set the custom response.
      $event->setResponse($response);
    }
  }

}
