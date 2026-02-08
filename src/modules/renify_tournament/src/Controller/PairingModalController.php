<?php

namespace Drupal\renify_tournament\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class PairingModalController extends ControllerBase {

  protected $entityFormBuilder;
  protected $entityTypeManager;

  public function __construct(EntityFormBuilderInterface $form_builder, EntityTypeManagerInterface $entity_manager) {
    $this->entityFormBuilder = $form_builder;
    $this->entityTypeManager = $entity_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.form_builder'),
      $container->get('entity_type.manager')
    );
  }

  public function openFormScoring(Request $request) {
     return $this->getAjaxResponse($request, 'score');
  }

  public function openFormStanding(Request $request) {
    return $this->getAjaxResponse($request, 'offcanvas');
  }

  protected function getAjaxResponse(Request $request, $view_mode_form) {
    $nid = $request->query->get('id');
    $response = new AjaxResponse();

    if ($this->currentUser()->isAnonymous()) {
      return $response;
    }

    if ($nid && $node = $this->entityTypeManager->getStorage('node')->load($nid)) {
      // Get the form using the 'offcanvas' mode we registered earlier
      $form = $this->entityFormBuilder->getForm($node, $view_mode_form);

      // Hide Buttons.
      $form['actions']['preview']['#attributes']['class'][] = 'visually-hidden';
      $form['actions']['delete']['#attributes']['class'][] = 'visually-hidden';

      unset($form['advanced']);
      // Define modal options
      $options = [
        'title' => $node->label(),
        'width' => '80%',
        'dialogClass' => 'tournament-score-modal',
      ];

      $response->addCommand(new OpenModalDialogCommand($node->label(), $form, $options));;
    }

    return $response;
  }
}
