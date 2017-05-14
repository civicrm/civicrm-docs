<?php

namespace AppBundle\Tests\Utils;

use AppBundle\Utils\StringTools;

class StringToolsTest extends \PHPUnit_Framework_TestCase
{
  /**
   * @param string $string
   *  The string to make safe for usage in URLs
   * @param string $expected
   *  The expected result
   * @dataProvider urlProvider
   */
  public function testUrlSafe($string, $expected) {
    $this->assertEquals($expected, StringTools::urlSafe($string));
  }

  /**
   * @return array
   */
  public function urlProvider() {
    return [
      [
        'PAGE-1',
        'page-1',
      ],
      [
        'this is a path',
        'this-is-a-path',
      ],
      [
        '***path 1',
        'path-1',
      ],
      [
        'another_path',
        'another-path',
      ],
    ];
  }
}
