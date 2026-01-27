<?php

declare(strict_types=1);

namespace Drupal\renify_dupr\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Service to handle DUPR API interactions.
 */
class DuprApiClient {

  public function __construct(
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager
  ) {}

  /**
   * Performs the external search and syncs to a local node.
   */
  public function searchAndSyncPlayer(string $query): ?NodeInterface {

    $storage = $this->entityTypeManager->getStorage('node');
    $existing = $storage->loadByProperties([
      'type' => 'players',
      'field_dupr_id' => $query
    ]);

    if ($existing) {
      return reset($existing);
    }

    $existing = $storage->loadByProperties([
      'type' => 'players',
      'title' => $query
    ]);

    if ($existing) {
      return reset($existing);
    }

    $data = $this->requestToDUPR($query);

    if ($data['status'] === 'SUCCESS' && !empty($data['result']['hits'])) {
      return $this->syncPlayerNode($data['result']['hits'][0]);
    }

    return NULL;
  }

  /**
   * Fetch DUPR stats.
   */
  protected function requestToDUPR(string $query) {
    $config = $this->configFactory->get('renify_dupr.settings');
    $token = $config->get('secretkey');

    if (empty($token)) {
      throw new \Exception('DUPR API Secret Key is not configured.');
    }

    $response = $this->httpClient->request('POST', 'https://api.dupr.gg/player/v1.0/search', [
      'json' => [
        'limit' => 1,
        'offset' => 0,
        'query' => $query,
        'exclude' => [],
        'includeUnclaimedPlayers' => true,
        'filter' => [
          'lat' => 9.1990827,
          'lng' => 123.2363246,
          'rating' => ['maxRating' => null, 'minRating' => null],
          'locationText' => "",
        ],
      ],
      'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type'  => 'application/json',
        'Accept'        => 'application/json',
      ],
    ]);

    return json_decode($response->getBody()->getContents(), TRUE);
  }

  /**
   * Upserts the player node.
   */
  protected function syncPlayerNode(array $p): NodeInterface {
    $storage = $this->entityTypeManager->getStorage('node');

    $existing = $storage->loadByProperties([
      'type' => 'players',
      'field_dupr_id' => $p['duprId']
    ]);

    if ($existing) {
      return reset($existing);
    }

    $clean_rating = fn($val) => is_numeric($val) ? $val : 0;
    $node = $storage->create(['type' => 'players']);
    $node->setTitle($p['fullName']);
    $node->set('field_age', $p['age'] ?? NULL);
    $node->set('field_user_id', $p['id']);
    $node->set('field_dupr_id', $p['duprId']);
    $node->set('field_gender', $p['gender']);
    $node->set('field_location', $p['shortAddress']);
    $node->set('field_verified', (int) ($p['verifiedEmail'] ?? 0));
    $node->set('field_rating_doubles', $clean_rating($p['ratings']['doubles'] ?? 0));
    $node->set('field_rating_singles', $clean_rating($p['ratings']['singles'] ?? 0));
    $node->setPublished(TRUE);

    $node->save();
    return $node;
  }
}
