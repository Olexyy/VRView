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

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    );

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
    }
    else {
      drupal_set_message($this->t('The VR Hotspot %feed has been added.', ['%feed' => $entity->toLink()->toString()]));
      $form_state->setRedirectUrl($entity->toUrl('collection'));
    }

    return $status;
  }

}
