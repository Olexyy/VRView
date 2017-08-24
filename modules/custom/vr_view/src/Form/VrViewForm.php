<?php

namespace Drupal\vr_view\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\vr_view\Entity\VrView;

/**
 * Form controller for the vr_view entity edit forms.
 *
 * @ingroup vr_view
 */
class VrViewForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\vr_view\Entity\VrView */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    $this->initParams($form_state);

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    );

    if($this->paramsExist($form_state)) {
      $form['summary'] = array (
        '#type' => 'item',
        '#weight' => -100,
        '#description' => $this->t("New view will be added to {$this->getParentVrView($form_state)->name->value}, 
        at position: yaw - {$this->getYaw($form_state)}, pitch - {$this->getPitch($form_state)}."),
      );
      $display = array(
        'label' => 'above',
        'type' => 'vr_view_image',
        'settings' => array(
          'type' => 'admin',
        ),
      );
      $form['summary'] = $this->entity->image->view($display);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    $entity = $this->getEntity();
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The VR View %feed has been updated.', [
        '%feed' => $entity->toLink()
          ->toString()
        ]));
      $form_state->setRedirectUrl($entity->toUrl('collection'));
    }
    else {
      if($this->paramsExist($form_state)) {
        $parent_vr_view = $this->getParentVrView($form_state);
        $hotspot = \Drupal::entityTypeManager()->getStorage('vr_hotspot')->create();
        $hotspot->vr_view_target = $entity;
        $hotspot->pitch = $this->getPitch($form_state);
        $hotspot->yaw = $this->getYaw($form_state);
        $hotspot->distance = 1;
        $hotspot->radius = 0.05;
        $hotspot->name = $parent_vr_view->name->value .'-'.$entity->name->value;
        $hotspot->save();
        $parent_vr_view->hotspots[] = $hotspot;
        $parent_vr_view->save();
        drupal_set_message($this->t('The VR View %feed has been added to current VR View.', [
          '%feed' => $parent_vr_view->toLink()->toString()
        ]));
        $form_state->setRedirectUrl(Url::fromRoute('entity.vr_view.tie_back', [
          'vr_view_id_child' => $parent_vr_view->id(), 'vr_view_id_parent' => $entity->id()
        ]));
      }
      else {
        drupal_set_message($this->t('The VR View %feed has been created.', [
          '%feed' => $entity->toLink()->toString()
        ]));
        $form_state->setRedirectUrl($entity->toUrl('collection'));
      }
    }

    return $status;
  }

  /**
   * Predicate to define whether form is built with predefined params.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return bool
   */
  private function paramsExist(FormStateInterface $form_state) {
    return $form_state->get('params_exist');
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return string
   */
  private function getPitch(FormStateInterface $form_state) {
    return $form_state->get('pitch');
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return string
   */
  private function getYaw(FormStateInterface $form_state) {
    return $form_state->get('yaw');
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\vr_view\Entity\VrView
   */
  private function getParentVrView(FormStateInterface $form_state) {
    return $form_state->get('parent_vr_view');
  }

  /**
   * Helper to initialize params if they are set.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  private function initParams(FormStateInterface $form_state) {
    if($vr_view_id = \Drupal::request()->get('vr_view_id')) {
      if($parent_vr_view = \Drupal::entityTypeManager()->getStorage('vr_view')->load($vr_view_id)) {
        $form_state->set('parent_vr_view', $parent_vr_view);
        $pitch = \Drupal::request()->get('pitch');
        $pitch = ($pitch)? $this->commasToDots($pitch) : '0';
        $form_state->set('pitch', $pitch);
        $yaw = \Drupal::request()->get('yaw');
        $yaw = ($yaw)? $this->commasToDots($yaw) : '0';
        $form_state->set('yaw', $yaw);
        $form_state->set('params_exist', TRUE);
        return;
      }
    }
    $form_state->set('params_exist', FALSE);
  }

  /**
   * Helper to convert commas in string to dots.
   * @param string $number
   * @return string | bool
   */
  private function commasToDots($number) {
    return str_replace(',', '.', $number);
  }
}
