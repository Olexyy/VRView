<?php

namespace Drupal\vr_view\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

/**
 * Form controller for the vr_hotspot entity edit forms.
 *
 * @ingroup vr_view
 */
class VrHotspotForm extends ContentEntityForm {

  /**
   * @var string const $operationAddToExisting
   */
  const operationAddExisting = 'add_existing';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $vr_view = NULL, $yaw = NULL, $pitch = NULL) {
    /* @var $entity \Drupal\vr_view\Entity\VRHotpot */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    if($this->operation == self::operationAddExisting) {
      $this->initParams($form_state, $vr_view, $yaw, $pitch);
      if(!$form_state->getValue('taxonomy_options', FALSE)) {
        $terms = Term::loadMultiple();
        $options = [];
        foreach ($terms as $term) {
          $options[$term->id()] = $term->name->value;
        }
        $form_state->setValue('taxonomy_options', $options);
      }
      $taxonomy_options = $form_state->getValue('taxonomy_options', FALSE);
      if(!$form_state->getValue('vr_view_options', FALSE)) {
        $parent_vr_view = $this->getParentVrView($form_state);
        $type = $parent_vr_view->type->entity;
        $type_id = FALSE;
        if ($type) {
          $type_id = $type->id();
        }
        else if ($taxonomy_options) {
          $type_id = current(array_keys($taxonomy_options));
        }
        $query = \Drupal::entityQuery('vr_view');
        if ($type_id) {
          $query->condition('type', $type_id, '=');
        }
        $vr_view_ids = $query->execute();
        $options = [];
        foreach ($vr_view_ids as $vr_view_id) {
          $vr_view_entity = \Drupal::entityTypeManager()
            ->getStorage('vr_view')
            ->load($vr_view_id);
          $options[$vr_view_id] = $vr_view_entity->name->value;
        }
        $form_state->setValue('vr_view_options', $options);
      }

      $form['vr_view_target']['widget'][0]['target_id'] = [
        '#title' => $this->t('Existing vr view'),
        '#description' => $this->t('Select VR view form same taxonomy type'),
        '#type' => 'select',
        '#required' => TRUE,
        '#empty_option' => '-Select-',
        '#options' => $form_state->getValue('vr_view_options'),
        '#prefix' => "<div id='dynamic-form-element'>",
        '#suffix' => "</div>",
      ];
      $form['yaw']['widget'][0]['value']['#default_value'] = $this->getYaw($form_state);
      $form['pitch']['widget'][0]['value']['#default_value'] = $this->getPitch($form_state);
      $form['distance']['widget'][0]['value']['#default_value'] = 1;
      $form['radius']['widget'][0]['value']['#default_value'] = 0.05;
      $form['name']['#access'] = FALSE;

      if($taxonomy_options) {
        $form['taxonomy_selector'] = [
          '#title' => $this->t('Types'),
          '#description' => $this->t('Select from available types'),
          '#type' => 'select',
          '#options' => $taxonomy_options,
          '#empty_option' => '-Select-',
          '#ajax' => [
            'callback' => [ $this, 'vrViewList' ],
            'event' => 'change',
            'progress' => [
              'type' => 'throbber',
              'message' => NULL,
            ],
          ],
        ];
      }
    }

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    );

    return $form;
  }

  public function vrViewList(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if($term_id = $form_state->getValue('taxonomy_selector')) {
      $options = [];
      $vr_view_ids = \Drupal::entityQuery('vr_view')
        ->condition('type', $term_id, '=')
        ->execute();
      foreach ($vr_view_ids as $vr_view_id) {
        $vr_view_entity = \Drupal::entityTypeManager()
          ->getStorage('vr_view')
          ->load($vr_view_id);
        $options[$vr_view_id] = $vr_view_entity->name->value;
      }
      $element = [
        '#type' => 'select',
        '#title' => $this->t('Existing vr view'),
        '#description' => $this->t('Select VR view form same taxonomy type'),
        '#options' => $options,
        '#required' => TRUE,
        '#empty_option' => '-Select-',
        '#prefix' => "<div id='dynamic-form-element'>",
        '#suffix' => "</div>",
      ];
      $response->addCommand(new ReplaceCommand('#dynamic-form-element', render($element)));
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if($this->operation == self::operationAddExisting) {
      $this->entity->name = $this->getParentVrView($form_state)->name->value . '-' . $this->entity->vr_view_target->entity->name->value;
    }
    $status = parent::save($form, $form_state);

    $entity = $this->getEntity();
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The VR Hotspot %feed has been updated.', ['%feed' => $entity->toLink()->toString()]));
    }
    else {
      if($this->operation == self::operationAddExisting) {
        $parent_vr_view = $this->getParentVrView($form_state);
        $parent_vr_view->hotspots[] = $entity;
        $parent_vr_view->save();

        drupal_set_message($this->t('Now, select position back reference to initial VR view %feed.', [
          '%feed' => $this->getParentVrView($form_state)->toLink()->toString(),
        ]));
        $form_state->setRedirectUrl(Url::fromRoute('entity.vr_view.tie_back', [
          'vr_view' => $entity->vr_view_target->entity->id(),
          'vr_view_id' => $this->getParentVrView($form_state)->id(),
        ]));
      }
      else {
        drupal_set_message($this->t('The VR Hotspot %feed has been added.', [
          '%feed' => $entity->toLink()
            ->toString()
        ]));
        $form_state->setRedirectUrl($entity->toUrl('collection'));
      }
    }

    return $status;
  }

  /**
   * Helper to initialize params if they are set.
   * @param string $vr_view
   * @param string $yaw
   * @param string $pitch
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  private function initParams(FormStateInterface $form_state, $vr_view, $yaw, $pitch) {
    if(!$this->hasParentVrView($form_state)) {
      if (isset($vr_view)) {
        if ($parent_vr_view = \Drupal::entityTypeManager()
          ->getStorage('vr_view')
          ->load($vr_view)) {
          $form_state->set('parent_vr_view', $parent_vr_view);
          if (isset($yaw)) {
            $yaw = ($yaw) ? $this->commasToDots($yaw) : '0';
            $form_state->set('yaw', $yaw);
          }
          if (isset($pitch)) {
            $pitch = ($pitch) ? $this->commasToDots($pitch) : '0';
            $form_state->set('pitch', $pitch);
          }
        }
      }
    }
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
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\vr_view\Entity\VrView
   */
  private function getParentVrView(FormStateInterface $form_state) {
    return $form_state->get('parent_vr_view');
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
   * Helper to convert commas in string to dots.
   * @param string $number
   * @return string | bool
   */
  private function commasToDots($number) {
    return str_replace(',', '.', $number);
  }

}
