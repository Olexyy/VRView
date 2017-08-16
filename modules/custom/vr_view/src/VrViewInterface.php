<?php

namespace Drupal\vr_view;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an entity.
 * It is good practise to provide an interface to
 * define the public access to an entity. In
 * addition, it invokes the 'EntityOwnerInterface'
 * to get access to additional functionality.
 *
 * @ingroup vr_view
 */
interface VrViewInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}