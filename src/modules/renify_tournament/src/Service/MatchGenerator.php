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
          if ($this->checkMatchExists($tournament_id, $cat_id, $bracket_id, $match_pair)) {
            continue;
          }

          $match_node = Node::create([
            'type' => 'match_tournament',
            'title' => '#' . implode('-', $match_pair),
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
  protected function checkMatchExists($tournament_id, $cat_id, $bracket_id, $match_pair) {
    $player_a = $match_pair[0];
    $player_b = $match_pair[1];

    $database = \Drupal::database();
    $query = $database->select('node__field_match_pairings', 'm1');
    $query->join('node__field_match_pairings', 'm2', 'm1.entity_id = m2.entity_id');
    $query->join('node__field_tournament_id', 't', 'm1.entity_id = t.entity_id');
    // Optional: Add category/bracket joins if you want to be hyper-specific
    $query->join('node__field_category', 'c', 'm1.entity_id = c.entity_id');

    $query->fields('m1', ['entity_id'])->condition('m1.field_match_pairings_target_id', $player_a)->condition('m2.field_match_pairings_target_id', $player_b)->condition('t.field_tournament_id_target_id', $tournament_id)->condition('c.field_category_target_id', $cat_id)->range(0, 1);

    $result = $query->execute()->fetchField();

    return $result;
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
