<?php

namespace Drupal\renify_tournament\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;

class ResetMatchService {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public function run($tournament_id) {
    $matches = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => 'match_tournament',
      'field_tournament_id' => $tournament_id
    ]);

    foreach ($matches as $match) {
      $match->set('field_match_score_a', 0);
      $match->set('field_match_score_b', 0);
      $match->save();
    }

    $pairings = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => 'pairings',
      'field_tournament_joined' => $tournament_id
    ]);

    foreach ($pairings as $pair) {
      $pair->set('field_points_against', 0);
      $pair->set('field_points_for', 0);
      $pair->set('field_score_deviation', 0);
      $pair->set('field_win', 0);
      $pair->set('field_loss', 0);
      $pair->set('field_status', [16]);
      $pair->save();
    }
  }
}
