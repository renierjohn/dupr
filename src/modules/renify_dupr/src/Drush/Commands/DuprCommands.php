<?php

namespace Drupal\renify_dupr\Drush\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\renify_dupr\Service\DuprRefreshService;
use Drupal\renify_dupr\Service\DUPRmatchService;
use Drush\Attributes as CLI;

class DuprCommands extends DrushCommands {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected DuprRefreshService $refreshService,
    protected DUPRmatchService $matchService
  ) {
    parent::__construct();
  }

  #[CLI\Command(name: 'dupr:fetch_player', aliases: ['dfp'])]
  #[CLI\Help(description: 'Syncs all player ratings and images from DUPR API.')]
  public function fetchPlayer() {
    $this->output()->writeln('Starting DUPR Sync...');
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'players')
      ->accessCheck(FALSE);
    $nids = $query->execute();

    if (empty($nids)) {
      $this->output()->writeln('No players found to sync.');
      return;
    }

    $operations = [];
    foreach ($nids as $nid) {
      // Define the service method to call for each nid
      $operations[] = [
        '\Drupal\renify_dupr\Batch\DuprBatchProcessor::processPlayers',
        [$nid],
      ];
    }

    $batch = [
      'title' => 'Syncing DUPR Matches',
      'operations' => $operations
    ];

    batch_set($batch);
    drush_backend_batch_process();

    $this->output()->writeln('Batch process initiated.');
  }

  #[CLI\Command(name: 'dupr:fetch_match', aliases: ['dfm'])]
  #[CLI\Help(description: 'Syncs all player ratings and images from DUPR API.')]
  public function fetchMatch() {
    $this->output()->writeln('Starting Match Sync & DUPR...');
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'players')
      ->accessCheck(FALSE);
    $nids = $query->execute();

    if (empty($nids)) {
      $this->output()->writeln('No players found to sync.');
      return;
    }

    $operations = [];
    foreach ($nids as $nid) {
      // Define the service method to call for each nid
      $operations[] = [
        '\Drupal\renify_dupr\Batch\DuprBatchProcessor::process',
        [$nid],
      ];
    }

    $batch = [
      'title' => 'Syncing DUPR Matches',
      'operations' => $operations
    ];

    batch_set($batch);
    drush_backend_batch_process();

    $this->output()->writeln('Batch process initiated.');
  }
}
