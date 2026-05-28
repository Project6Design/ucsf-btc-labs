<?php

namespace Drupal\Tests\block_exclude_pages\Kernel;

use Psr\Log\LoggerInterface;
use Drupal\KernelTests\Core\Plugin\Condition\RequestPathTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests exclude pages condition.
 *
 * @coversDefaultClass \Drupal\block_exclude_pages\Plugin\Condition\BlockExcludePagesRequestPath
 * @group Plugin
 */
class BlockExcludePagesRequestPathTest extends RequestPathTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block_exclude_pages'];

  /**
   * Set up the test environment.
   */
  protected function setUp(): void {
    parent::setUp();

    // Add a mock session to the container.
    $mockSession = new Session(new MockArraySessionStorage());
    $this->container->set('session', $mockSession);

    // Reinitialize the request stack with the session-aware request stack.
    $this->container->set('request_stack', $this->requestStack);

    // Add a mock logger to the container for the service.
    $mockLogger = $this->createMock(LoggerInterface::class);
    $this->container->set('logger.channel.block_exclude_pages', $mockLogger);
  }

  /**
   * Tests the request path condition.
   *
   * @param array $config_paths
   *   An array of config paths.
   * @param array $paths
   *   An array of paths.
   *
   * @dataProvider providerTestGetFieldAccess
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testConditionsExclude(array $config_paths, array $paths): void {
    /** @var \Drupal\block_exclude_pages\Plugin\Condition\BlockExcludePagesRequestPath $condition */
    $condition = $this->pluginManager->createInstance('request_path');
    $condition->setConfig('pages', implode(PHP_EOL, $config_paths));

    foreach ($paths as $path => $expected) {
      $request = Request::create($path);
      $this->requestStack->push($request);

      $this->aliasManager->addAlias($path, $path);
      $this->assertEquals($expected, $condition->evaluate(), "The path $path handled correctly.");

      $this->requestStack->pop();
    }
  }

  /**
   * Data provider for ::testGetFieldAccess.
   */
  public static function providerTestGetFieldAccess() {
    return [
      [
        ['/*', '!/excluded'],
        [
          '/something' => TRUE,
          '/excluded' => FALSE,
        ],
      ],

      // First disallow, then allow (works as expected).
      [
        ['!/order/*/*', '/order/*/complete'],
        [
          '/order/1/foo' => FALSE,
          '/order/1/complete' => TRUE,
        ],
      ],
      // Reverse order (does not work as expected).
      [
        ['/order/*/complete', '!/order/*/*'],
        [
          '/order/1/foo' => FALSE,
          '/order/1/complete' => FALSE,
        ],
      ],

      [
        ['/1/2/*/foo'],
        [
          '/1/2/3/foo' => TRUE,
          '/1/2/3/4/foo' => TRUE,
          '/2/1/foo' => FALSE,
          '/2/3/4/5/foo' => FALSE,
        ],
      ],
    ];
  }

}
