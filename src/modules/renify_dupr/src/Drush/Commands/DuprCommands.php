<?php

namespace Drupal\renify_dupr\Drush\Commands;

use Drush\Commands\DrushCommands;
use Drupal\renify_dupr\Service\DuprRefreshService;
use Drush\Attributes as CLI;

class DuprCommands extends DrushCommands {

  public function __construct(
    protected DuprRefreshService $refreshService
  ) {
    parent::__construct();
  }

  #[CLI\Command(name: 'dupr:fetch', aliases: ['df'])]
  #[CLI\Help(description: 'Syncs all player ratings and images from DUPR API.')]
  public function fetch() {
    $this->output()->writeln('Starting DUPR Sync...');
    $this->refreshService->refreshAllPlayers();
    $this->logger()->success('DUPR Sync Complete.');
  }
}
