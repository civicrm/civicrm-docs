<?php

namespace AppBundle\Utils;

define('locales_dir', __DIR__ . '/../../../vendor/symfony/symfony/src/Symfony/Component/Intl/Resources/data/locales');

/**
 * A set of helper tools for dealing with different locales
 *
 */
class LocaleTools {
  
  const LOCALES_DIR = locales_dir;

  /**
   * Returns the name of a language, in another language. For example, if 
   * $languageCode = 'en' and $localeCode = 'es' this function would answer the
   * question "what word do Spanish-speaking people use to refer to English?"
   * 
   * @param type $languageCode The language we're asking about
   * @param type $localeCode   The language in which we want our answer
   * 
   * @return string 
   */
  public static function getLaguageNameInLocale($languageCode, $localeCode) {
    $localesFiles = self::LOCALES_DIR . "/$localeCode.json"; 
    $locales = json_decode(file_get_contents($localesFiles), TRUE);
    return $locales['Names'][$languageCode];
  }

}
