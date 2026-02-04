<?php

namespace Drupal\renify_tournament\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Component\Utility\Bytes;

class PairingCsvUploadForm extends FormBase {

  public function getFormId() {
    return 'renify_tournament_pairing_csv_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $tournament_id = NULL) {
    $form['tournament_id'] = [
      '#type' => 'hidden',
      '#value' => $tournament_id,
    ];

    $form['sample_link'] = [
      '#type' => 'item',
      '#markup' => '<a href="/tournaments/sample-csv" class="button button--small">' . $this->t('Download Sample CSV') . '</a>',
      '#description' => $this->t('Download this template to ensure your headers match our system.'),
    ];

    $form['csv_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload Pairings CSV'),
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'csv'],
        'FileSizeLimit' => ['fileLimit' => Bytes::toNumber('10MB')],
      ],
      '#description' => $this->t('Max size: 10MB. Format: CSV only.'),
      '#required' => TRUE,
      '#upload_location' => 'public://csv-imports/',
    ];


    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate Pairings'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  $fid = $form_state->getValue(['csv_file', 0]);
  if (!$fid) return;

  $file = File::load($fid);
  if (($handle = fopen($file->getFileUri(), "r")) !== FALSE) {
    // PHP 8.3 Fix: Explicitly provide delimiter, enclosure, and escape
    $header = fgetcsv($handle, 1000, ",", "\"", "\\");
    fclose($handle);

    $expected_columns = [
      "DUPR ID Player A", "Name Player A", "DUPR ID Player B",
      "Name Player B", "Category", "Bracket", "Remarks"
    ];

    if (!$header) {
      $form_state->setErrorByName('csv_file', $this->t('The CSV file is empty or unreadable.'));
      return;
    }

    $header = array_map('trim', (array) $header);

    if (count($header) !== count($expected_columns)) {
      $form_state->setErrorByName('csv_file', $this->t('Invalid format. Expected @count columns, found @found.', [
        '@count' => count($expected_columns),
        '@found' => count($header),
      ]));
    }
  }
}

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fid = $form_state->getValue(['csv_file', 0]);
    $file = File::load($fid);
    $tournament_id = $form_state->getValue('tournament_id');

    $rows = [];
    if (($handle = fopen($file->getFileUri(), "r")) !== FALSE) {
      // Fix: Added explicit escape parameter
      fgetcsv($handle, 1000, ",", "\"", "\\");

      while (($data = fgetcsv($handle, 1000, ",", "\"", "\\")) !== FALSE) {
        $rows[] = $data;
      }
      fclose($handle);
    }

    $batch = [
      'title' => $this->t('Processing Pairings...'),
      'operations' => [],
      'finished' => [static::class, 'finishBatch'],
    ];

    foreach ($rows as $index => $row) {
      $batch['operations'][] = [
        [static::class, 'processRow'],
        [$row, $index + 2, $tournament_id],
      ];
    }

    batch_set($batch);
  }

  /**
   * Batch Operation: Processes a single row.
   */
  public static function processRow($data, $row_num, $tournament_id, &$context) {
    // We need to re-initialize services inside static batch methods
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    // 1. Lookup Players
    $player_a = static::lookupEntityByField('players', 'field_dupr_id', $data[0]);
    $player_b = static::lookupEntityByField('players', 'field_dupr_id', $data[2]);

    if (!$player_a || !$player_b) {
      $missing = !$player_a ? $data[0] : $data[2];
      $context['results']['errors'][] = "Row $row_num: Player DUPR ID '$missing' not found.";
      return;
    }

    // 2. Lookup Taxonomy
    $cat_id = static::lookupTermByName('category', $data[4]);
    $brk_id = static::lookupTermByName('bracket', $data[5]);

    if (!$cat_id || !$brk_id) {
      $missing = !$cat_id ? "Category: $data[4]" : "Bracket: $data[5]";
      $context['results']['errors'][] = "Row $row_num: Taxonomy term '$missing' not found.";
      return;
    }

    // 3. Create Node
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

  /**
   * Batch Finished callback.
   */
  public static function finishBatch($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $count = $results['success'] ?? 0;
      $messenger->addStatus(t('Successfully created @count pairings.', ['@count' => $count]));
    }
    if (!empty($results['errors'])) {
      foreach ($results['errors'] as $error) {
        $messenger->addError($error);
      }
    }
  }

   // --- Helpers ---
   protected static function lookupEntityByField($bundle, $field, $value) {
    $value = trim($value);
    if (empty($value)) return NULL;

    // 1. Try to find the player in the local database.
    $ids = \Drupal::entityQuery('node')
      ->condition('type', $bundle)
      ->condition($field, $value)
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($ids)) {
      return reset($ids);
    }

    // 2. If not found and it's a player, try to sync from the API.
    if ($bundle === 'players' && $field === 'field_dupr_id') {
      try {
        /** @var \Drupal\renify_dupr\ApiClientService $api_client */
        $api_client = \Drupal::service('renify_dupr.api_client');

        // This method is expected to fetch data and return a Node object.
        $new_player_node = $api_client->searchAndSyncPlayer($value);

        if ($new_player_node instanceof \Drupal\node\NodeInterface) {
          return $new_player_node->id();
        }
      }
      catch (\Exception $e) {
        // Log the error if the API call fails so we don't crash the batch.
        \Drupal::logger('renify_tournament')->error('API Sync failed for DUPR ID @id: @message', [
          '@id' => $value,
          '@message' => $e->getMessage(),
        ]);
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
