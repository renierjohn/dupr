<?php

namespace Drupal\renify_tournament\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Component\Utility\Bytes;
use Drupal\renify_tournament\Service\CsvPairingProcessor;
use Drupal\renify_tournament\Service\MatchGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PairingCsvUploadForm extends FormBase {

  protected $csvProcessor;

  protected $matchGenerator;

  public function __construct(CsvPairingProcessor $csv_processor, MatchGenerator $match_generator) {
    $this->csvProcessor = $csv_processor;
    $this->matchGenerator = $match_generator;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renify_tournament.csv_processor'),
      $container->get('renify_tournament.match_generator')
    );
  }

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
      '#upload_location' => 'public://csv-imports/',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload to Generate Pairings'),
      '#button_type' => 'primary',
    ];

    $form['actions']['generate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate Matches'),
      '#button_type' => 'secondary',
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

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the element that triggered the submission.
    $triggering_element = $form_state->getTriggeringElement();

    // Get the array key of the button (e.g., 'submit' or 'generate').
    $button_clicked = end($triggering_element['#parents']);

    switch ($button_clicked) {
      case 'submit':
        $this->handleCsvUpload($form_state);
        break;

      case 'generate':
        $this->handleMatchGeneration($form_state);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function handleCsvUpload(FormStateInterface $form_state) {
    $fid = $form_state->getValue(['csv_file', 0]);
    if ($fid && ($file = File::load($fid))) {
      $tournament_id = $form_state->getValue('tournament_id');
      // Call the service!
      $this->csvProcessor->processCsvFile($file, $tournament_id);
    }
    else {
      \Drupal::messenger()->addError($this->t('Please upload a CSV file.'));
    }
  }

  /**
   * Handle Match Generation button.
   */
  protected function handleMatchGeneration(FormStateInterface $form_state) {
    $tournament_id = $form_state->getValue('tournament_id');

    if ($tournament_id) {
      $count = $this->matchGenerator->generateMatches($tournament_id);

      if ($count > 0) {
        \Drupal::messenger()->addStatus($this->t('Successfully generated @count matches.', ['@count' => $count]));
      } else {
        \Drupal::messenger()->addWarning($this->t('No pairings found to generate matches.'));
      }
    }
  }
}
