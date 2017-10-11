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
          'editionIdentifier' => NULL,
          'path' => NULL,
          'fragment' => NULL,
        ],
      ],

      [
        '/dev',
        [
          'bookSlug' => 'dev',
          'languageCode' => NULL,
          'versionDescriptor' => NULL,
          'editionIdentifier' => NULL,
          'path' => NULL,
          'fragment' => NULL,
        ],
      ],

      [
        " /dev \n",
        [
          'bookSlug' => 'dev',
          'languageCode' => NULL,
          'versionDescriptor' => NULL,
          'editionIdentifier' => NULL,
          'path' => NULL,
          'fragment' => NULL,
        ],
      ],

      [
        "/foo bar/baz bat",
        [
          'bookSlug' => 'foo bar',
          'languageCode' => 'baz bat',
          'versionDescriptor' => NULL,
          'editionIdentifier' => NULL,
          'path' => NULL,
          'fragment' => NULL,
        ],
      ],

      [
        '/dev/en',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => NULL,
          'editionIdentifier' => NULL,
          'path' => NULL,
          'fragment' => NULL,
        ],
      ],

      [
        '/dev/en/latest',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
          'editionIdentifier' => 'dev/en/latest',
          'path' => NULL,
          'fragment' => NULL,
        ],
      ],

      [
        'dev/en/latest',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
          'editionIdentifier' => 'dev/en/latest',
          'path' => NULL,
          'fragment' => NULL,
        ],
      ],

      [
        'dev/en/latest/',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
          'editionIdentifier' => 'dev/en/latest',
          'path' => NULL,
          'fragment' => NULL,
        ],
      ],

      [
        '//dev///////en//latest///',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
          'editionIdentifier' => 'dev/en/latest',
          'path' => NULL,
          'fragment' => NULL,
        ],
      ],

      [
        'dev/en/latest/category/foo/my-page/',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
          'editionIdentifier' => 'dev/en/latest',
          'path' => 'category/foo/my-page',
          'fragment' => NULL,
        ],
      ],

      [
        'dev/en/latest/category/foo/my-page#',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
          'editionIdentifier' => 'dev/en/latest',
          'path' => 'category/foo/my-page',
          'fragment' => NULL,
        ],
      ],

      [
        'dev/en/latest/category/foo/my-page/#some-section',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
          'editionIdentifier' => 'dev/en/latest',
          'path' => 'category/foo/my-page',
          'fragment' => 'some-section',
        ],
      ],

      [
        'dev/en/latest/category/foo/my-page/#some-section#another-section',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
          'editionIdentifier' => 'dev/en/latest',
          'path' => 'category/foo/my-page',
          'fragment' => 'some-section#another-section',
        ],
      ],

      [
        'dev/en/latest/category/foo/my-page#some-section',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
          'editionIdentifier' => 'dev/en/latest',
          'path' => 'category/foo/my-page',
          'fragment' => 'some-section',
        ],
      ],

      [
        'dev/en/latest/category/foo/my-page.md#some-section',
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
          'editionIdentifier' => 'dev/en/latest',
          'path' => 'category/foo/my-page.md',
          'fragment' => 'some-section',
        ],
      ],

      [
        'dev/#some-section',
        [
          'bookSlug' => 'dev',
          'languageCode' => NULL,
          'versionDescriptor' => NULL,
          'editionIdentifier' => NULL,
          'path' => NULL,
          'fragment' => 'some-section',
        ],
      ],


    ];
  }

  /**
   * @param array $parts
   * @param string $expected
   *
   * @dataProvider identifierPartsProvider
   */
  public function testAssembleIdentifier($parts, $expected) {
    $this->assertEquals($expected, Library::assembleIdentifier($parts));
  }

  public function identifierPartsProvider() {
    return [
      [
        [
          'bookSlug' => NULL,
          'languageCode' => NULL,
          'versionDescriptor' => NULL,
          'editionIdentifier' => NULL,
          'path' => NULL,
          'fragment' => NULL,
        ],
        '',
      ],

      [
        [
          'bookSlug' => 'dev',
          'languageCode' => NULL,
          'versionDescriptor' => NULL,
          'editionIdentifier' => NULL,
          'path' => NULL,
          'fragment' => NULL,
        ],
        'dev',
      ],

      [
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => NULL,
          'editionIdentifier' => NULL,
          'path' => NULL,
          'fragment' => NULL,
        ],
        'dev/en',
      ],

      [
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
          'editionIdentifier' => 'dev/en/latest',
          'path' => NULL,
          'fragment' => NULL,
        ],
        'dev/en/latest',
      ],

      [
        [
          'bookSlug' => 'foo',
          'languageCode' => 'bar',
          'versionDescriptor' => 'baz',
          'editionIdentifier' => 'dev/en/latest',
          'path' => NULL,
          'fragment' => NULL,
        ],
        'dev/en/latest',
      ],

      [
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
          'editionIdentifier' => 'dev/en/latest',
          'path' => 'category/foo/my-page',
          'fragment' => NULL,
        ],
        'dev/en/latest/category/foo/my-page',
      ],

      [
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
          'editionIdentifier' => 'dev/en/latest',
          'path' => 'category/foo/my-page',
          'fragment' => 'some-section',
        ],
        'dev/en/latest/category/foo/my-page/#some-section',
      ],

      [
        [
          'bookSlug' => 'dev',
          'languageCode' => 'en',
          'versionDescriptor' => 'latest',
          'editionIdentifier' => 'dev/en/latest',
          'path' => 'category/foo/my-page',
          'fragment' => 'some-section#another-section',
        ],
        'dev/en/latest/category/foo/my-page/#some-section#another-section',
      ],

      [
        [
          'bookSlug' => 'dev',
          'languageCode' => NULL,
          'versionDescriptor' => NULL,
          'editionIdentifier' => NULL,
          'path' => NULL,
          'fragment' => 'some-section',
        ],
        'dev',
      ],

    ];
  }


}
