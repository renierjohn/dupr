<?php

namespace Drupal\renify_url_alias\EventSubscriber;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects /node/ID to its alias if one exists.
 */
class RouteNormalizerSubscriber implements EventSubscriberInterface {

  protected $aliasManager;
  protected $currentPath;

  public function __construct(AliasManagerInterface $alias_manager, CurrentPathStack $current_path) {
    $this->aliasManager = $alias_manager;
    $this->currentPath = $current_path;
  }

  public function onKernelRequest(RequestEvent $event) {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();
    // Get the raw URI from the browser (e.g., /node/12)
    $raw_path = $request->getPathInfo();

    // 1. Only act if the browser is explicitly asking for /node/ID
    if (preg_match('/^\/node\/\d+$/', $raw_path)) {
      $system_path = $this->currentPath->getPath($request);
      $alias = $this->aliasManager->getAliasByPath($system_path);

      // 2. Only redirect if an alias exists and it's different from what's in the browser
      if ($alias !== $raw_path) {
        $query = $request->getQueryString();
        $destination = $alias . ($query ? '?' . $query : '');

        $response = new RedirectResponse($destination, 301);
        $event->setResponse($response);
      }
    }
  }

  public static function getSubscribedEvents() {
    // Priority must be high enough to run before the route is fully processed.
    $events[KernelEvents::REQUEST][] = ['onKernelRequest', 30];
    return $events;
  }
}
