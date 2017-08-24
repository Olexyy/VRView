<?php
/**
 * Created by PhpStorm.
 * User: Админ
 * Date: 22.08.2017
 * Time: 21:47
 */

namespace Drupal\vr_view\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class VrViewTieBackForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vr_view_tie_back_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $vr_view_id_parent = NULL, $vr_view_id_child = NULL) {
    $this->initParams($form_state, $vr_view_id_parent, $vr_view_id_child);
    if($this->paramsExist($form_state)) {
      $display = [
        'type' => 'vr_view_image',
        'settings' => [
          'type' => 'admin'
        ]
      ];
      $form['parent_vr_view'] = $this->getParentVrView($form_state)->image->view($display);
    }
    else {
      $form['parent_vr_view'] = [
        '#type' => 'item',
        '#title' => $this->t('VR View does not exist'),
      ];
    }
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Tie back'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * Helper to initialize params if they are set.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  private function initParams(FormStateInterface $form_state, $vr_view_id_parent, $vr_view_id_child) {
    if($vr_view_id_parent) {
      if ($vr_view_id_child) {
        if ($parent_vr_view = \Drupal::entityTypeManager()
          ->getStorage('vr_view')
          ->load($vr_view_id_parent)
        ) {
          if ($child_vr_view = \Drupal::entityTypeManager()
            ->getStorage('vr_view')
            ->load($vr_view_id_child)
          ) {
            $form_state->set('parent_vr_view', $parent_vr_view);
            $form_state->set('child_vr_view', $child_vr_view);
            $form_state->set('params_exist', TRUE);
            return;
          }
        }
      }
    }
    $form_state->set('params_exist', FALSE);
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
   * Helper to instantiate passed param.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\vr_view\Entity\VrView
   */
  private function getParentVrView(FormStateInterface $form_state) {
    return $form_state->get('parent_vr_view');
  }

  /**
   * Helper to instantiate passed param.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\vr_view\Entity\VrView
   */
  private function getChildVrView(FormStateInterface $form_state) {
    return $form_state->get('child_vr_view');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) { }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if($this->paramsExist($form_state)) {
      $parent_vr_view = $this->getParentVrView($form_state);
      $child_vr_view = $this->getChildVrView($form_state);
      $yaw = $form_state->getValue('yaw-value-submit');
      $pitch = $form_state->getValue('pitch-value-submit');
      $hotspot = \Drupal::entityTypeManager()->getStorage('vr_hotspot')->create();
      $hotspot->vr_view_target = $child_vr_view;
      $hotspot->pitch = $pitch;
      $hotspot->yaw = $yaw;
      $hotspot->distance = 1;
      $hotspot->radius = 0.05;
      $hotspot->name = $parent_vr_view->name->value .'-'.$child_vr_view->name->value;
      $hotspot->save();
      $parent_vr_view->hotspots[] = $hotspot;
      $parent_vr_view->save();
      drupal_set_message($this->t('The VR View %child has been added to VR View %parent.', array(
        '%child' => $child_vr_view->toLink()->toString(),
        '%parent' => $parent_vr_view->toLink()->toString()
      )));
      $form_state->setRedirectUrl($child_vr_view->toUrl());
    }
  }
}