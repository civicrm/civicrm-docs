<?php

namespace AppBundle\Tests\Utils;

use AppBundle\Utils\LocaleTools;

class LocaleToolsTest extends \PHPUnit_Framework_TestCase {

  /**
   * @param string $languageCode
   *
   * @param string $localeCode
   *
   * @param string $expected
   *
   * @dataProvider languageNameInLocaleProvider
   */
  public function testGetLanguageNameInLocale($languageCode, $localeCode, $expected) {
    $this->assertEquals($expected, LocaleTools::getLanguageNameInLocale($languageCode, $localeCode));
  }

  /**
   * @return array
   */
  public function languageNameInLocaleProvider() {
    return [
      [
        'es',
        'en',
        'Spanish',
      ],
      [
        'es',
        'es',
        'español',
      ],
    ];
  }

  /**
   * @param string $languageCode
   *
   * @dataProvider codeProvider
   */
  public function testCodeIsValid($languageCode, $expected) {
    $this->assertEquals($expected, LocaleTools::codeIsValid($languageCode));
  }

  public function codeProvider() {
    $validCodes = ['en', 'es', 'fr'];
    $invalidCodes = ['', NULL, 0, 'English', 'english', 'qq', '00', 'en-US'];

    $validCodes = array_map(function ($i) {
      return [$i, TRUE];
    }, $validCodes);
    $invalidCodes = array_map(function ($i) {
      return [$i, FALSE];
    }, $invalidCodes);

    return array_merge($validCodes, $invalidCodes);
  }

}
