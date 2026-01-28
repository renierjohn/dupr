<?php

namespace Drupal\renify_dupr\Batch;

/**
 * Static class to handle batch operations without Container serialization issues.
 */
class DuprBatchProcessor {

  /**
   * Batch process callback.
   */
  public static function process(int $nid, array &$context): void {
    // Call the service via the global container here
    \Drupal::service('renify_dupr.match_service')->processPlayerBatch($nid, $context);
  }


  /**
   * Batch process callback.
   */
  public static function processPlayers(int $nid, array &$context): void {
    // Call the service via the global container here
    \Drupal::service('renify_dupr.refresh_service')->refreshAllPlayersByBatch($nid, $context);
    // \Drupal::service('renify_dupr.match_service')->processPlayerBatch($nid, $context);
  }

}
