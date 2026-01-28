<?php

declare(strict_types=1);

namespace Drupal\renify_dupr\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Configure Renify dupr settings for this site.
 */
final class DUPRloginConfigForm extends ConfigFormBase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a DUPRloginConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   * The typed config manager.
   * @param \GuzzleHttp\ClientInterface $http_client
   * The HTTP client.
   */
  public function __construct($config_factory, $typed_config_manager, ClientInterface $http_client) {
    // We pass both required arguments to the parent ConfigFormBase
    parent::__construct($config_factory, $typed_config_manager);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'), // This satisfies the 2nd argument
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'renify_dupr_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['renify_dupr.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('renify_dupr.settings');

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username (Email)'),
      '#default_value' => $config->get('username'),
      '#required' => TRUE,
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $config->get('name'),
      '#disabled' => TRUE,
    ];

    $form['dupr_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User ID'),
      '#default_value' => $config->get('dupr_id'),
      '#disabled' => TRUE,
    ];

    $form['secretkey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token (Secret Key)'),
      '#default_value' => $config->get('secretkey'),
      '#disabled' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $email = $form_state->getValue('username');
    $password = $form_state->getValue('password');

    $auth_data = $this->loginDuprAccount($email, $password);

    if (!$auth_data) {
      $form_state->setErrorByName('username', $this->t('Failed to authenticate with DUPR. Please check your credentials.'));
    } else {
      // Store the API results in the form state to use them in submitForm
      $form_state->set('dupr_auth_data', $auth_data);
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Performs the POST request to DUPR API.
   */
  protected function loginDuprAccount(string $email, string $password): ?array {
    try {
      $response = $this->httpClient->request('POST', 'https://api.dupr.gg/auth/v1.0/login', [
        'json' => [
          'email' => $email,
          'password' => $password,
        ],
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ],
      ]);

      if ($response->getStatusCode() === 200) {
        $body = json_decode($response->getBody()->getContents(), TRUE);
        if ($body['status'] === 'SUCCESS') {
          return $body['result'];
        }
      }
    } catch (GuzzleException $e) {
      \Drupal::logger('renify_dupr')->error($e->getMessage());
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $auth_data = $form_state->get('dupr_auth_data');

    $this->config('renify_dupr.settings')
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->set('name', $auth_data['user']['fullName'])
      ->set('dupr_id', $auth_data['user']['id'])
      ->set('secretkey', $auth_data['accessToken'])
      ->save();

    parent::submitForm($form, $form_state);
    $this->messenger()->addStatus($this->t('Successfully authenticated and updated DUPR settings.'));
  }

}
