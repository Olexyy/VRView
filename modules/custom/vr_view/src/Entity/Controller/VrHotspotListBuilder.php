<?php

namespace Drupal\vr_view\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list controller for vr_hotspot entity.
 *
 * @ingroup vr_view
 */
class VrHotspotListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('You can manage fields on the <a href="@adminlink">Vr Hotspot admin page</a>.', array(
        '@adminlink' => \Drupal::urlGenerator()
          ->generateFromRoute('vr_hotspot.vr_hotspot_settings'),
      )),
    ];

    $build += parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the vr_view list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['distance'] = $this->t('Distance');
    $header['yaw'] = $this->t('Yaw');
    $header['pitch'] = $this->t('Pitch');
    $header['radius'] = $this->t('Radius');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\vr_view\Entity\VrView */
    $row['id'] = $entity->id();
    $row['name'] = $entity->name->value;
    $row['distance'] = $entity->distance->value;
    $row['yaw'] = $entity->yaw->value;
    $row['pitch'] = $entity->pitch->value;
    $row['radius'] = $entity->radius->value;

    return $row + parent::buildRow($entity);
  }

}