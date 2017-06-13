<?php

namespace AppBundle\Model;

class LibraryTest extends \PHPUnit_Framework_TestCase {

  /**
   * @param string $identifier
   * @param array $expected
   * @dataProvider identifierProvider
   */
  public function testParseIdentifier($identifier, $expected) {
    $this->assertEquals($expected, Library::parseIdentifier($identifier));
  }

  public function identifierProvider() {
    return [

      [
        '',
        [
          'bookSlug' => NULL,
          'languageCode' => NULL,
          'versionDescriptor' => NULL,
        ],
      ],

      [
        '/dev',
        [
          'bookSlug' => 'dev',
          'languageCode' => NULL,
          'versionDescriptor' => NULL,
        ],
      ],

      [
        '/dev/en',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => NULL,
        ],
      ],

      [
        '/dev/en/latest',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
        ],
      ],

      [
        'dev/en/latest',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
        ],
      ],

      [
        'dev/en/latest/',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
        ],
      ],

      [
        '//dev///////en//latest///',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
        ],
      ],

    ];
  }

}
