<?php

declare(strict_types=1);

namespace Drupal\renify_dupr\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\node\NodeInterface;
use GuzzleHttp\ClientInterface;

class DuprRefreshService {

  public function __construct(
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileRepositoryInterface $fileRepository // Injected
  ) {}

  public function refreshAllPlayers(): void {
    $token = $this->configFactory->get('renify_dupr.settings')->get('secretkey');
    if (!$token) return;

    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'players')
      ->accessCheck(FALSE)
      ->execute();

    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    foreach ($nodes as $node) {
      $userId = $node->get('field_user_id')->value;
      if ($userId) {
        $this->refreshSinglePlayer($node, $userId, $token);
      }
    }
  }

  protected function refreshSinglePlayer(NodeInterface $node, $userId, $token): void {
    try {
      $response = $this->httpClient->request('GET', "https://api.dupr.gg/player/v1.0/$userId", [
        'headers' => ['Authorization' => 'Bearer ' . $token],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      if ($data['status'] === 'SUCCESS') {
        $res = $data['result'];

        // Update Ratings
        $node->set('field_rating_doubles', is_numeric($res['ratings']['doubles']) ? $res['ratings']['doubles'] : 0);
        $node->set('field_rating_singles', is_numeric($res['ratings']['singles']) ? $res['ratings']['singles'] : 0);

        // Update Image
        if (!empty($res['imageUrl'])) {
          $this->updatePlayerImage($node, $res['imageUrl']);
        }

        $node->save();
      }
    } catch (\Exception $e) {
      \Drupal::logger('renify_dupr')->error("Failed refresh for ID $userId: " . $e->getMessage());
    }
  }

  protected function updatePlayerImage(NodeInterface $node, string $url): void {
    try {
      $imageData = $this->httpClient->request('GET', $url)->getBody()->getContents();

      if ($imageData) {
        $filename = basename(parse_url($url, PHP_URL_PATH));

        // 1. Define the variable first (PHP 8.4 requirement)
        $directory = "public://dupr";
        $destination = $directory . '/' . $filename;

        // 2. Pass the variable to the method
        // FileSystemInterface::prepareDirectory expects a reference to a variable
        \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

        $file = $this->fileRepository->writeData($imageData, $destination, FileSystemInterface::EXISTS_REPLACE);

        if ($file) {
          $node->set('field_image', [
            'target_id' => $file->id(),
            'alt' => $node->getTitle() . ' Profile Image',
          ]);
        }
      }
    } catch (\Exception $e) {
       \Drupal::logger('renify_dupr')->error("Image download failed for " . $node->getTitle() . ": " . $e->getMessage());
    }
  }
}
