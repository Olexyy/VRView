<?php
/**
 * Created by PhpStorm.
 * User: Админ
 * Date: 18.09.2017
 * Time: 20:25
 */

namespace Drupal\vr_view\Entity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class VrViewController extends ControllerBase {

  /**
   * ModalFormHotSpotController constructor.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFormBuilder $entity_form_builder
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * The Drupal service container.
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  public function defaultYaw($vr_view_id, $yaw) {
    if(is_numeric($yaw)) {
      if($vr_view_id &&($vr_view = \Drupal::entityTypeManager()
          ->getStorage('vr_view')
          ->load($vr_view_id))) {
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