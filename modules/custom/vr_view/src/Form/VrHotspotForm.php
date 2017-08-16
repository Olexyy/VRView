<?php

namespace Drupal\vr_view\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the vr_hotspot entity edit forms.
 *
 * @ingroup vr_view
 */
class VrHotspotForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\vr_view\Entity\VRHotpot */
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
      $display = array(
        'type' => 'vr_view_image',
        'settings' => array (
          'type' => 'admin'
        ));
      $form['parent_vr_view'] = $this->getParentVrView($form_state)->image->view($display);
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
      drupal_set_message($this->t('The VR Hotspot %feed has been updated.', ['%feed' => $entity->toLink()->toString()]));
    } else {
      drupal_set_message($this->t('The VR Hotspot %feed has been added.', ['%feed' => $entity->toLink()->toString()]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
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
   * Helper to instantiate passed param.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
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
    if($vr_view_id_parent = \Drupal::request()->get('vr_view_id_parent')) {
      if ($vr_view_id_child = \Drupal::request()->get('vr_view_id_child')) {
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
}
