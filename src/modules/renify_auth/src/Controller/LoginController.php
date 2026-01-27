<?php

namespace Drupal\renify_auth\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoginController extends ControllerBase {

  // protected $externalAuth;

  // public function __construct($external_auth) {
  //   $this->externalAuth = $external_auth;
  // }

  // public static function create(ContainerInterface $container) {
  //   return new static(
  //     $container->get('externalauth.externalauth')
  //   );
  // }

  public function authenticate(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    // 1. Validate against your EXTERNAL SERVER
    // Use Guzzle or any HTTP client to check credentials.
    $is_valid_externally = $this->checkExternalServer($username, $password);

    if (!$is_valid_externally) {
      return new JsonResponse(['error' => 'Invalid credentials'], 401);
    }

    // 2. Map to Drupal using externalauth
    // 'renify' is the provider name; $username is the external ID.
    $account_data = ['mail' => $username, 'name' => $username];
    // $account = $this->externalAuth->loginRegister($username, 'renify', $account_data);

    if ($account) {
      // 3. Return your API Key / Token
      // If using Key Auth module:
      $api_key = $account->get('field_api_key')->value;

      return new JsonResponse([
        'status' => 'success',
        'uid' => $account->id(),
        'api_key' => $api_key,
        'roles' => $account->getRoles(),
      ]);
    }

    return new JsonResponse(['error' => 'Drupal login failed'], 500);
  }

  private function checkExternalServer($email, $pass) {
    // 1. Find the username associated with this email
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $accounts = $user_storage->loadByProperties(['mail' => $email]);
    $account = reset($accounts);

    if ($account) {
      $username = $account->getAccountName();

      // 2. Use the user.auth service with the retrieved username
      $user_auth = \Drupal::service('user.auth');

      // authenticate() returns the UID on success, or FALSE on failure
      $uid = $user_auth->authenticate($username, $pass);

      return (bool) $uid;
    }

    return FALSE;
  }
}
