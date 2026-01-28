<?php

namespace Drupal\renify_dupr\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;

/**
 * Service to sync DUPR match history.
 */
class DUPRmatchService {

  public function __construct(
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerChannelFactoryInterface $loggerFactory
  ) {}

  /**
   * Batch callback for processing a single player.
   */
  public function processPlayerBatch(int $nid, array &$context): void {
    $storage = $this->entityTypeManager->getStorage('node');
    $player = $storage->load($nid);

    if ($player && !$player->get('field_user_id')->isEmpty()) {
      $uid = $player->get('field_user_id')->value;
      $this->fetchMatchesForUser($uid);

      // Update batch message
      $context['message'] = "Syncing matches for player: " . $player->label();
      $context['results'][] = $nid;
    }
  }

  /**
   * Main entry point to sync all players.
   */
  public function syncAll(): void {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'players')
      ->accessCheck(FALSE);
    $nids = $query->execute();

    if (empty($nids)) {
      return;
    }

    $players = $storage->loadMultiple($nids);
    foreach ($players as $player) {
      if (!$player->get('field_user_id')->isEmpty()) {
        $this->fetchMatchesForUser($player->get('field_user_id')->value);
      }
    }
  }

  /**
   * Fetches and processes matches using a POST request with filters.
   */
  public function fetchMatchesForUser(string $userId): void {
    $url = "https://api.dupr.gg/player/v1.0/{$userId}/history";

    // Define the specific body structure required by the API.
    $body = [
      'filters' => [
        'eventFormat' => null,
      ],
      'limit' => 2,
      'offset' => 0,
      'sort' => [
        'order' => 'DESC',
        'parameter' => 'MATCH_DATE',
      ],
    ];

    try {
      // Switch to 'POST' and use the 'json' key to send the body.
      $token = $this->configFactory->get('renify_dupr.settings')->get('secretkey');
      $response = $this->httpClient->request('POST', $url, [
         'headers' => ['Authorization' => 'Bearer ' . $token],
        'json' => $body,
      ]);

      $data = Json::decode($response->getBody()->getContents());

      if (($data['status'] ?? '') === 'SUCCESS' && !empty($data['result']['hits'])) {
        foreach ($data['result']['hits'] as $matchData) {
          $this->processMatch($matchData);
        }
      }
    } catch (\Exception $e) {
      $this->loggerFactory->get('renify_dupr')->error('Sync failed for ID @id: @msg', [
        '@id' => $userId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  protected function processMatch(array $data): void {
    $storage = $this->entityTypeManager->getStorage('node');

    // Duplicate check
    $existing = $storage->getQuery()
      ->condition('type', 'matches')
      ->condition('field_match_id', $data['id'])
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($existing)) return;

    // Process Team A (index 0)
    $teamAData = $data['teams'][0] ?? null;
    $teamANids = $this->getMemberNids($teamAData);
    $scoreA = $this->extractScores($teamAData);

    // Process Team B (index 1)
    $teamBData = $data['teams'][1] ?? null;
    $teamBNids = $this->getMemberNids($teamBData);
    $scoreB = $this->extractScores($teamBData);

    // Identify Winner
    $winnerNids = [];
    if ($teamAData['winner'] ?? FALSE) {
      $winnerNids = $teamANids;
    } elseif ($teamBData['winner'] ?? FALSE) {
      $winnerNids = $teamBNids;
    }

    $storage->create([
      'type'               => 'matches',
      'title'              => "Match #{$data['id']}",
      'field_match_id'     => $data['id'],
      'field_verified'     => (bool) ($data['confirmed'] ?? FALSE),
      'field_venue'        => $data['venue'] ?? '',
      'field_location'     => $data['location'] ?? '',
      'field_event_format' => $data['eventFormat'] ?? '',
      'field_event_date'   => strtotime($data['eventDate']),
      'field_team_a'       => $teamANids,
      'field_team_b'       => $teamBNids,
      'field_score_a'      => $scoreA, // Multi-value integer field
      'field_score_b'      => $scoreB, // Multi-value integer field
      'field_team_winner'  => $winnerNids,
    ])->save();
  }

  protected function getMemberNids(?array $team): array {
    if (!$team) return [];

    $nids = [];
    foreach (['player1', 'player2'] as $key) {
      if (!empty($team[$key])) {
        $nids[] = $this->ensurePlayerNode($team[$key]);
      }
    }
    return $nids;
  }

  /**
   * Helper to extract games into an array, filtering out -1.
   */
  protected function extractScores(?array $team): array {
    if (!$team) return [];

    $scores = [];
    for ($i = 1; $i <= 5; $i++) {
      $val = $team["game$i"] ?? -1;
      if ($val != -1) {
        $scores[] = (int) $val;
      }
    }
    return $scores;
  }

  protected function ensurePlayerNode(array $playerData): int|string {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'players')
      ->condition('field_user_id', $playerData['id'])
      ->accessCheck(FALSE);
    $ids = $query->execute();

    if (!empty($ids)) {
      return reset($ids);
    }

    $playerNode = $storage->create([
      'type'          => 'players',
      'title'         => $playerData['fullName'],
      'field_user_id' => $playerData['id'],
      'field_dupr_id' => $playerData['duprId'],
    ]);
    $playerNode->save();

    return $playerNode->id();
  }
}
