<?php

namespace Drupal\renify_tournament\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;

class CsvPairingProcessor {
  use StringTranslationTrait;

  protected $entityTypeManager;
  protected $database;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Prepares and sets the batch for CSV processing.
   */
  public function processCsvFile(File $file, $tournament_id) {
    $rows = [];
    if (($handle = fopen($file->getFileUri(), "r")) !== FALSE) {
      fgetcsv($handle, 1000, ",", "\"", "\\"); // Skip header
      while (($data = fgetcsv($handle, 1000, ",", "\"", "\\")) !== FALSE) {
        $rows[] = $data;
      }
      fclose($handle);
    }

    $batch = [
      'title' => $this->t('Processing Pairings...'),
      'operations' => [],
      'finished' => [self::class, 'finishBatch'],
    ];

    foreach ($rows as $index => $row) {
      $batch['operations'][] = [
        [self::class, 'processRow'],
        [$row, $index + 2, $tournament_id],
      ];
    }

    batch_set($batch);
  }

  public static function processRow($data, $row_num, $tournament_id, &$context) {
    // Inside static batch methods, we still use \Drupal::service
    // but the logic remains isolated here.
    $player_a = self::lookupEntityByField('players', 'field_dupr_id', $data[0]);
    $player_b = self::lookupEntityByField('players', 'field_dupr_id', $data[2]);

    if (!$player_a || !$player_b) {
      $missing = !$player_a ? $data[0] : $data[2];
      $context['results']['errors'][] = "Row $row_num: Player DUPR ID '$missing' not found.";
      return;
    }

    $cat_id = self::lookupTermByName('category', $data[4]);
    $brk_id = self::lookupTermByName('bracket', $data[5]);

    if (!$cat_id || !$brk_id) {
      $missing = !$cat_id ? "Category: $data[4]" : "Bracket: $data[5]";
      $context['results']['errors'][] = "Row $row_num: Taxonomy term '$missing' not found.";
      return;
    }

    $database = \Drupal::database();
    $query = $database->select('node__field_players', 'p1');
    $query->join('node__field_players', 'p2', 'p1.entity_id = p2.entity_id');
    $query->join('node__field_tournament_joined', 't', 'p1.entity_id = t.entity_id');

    $query->fields('p1', ['entity_id'])
      ->condition('p1.field_players_target_id', $player_a)
      ->condition('p2.field_players_target_id', $player_b)
      ->condition('t.field_tournament_joined_target_id', $tournament_id)
      ->range(0, 1);

    $nid = $query->execute()->fetchField();

    if ($nid) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      $node->set('field_category', $cat_id);
      $node->set('field_bracket', $brk_id);
      $node->set('field_remarks', $data[6]);
      $node->save();
      $context['results']['success']++;
      return;
    }

    $node = Node::create([
      'type' => 'pairings',
      'title' => trim($data[1]) . ' & ' . trim($data[3]),
      'field_players' => [$player_a, $player_b],
      'field_category' => $cat_id,
      'field_bracket' => $brk_id,
      'field_remarks' => $data[6],
      'field_tournament_joined' => $tournament_id,
    ]);
    $node->save();

    $context['results']['success']++;
  }

  public static function finishBatch($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $count = $results['success'] ?? 0;
      $messenger->addStatus(t('Successfully processed @count pairings.', ['@count' => $count]));
    }
    if (!empty($results['errors'])) {
      foreach ($results['errors'] as $error) {
        $messenger->addWarning($error);
      }
    }
  }

  protected static function lookupEntityByField($bundle, $field, $value) {
    $value = trim($value);
    if (empty($value)) return NULL;

    $ids = \Drupal::entityQuery('node')
      ->condition('type', $bundle)
      ->condition($field, $value)
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($ids)) return reset($ids);

    if ($bundle === 'players' && $field === 'field_dupr_id') {
      try {
        $api_client = \Drupal::service('renify_dupr.api_client');
        $new_player_node = $api_client->searchAndSyncPlayer($value);
        if ($new_player_node instanceof \Drupal\node\NodeInterface) {
          return $new_player_node->id();
        }
      } catch (\Exception $e) {
        \Drupal::logger('renify_tournament')->error($e->getMessage());
      }
    }
    return NULL;
  }

  protected static function lookupTermByName($vid, $name) {
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
      'vid' => $vid,
      'name' => trim($name),
    ]);
    $term = reset($terms);
    return $term ? $term->id() : NULL;
  }
}
