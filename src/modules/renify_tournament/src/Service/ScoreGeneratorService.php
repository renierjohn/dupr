<?php

namespace Drupal\renify_tournament\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class ScoreGeneratorService {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public function save(NodeInterface $entity) {
    $score_a = $entity->field_match_score_a->value;
    $score_b = $entity->field_match_score_b->value;
    $pairings = $entity->field_match_pairings;


    if (intval($score_a) > intval($score_b)) {
      $entity->set('field_match_winner', $pairings[0]->entity->id());
      $entity->set('field_match_lossser', $pairings[1]->entity->id());
    } else {
      $entity->set('field_match_winner', $pairings[1]->entity->id());
      $entity->set('field_match_lossser', $pairings[0]->entity->id());
    }

    $this->saveCalculatedPoints($entity, $pairings, $score_a, $score_b);

  }

  protected function saveCalculatedPoints($node, $pairings, $score_a, $score_b) {
    $team_a_win_count = 0;
    $team_a_loss_count = 0;
    $team_a_PF = 0;
    $team_a_PA = 0;
    $team_a_Div = 0;

    $team_b_win_count = 0;
    $team_b_loss_count = 0;
    $team_b_PF = 0;
    $team_b_PA = 0;
    $team_b_Div = 0;

    $team_a_winner = $this->getMatchResultNodes($node, $pairings[0]->entity, 'field_match_winner');
    $team_a_losser = $this->getMatchResultNodes($node, $pairings[0]->entity, 'field_match_lossser');

    $team_b_winner = $this->getMatchResultNodes($node, $pairings[1]->entity, 'field_match_winner');
    $team_b_losser = $this->getMatchResultNodes($node, $pairings[1]->entity, 'field_match_lossser');

    foreach ($team_a_winner as $team_a_win) {
      $score_a = $team_a_win->field_match_score_a->value;
      $team_a_PF += intval($score_a);

      $score_b = $team_a_win->field_match_score_b->value;
      $team_a_PA += intval($score_b);
    }

    foreach ($team_a_losser as $team_a_loss) {
      $score_a = $team_a_loss->field_match_score_a->value;
      $team_a_PF += intval($score_a);

      $score_b = $team_a_loss->field_match_score_b->value;
      $team_a_PA += intval($score_b);
    }

    foreach ($team_b_winner as $team_b_win) {
      $score_b = $team_b_win->field_match_score_b->value;
      $team_b_PF += intval($score_b);

      $score_a = $team_b_win->field_match_score_a->value;
      $team_b_PA += intval($score_a);
    }

    foreach ($team_b_losser as $team_b_loss) {
      $score_b = $team_b_loss->field_match_score_b->value;
      $team_b_PF += intval($score_b);

      $score_a = $team_b_loss->field_match_score_a->value;
      $team_b_PA += intval($score_a);
    }


    $team_a_win_count = count($team_a_winner);
    $team_a_loss_count = count($team_a_losser);

    $team_b_win_count = count($team_b_winner);
    $team_b_loss_count = count($team_b_losser);

    $team_a_Div = $team_a_PF - $team_a_PA;
    $team_b_Div = $team_b_PF - $team_b_PA;

// dump($team_b_win_count, $team_b_loss_count, $team_b_PF, $team_b_PA, $team_b_Div, $team_b_losser);

    $pairings[0]->entity
      ->set('field_win', $team_a_win_count)
      ->set('field_loss', $team_a_loss_count)
      ->set('field_points_for', $team_a_PF)
      ->set('field_points_against', $team_a_PA)
      ->set('field_score_deviation', $team_a_Div)
      ->save();

    $pairings[1]->entity
      ->set('field_win', $team_b_win_count)
      ->set('field_loss', $team_b_loss_count)
      ->set('field_points_for', $team_b_PF)
      ->set('field_points_against', $team_b_PA)
      ->set('field_score_deviation', $team_b_Div)
      ->save();
  }

  protected function getMatchResultNodes($node, $pairings, $win_loss_field = 'field_match_winner') {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    // 1. Basic properties (Equality)
    $query->condition('type', 'match_tournament')
      ->condition('field_tournament_id', $node->field_tournament_id->entity->id())
      ->condition('field_category', $node->field_category->entity->id())
      ->condition('field_bracket', $node->field_bracket->entity->id())
      ->condition($win_loss_field, $pairings->id());

    // 2. Score Logic: Score A > Score B
    // Note: We use the field name twice to compare them
    // $query->condition('field_match_score_a', 'field_match_score_b', $operation);

    // 3. Ensure neither score is 0
    $query->condition('field_match_score_a', 0, '<>')
      ->condition('field_match_score_b', 0, '<>');

    // 4. Access check is mandatory in Drupal 11
    $query->accessCheck(FALSE);

    $nids = $query->execute();

    // 5. Load the actual node entities
    return $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
  }

  protected function getScorePL() {

  }

  protected function getScorePA() {

  }

  protected function getScoreDeviation() {

  }
}
