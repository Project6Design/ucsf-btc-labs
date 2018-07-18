<?php

namespace Drupal\sharemessage\Entity\Handler;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Share Messages.
 */
class ShareMessageListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    $header['plugin'] = t('Plugin');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\sharemessage\ShareMessageInterface $entity */
    $row['label'] = $entity->label();
    $row['plugin'] = $entity->getPluginId();
    return $row + parent::buildRow($entity);
  }

}
