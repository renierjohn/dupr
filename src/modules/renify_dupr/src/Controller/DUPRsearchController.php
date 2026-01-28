<?php

declare(strict_types=1);

namespace Drupal\renify_dupr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\ClientInterface;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\Html;
use Drupal\renify_dupr\Service\DuprApiClient;
/**
 * Returns responses for Renify dupr routes.
 */
final class DUPRsearchController extends ControllerBase {

  /**
   * The DUPR API Client service.
   *
   * @var \Drupal\renify_dupr\Service\DuprApiClient
   */
  protected $duprClient;

  public function __construct(DuprApiClient $dupr_client) {
    $this->duprClient = $dupr_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renify_dupr.api_client')
    );
  }

  /**
   * Search for a player locally or via DUPR API.
   */
  public function search(Request $request): CacheableJsonResponse {
      $q = Html::escape(trim($request->query->get('q', '')));

      if (empty($q)) {
        return new CacheableJsonResponse(['error' => 'Missing query'], 400);
      }

      try {
        $node = $this->duprClient->searchAndSyncPlayer($q);

        if ($node) {
          $data = [
            'full_name' => $node->getTitle(),
            'dupr_id' => $node->get('field_dupr_id')->value,
            'rating_doubles' => $node->get('field_rating_doubles')->value,
            'rating_singles' => $node->get('field_rating_singles')->value,
            'nid' => $node->id(),
          ];

          // Use CacheableJsonResponse instead of JsonResponse
          $response = new CacheableJsonResponse($data);

          // Now this method will exist!
          $cache_metadata = $response->getCacheableMetadata();
          $cache_metadata->addCacheContexts(['url.query_args:q']);
          $cache_metadata->addCacheTags(['config:renify_dupr.settings', 'node:' . $node->id()]);

          return $response;
        }
      } catch (\Exception $e) {
        return new CacheableJsonResponse(['error' => $e->getMessage()], 500);
      }

      return new CacheableJsonResponse(['message' => 'API Key missing'], 404);
    }
}
