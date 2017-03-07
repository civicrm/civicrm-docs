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
    $localeFile = self::LOCALES_DIR . "/$localeCode.json";
    $locales = json_decode(file_get_contents($localeFile), TRUE);
    return $locales['Names'][$languageCode];
  }

  /**
   * Checks to see whether a given language code is a valid ISO-639-1 code.
   *
   * @param string $languageCode (e.g. "en", or "es")
   * @return boolean TRUE if the code is valid
   */
  public static function codeIsValid($languageCode) {
    return file_exists(self::LOCALES_DIR . "/$languageCode.json");
  }

}