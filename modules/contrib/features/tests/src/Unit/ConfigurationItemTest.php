<?php

namespace Drupal\Tests\features\Unit;

use Drupal\features\ConfigurationItem;
use Drupal\features\FeaturesManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\features\ConfigurationItem
 */
#[Group('features')]
class ConfigurationItemTest extends TestCase {

  /**
   * @covers ::fromConfigStringToConfigType
   */
  public function testFromConfigStringToConfigType() {
    $this->assertEquals('system.simple', ConfigurationItem::fromConfigStringToConfigType(FeaturesManagerInterface::SYSTEM_SIMPLE_CONFIG));
    $this->assertEquals('node', ConfigurationItem::fromConfigStringToConfigType('node'));
  }

}
