<?php

namespace Drupal\vr_view\Entity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class VrHotspotController extends ControllerBase {

  protected $vrHotspotStorage;

  /**
   * ModalFormHotSpotController constructor.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->vrHotspotStorage = $entity_type_manager->getStorage('vr_hotspot');
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
   * @param $vr_hotspot
   * @param $vr_view
   * @param $yaw
   * @param $pitch
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function position($vr_hotspot, $vr_view, $yaw, $pitch) {
    //\Drupal::entityTypeManager()->getStorage('vr_view')
    if($yaw && is_numeric($yaw) && $pitch && is_numeric($pitch)) {
      if($vr_hotspot &&($vr_hotspot = $this->vrHotspotStorage->load($vr_hotspot))) {
        $vr_hotspot->yaw = (float)$yaw;
        $vr_hotspot->pitch = (float)$pitch;
        $vr_hotspot->save();
        drupal_set_message($this->t('The VR View %vr_hotspot has been set %yaw yaw, %pitch pitch.', [
          '%vr_hotspot' => $vr_hotspot->toLink()->toString(),
          '%yaw' => $yaw,
          '%pitch' => $pitch
        ]));
        return $this->redirect('entity.vr_view.canonical', ['vr_view' => $vr_view->id()]);
      }
    }
    drupal_set_message($this->t('Something went wrong.'));
    return new RedirectResponse(\Drupal::request()->server->get('HTTP_REFERER'));
  }


}