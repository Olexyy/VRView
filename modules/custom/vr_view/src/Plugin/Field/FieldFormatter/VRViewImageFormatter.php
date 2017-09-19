<?php

namespace Drupal\vr_view\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\vr_view\Entity;

/**
 * Plugin implementation of the 'VRView' formatter.
 *
 * @FieldFormatter(
 *   id = "vr_view_image",
 *   label = @Translation("VR view image admin"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class VRViewImageFormatter extends FormatterBase {

  const TypeAdmin = 'admin';
  const TypeSelector = 'selector';
  const TypeUser = 'user';

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();
    $summary[] = $this->t('Displays the VR view image admin');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'type' => self::TypeUser,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['type'] = [
      '#title' => t('Formatter type'),
      '#type' => 'select',
      '#options' => [
        self::TypeAdmin => $this->t('Admin view'),
        self::TypeSelector => $this->t('Select params'),
        self::TypeUser => $this->t('User view'),
      ],
      '#default_value' => $this->getSetting('type'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $type = $this->getSetting('type');
    $element = [];
    foreach ($items as $delta => $item) {
      if($type == self::TypeAdmin) {
        $element[$delta] = $this->viewBuilderAdmin($item);
      }
      else if ($type == self::TypeSelector) {
        $element[$delta] = $this->viewBuilderSelector($item);
      }
      else {
        $element[$delta] = $this->viewBuilderUser($item);
      }
    }
    return $element;
  }

  /**
   * Helper to get definition of render array for vr_view entity (admin).
   * @param \stdClass $item
   * @return array $widget
   */
  private function viewBuilderAdmin($item) {
    $entity = $item->getEntity();
    $js_settings = $this->jsSettings($entity, self::TypeAdmin);
    $widget = array();
    $widget['vr_view_widget'] = array(
      'vr_view_title' => array(
        '#markup' => '<h2 class="vrview-title" id="vrview-title"></h2>',
      ),
      'vr_view_image' => array(
        '#markup' => '<div id="vrview"></div>',
      ),
      'vr_view_description' => array(
        '#markup' => '<p class="vrview-description" id="vrview-description"></p>',
      ),
      'vr_view_image_position' => array(
        '#markup' => '<div class="vrview-position position">
                        <div class="position-title">Yaw: <span class="position-yaw value" id="yaw-value">0</span></div>                        
                        <div class="position-title">Pitch: <span class="position-pitch value" id="pitch-value">0</span></div>
                    </div>',
      ),
      'vr_view_default_yaw' => array(
        '#markup' => '<div class="vrview-default-yaw default-yaw">
                        <div class="default-yaw-title">Default yaw: <span class="default-yaw-value" id="default-yaw-value">0</span></div>
                    </div>',
      ),
      'vr_view_admin_actions' => array (
        '#markup' => Link::fromTextAndUrl(t('Add new Vr view using current pitch and yaw'), Url::fromUri("internal:/vr_view/add/{$entity->id->value}/0/0", [ 'attributes' => ['id' => 'dynamic-button-add-new', 'class' => ['button-action', 'button', 'dynamic-args'] ]]))->toString()
          .Link::fromTextAndUrl(t('Add existing Vr view using current pitch and yaw'), Url::fromUri("internal:/vr_hotspot/add/{$entity->id->value}/0/0", [ 'attributes' => ['id' => 'dynamic-button-add-existing', 'class' => ['button-action', 'button', 'dynamic-args'] ]]))->toString()
          .$this->hotspotsLinks($entity)
          .'<br />'.t('Add new or edit existing hotspots, using current pitch and yaw.').'<br />'
          .Link::fromTextAndUrl(t('Make current yaw to be default'), Url::fromUri("internal:/vr_view/default_yaw/{$entity->id->value}/0", [ 'attributes' => ['id' => 'dynamic-button-default-yaw', 'class' => ['button-action', 'button', 'dynamic-args'] ]]))->toString(),
      ),
      'yaw-value-submit' => array(
        '#type' => 'hidden',
        '#default_value' => 0,
        '#name' => 'yaw-value-submit',
      ),
      'pitch-value-submit' => array(
        '#type' => 'hidden',
        '#default_value' => 0,
        '#name' => 'pitch-value-submit',
      ),
      '#attached' => array(
        'library' => array( 'vr_view/vr_library', 'core/drupal.dialog.ajax' ),
        'drupalSettings' => array( 'vr_view' => $js_settings ),
      ),
      '#allowed_tags' => array('div', 'span', 'input', 'a'),
    );
    return $widget;
  }

  private function viewBuilderSelector($item) {
    // TODO
    return [];
  }

  private function viewBuilderUser($item) {
    // TODO
    return [];
  }

  /**
   * Helper to build js settings.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $type
   *
   * @return array
   */
  private function jsSettings(EntityInterface $entity, $type) {
    $start_image_uri = file_create_url('public://pics/blank.png');
    $vr_view_name = $entity->name->value.'_'.$entity->id();
    $js_settings = [
      'mode' => $type,
      'start_image' => $start_image_uri,
      'start_view' => $vr_view_name,
      'views' => [],
      'link_add_new' => Url::fromUri("internal:/vr_view/add")->toString(),
      'link_add_existing' => Url::fromUri("internal:/vr_hotspot/add")->toString(),
      'link_default_yaw' => Url::fromUri("internal:/vr_view/default_yaw")->toString(),
    ];
    $this->vrViewToJsSettings($entity, $js_settings, $type);
    return $js_settings;
  }

  private function vrViewToJsSettings(EntityInterface $entity, array &$js_settings, $type) {
    $vr_view_name = $entity->name->value.'_'.$entity->id->value;
    $is_stereo = $entity->is_stereo->value;
    $file = $entity->image->entity;
    $image_uri = file_create_url($file->getFileUri());
    $js_settings['views'][$vr_view_name] = [
      'source' => $image_uri,
      'is_stereo' => $is_stereo,
      'hotspots' => $this->hotspotsToJsSettings($entity),
      'default_yaw' => $this->commaToDot($entity->default_yaw->value),
      'is_yaw_only' => $entity->is_yaw_only->value,
      'id' => $entity->id(),
      'name' => $entity->name->value,
      'description' => $entity->description->value,
    ];
    $hotspots = $entity->hotspots->referencedEntities();
    foreach ($hotspots as $hotspot) {
      if($vr_view = $hotspot->vr_view_target->entity) {
        $vr_view_nnn = $vr_view->name->value.'_'.$vr_view->id->value;
        if(!isset($js_settings['views'][$vr_view_nnn])) {
          $this->vrViewToJsSettings($vr_view, $js_settings, $type);
        }
      }
    }
  }

  private function hotspotsToJsSettings(EntityInterface $entity) {
    $hotspot_settings = [];
    $hotspots = $entity->hotspots->referencedEntities();
    foreach ($hotspots as $hotspot) {
      if($vr_view = $hotspot->vr_view_target->entity) {
        $vr_view_name = $vr_view->name->value.'_'.$vr_view->id->value;
        $hotspot_settings[$vr_view_name] = [
          'pitch' => $this->commaToDot($hotspot->pitch->value),
          'yaw' => $this->commaToDot($hotspot->yaw->value),
          'radius' => $this->commaToDot($hotspot->radius->value),
          'distance' => $this->commaToDot($hotspot->distance->value),
        ];
      }
    }
    return $hotspot_settings;
  }

  private function hotspotsLinks(EntityInterface $entity) {
    $html = '';
    $hotspots = $entity->hotspots->referencedEntities();
    foreach ($hotspots as $hotspot) {
      if($vr_view = $hotspot->vr_view_target->entity) {
        $hotspot_id = $hotspot->id->value;
        $hotspot_name = $hotspot->name->value;
        $vr_view_name = $vr_view->name->value;
        $name = $hotspot_name .'('. $vr_view_name .')';
        $html .= Link::fromTextAndUrl($name, Url::fromUri("internal:/vr_hotspot/{$hotspot_id}", [ 'attributes' => ['id' => 'modal-button-edit', 'class' => ['modal-button-edit', 'button',] ]]))->toString();
      }
    }
    return $html;
  }

  /**
   * Helper to normalize float value.
   * @param string $string
   *
   * @return string
   */
  private function commaToDot($string) {
    return str_replace(',', '.', (string)$string);
  }
}