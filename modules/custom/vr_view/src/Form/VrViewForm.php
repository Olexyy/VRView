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
   * @var string const $operationInteractive
   */
  const operationInteractive = 'interactive';

  /**
   * @var string const $operationTieBack
   */
  const operationTieBack = 'tie_back';

  /**
   * @var string const $operationAddToExisting
   */
  const operationAddToExisting = 'add_to_existing';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $vr_view_id = NULL, $yaw = NULL, $pitch = NULL) {
    /* @var $entity \Drupal\vr_view\Entity\VrView */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->getEntity();
    if($this->operation == self::operationInteractive) {
      $this->hideElements($form);
      $form['vr_view_widget']= $entity->image->view(VrView::getDisplayDefinition(VrView::displayTypeAdmin));
      $form['#title'] = t('Interactive edit');
      return $form;
    }
    else if($this->operation == self::operationTieBack) {
      $this->initParams($form_state, $vr_view_id, NULL, NULL);
      if($this->hasParentVrView($form_state)) {
        $this->hideElements($form);
        $form['vr_view_widget'] = $entity->image->view(VrView::getDisplayDefinition(VrView::displayTypeSelector));
        $form['#title'] = t('Vr view tie back');
        $form['actions']['submit']['#submit'] = [ '::tieBackFormSubmit' ];
        $form['actions']['submit']['#value'] = $this->t('Tie back');
        $form['actions']['submit']['#button_type'] = 'primary';
        $form['actions']['delete']['#access'] = FALSE;
        return $form;
      }
      return [];
    }
    else if($this->operation == self::operationAddToExisting) {
      $this->initParams($form_state, $vr_view_id, $yaw, $pitch);
      $form['summary'] = array (
        '#type' => 'item',
        '#weight' => -100,
        '#description' => $this->t("New view will be added to {$this->getParentVrView($form_state)->name->value}, 
        at position: yaw - {$this->getYaw($form_state)}, pitch - {$this->getPitch($form_state)}."),
      );
      $form['summary'] = $this->entity->image->view(VrView::getDisplayDefinition(VrView::displayTypeAdmin));
    }
    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    );

    return $form;
  }

  /**
   * @param array $form
   */
  private function hideElements(array &$form) {
    foreach($form as $key => $element) {
      if(is_array($element) && isset ($element['#access'])) {
        $form[$key]['#access'] = FALSE;
      }
    }
  }

  /**
   * @inheritdoc
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|\Drupal\Core\Entity\ContentEntityTypeInterface|void
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name')[0]['value'];
    $exists = \Drupal::entityQuery('vr_view')
      ->condition('name', $name, '=')
      ->count()
      ->execute();
    if($exists) {
      $form_state->setErrorByName('name', $this->t('Value should be unique'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * Alternative submit for tie back form.
   * @param array $forrm
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function tieBackFormSubmit(array $forrm, FormStateInterface $form_state) {
    $parent_vr_view = $this->getParentVrView($form_state);
    $child_vr_view = $this->getEntity();
    $yaw = $form_state->getValue('yaw-value-submit');
    $pitch = $form_state->getValue('pitch-value-submit');
    $hotspot = \Drupal::entityTypeManager()->getStorage('vr_hotspot')->create();
    $hotspot->vr_view_target = $parent_vr_view;
    $hotspot->pitch = (float)$pitch;
    $hotspot->yaw = (float)$yaw;
    $hotspot->distance = 1;
    $hotspot->radius = 0.05;
    $hotspot->name = $child_vr_view->name->value .'-'.$parent_vr_view->name->value;
    $hotspot->save();
    $child_vr_view->hotspots[] = $hotspot;
    $child_vr_view->save();
    drupal_set_message($this->t('The VR View %child has been added to VR View %parent.', [
      '%child' => $child_vr_view->toLink()->toString(),
      '%parent' => $parent_vr_view->toLink()->toString()
    ]));
    $form_state->setRedirectUrl($parent_vr_view->toUrl('interactive'));
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
      if($this->operation == self::operationAddToExisting) {
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
          'vr_view_id' => $parent_vr_view->id(), 'vr_view' => $entity->id()
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
  private function hasParentVrView(FormStateInterface $form_state) {
    return $form_state->has('parent_vr_view');
  }

  /**
   * Predicate to define whether form has property.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return bool
   */
  private function hasYaw(FormStateInterface $form_state) {
    return $form_state->has('yaw');
  }

  /**
   * Predicate to define whether form has property.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return bool
   */
  private function hasPitch(FormStateInterface $form_state) {
    return $form_state->has('pitch');
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
   * @param string $vr_view_id
   * @param string $yaw
   * @param string $pitch
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  private function initParams(FormStateInterface $form_state, $vr_view_id, $yaw, $pitch) {
    //\Drupal::request()->get('vr_view_id')
    if(isset($vr_view_id)) {
      if($parent_vr_view = \Drupal::entityTypeManager()->getStorage('vr_view')->load($vr_view_id)) {
        $form_state->set('parent_vr_view', $parent_vr_view);
        if(isset($yaw)) {
          $yaw = ($yaw)? $this->commasToDots($yaw) : '0';
          $form_state->set('yaw', $yaw);
        }
        if(isset($pitch)) {
          $pitch = ($pitch)? $this->commasToDots($pitch) : '0';
          $form_state->set('pitch', $pitch);
        }
      }
    }
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
