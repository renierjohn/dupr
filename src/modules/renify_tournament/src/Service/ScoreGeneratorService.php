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

    $pair_a = $this->getScore_PF_PA($node, $pairings[0]->entity);
    $pair_b = $this->getScore_PF_PA($node, $pairings[1]->entity);

    $pairings[0]->entity
      ->set('field_win', $pair_a['win'])
      ->set('field_loss', $pair_a['loss'])
      ->set('field_points_for', $pair_a['pf'])
      ->set('field_points_against', $pair_a['pa'])
      ->set('field_score_deviation', $pair_a['pd'])
      ->save();

    $pairings[1]->entity
      ->set('field_win', $pair_b['win'])
      ->set('field_loss', $pair_b['loss'])
      ->set('field_points_for', $pair_b['pf'])
      ->set('field_points_against', $pair_b['pa'])
      ->set('field_score_deviation', $pair_b['pd'])
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

  protected function getScore_PF_PA($node, $pairing_entity) {
    $team_win_arr = $this->getMatchResultNodes($node, $pairing_entity, 'field_match_winner');
    $team_loss_arr = $this->getMatchResultNodes($node, $pairing_entity, 'field_match_lossser');

    $team_PF = 0;
    $team_PA = 0;

    $team_win_count = 0;
    $team_loss_count = 0;

    foreach ($team_win_arr as $team_win) {
      $pairings = $team_win->field_match_pairings;

      // Check the array position of winner team.
      if ($pairings[0]->target_id == $pairing_entity->id()) {
        $score_win = $team_win->field_match_score_a->value;
        $score_loss = $team_win->field_match_score_b->value;

        $team_PF = intval($score_win) + $team_PF;
        $team_PA = intval($score_loss) + $team_PA;

        $team_win_count = $team_win_count + 1;
      } else {
        $score_win = $team_win->field_match_score_b->value;
        $score_loss = $team_win->field_match_score_a->value;

        $team_PF = intval($score_win) + $team_PF;
        $team_PA = intval($score_loss) + $team_PA;

        $team_win_count = $team_win_count + 1;
      }
    }

    foreach ($team_loss_arr as $team_loss) {
      $pairings = $team_loss->field_match_pairings;

      // Check the array position of losser team.
      if ($pairings[0]->target_id == $pairing_entity->id()) {
        $score_win = $team_loss->field_match_score_b->value;
        $score_loss = $team_loss->field_match_score_a->value;

        $team_PF = intval($score_loss) + $team_PF;
        $team_PA = intval($score_win) + $team_PA;

        $team_loss_count = $team_loss_count + 1;
      } else {
        $score_win = $team_loss->field_match_score_a->value;
        $score_loss = $team_loss->field_match_score_b->value;

        $team_PF = intval($score_loss) + $team_PF;
        $team_PA = intval($score_win) + $team_PA;

        $team_loss_count = $team_loss_count + 1;
      }
    }

    return [
      'win' => $team_win_count,
      'loss' => $team_loss_count,
      'pf' => $team_PF,
      'pa' => $team_PA,
      'pd' => ($team_PF - $team_PA)
    ];
  }

}
