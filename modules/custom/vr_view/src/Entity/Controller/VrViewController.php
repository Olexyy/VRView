<?php

namespace Drupal\vr_view\Entity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class VrViewController extends ControllerBase {

  protected $vrViewStorage;

  /**
   * ModalFormHotSpotController constructor.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->vrViewStorage = $entity_type_manager->getStorage('vr_view');
  }

  /**
   * {@inheritdoc}
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * The Drupal service container.
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Callback handler.
   * @param $vr_view_id
   * @param $yaw
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function defaultYaw($vr_view_id, $yaw) {
    //\Drupal::entityTypeManager()->getStorage('vr_view')
    if(is_numeric($yaw)) {
      if($vr_view_id &&($vr_view = $this->vrViewStorage->load($vr_view_id))) {
        $vr_view->default_yaw = (float)$yaw;
        $vr_view->save();
        drupal_set_message($this->t('The VR View %vr_view has been is set to default %yaw yaw.', array(
          '%vr_view' => $vr_view->toLink()->toString(),
          '%yaw' => $yaw
        )));
        return $this->redirect('entity.vr_view.canonical', ['vr_view' => $vr_view->id()]);
      }
    }
    drupal_set_message($this->t('Something went wrong.'));
    return new RedirectResponse(\Drupal::request()->server->get('HTTP_REFERER'));
  }

}