<?php

namespace Drupal\renify_tournament\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;

class MatchGenerator {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Generates Round Robin matches for a specific tournament.
   */
  public function generateMatches($tournament_id) {
    $storage = $this->entityTypeManager->getStorage('node');

    // 1. Fetch all pairings for this tournament.
    $query = $storage->getQuery()
      ->condition('type', 'pairings')
      ->condition('field_tournament_joined', $tournament_id)
      ->accessCheck(FALSE);
    $nids = $query->execute();

    if (empty($nids)) {
      return 0;
    }

    $nodes = $storage->loadMultiple($nids);
    $grouped_pairings = [];

    // 2. Group pairings by Category and Bracket.
    foreach ($nodes as $node) {
      $cat = $node->get('field_category')->target_id;
      $bracket = $node->get('field_bracket')->target_id;
      $grouped_pairings[$cat][$bracket][] = $node->id();
    }

    $match_count = 0;

    // 3. Process each group to create Round Robin matches.
    foreach ($grouped_pairings as $cat_id => $brackets) {
      foreach ($brackets as $bracket_id => $pairing_nids) {
        $matches = $this->buildRoundRobinPairs($pairing_nids);

        foreach ($matches as $match_pair) {
          $match_node = Node::create([
            'type' => 'match_tournament',
            'title' => 'Match: #' . implode('-', $match_pair),
            'field_match_pairings' => $match_pair, // Store the two Pairing NIDs
            'field_tournament_id' => $tournament_id,
            'field_category' => $cat_id,
            'field_bracket' => $bracket_id
          ]);
          $match_node->save();
          $match_count++;
        }
      }
    }

    return $match_count;
  }

  /**
   * Simple Round Robin Pairing logic (Unique combinations).
   */
  protected function buildRoundRobinPairs(array $items) {
    $pairs = [];
    $count = count($items);

    // Compare every item against every subsequent item once.
    for ($i = 0; $i < $count; $i++) {
      for ($j = $i + 1; $j < $count; $j++) {
        $pairs[] = [$items[$i], $items[$j]];
      }
    }
    return $pairs;
  }
}
