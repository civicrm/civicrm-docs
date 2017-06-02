<?php

namespace AppBundle\Utils;

class StringTools {

  /**
   * Cleans up strings to be used for within URLs. For example, it makes them
   * lowercase and replaces spaces with dashes.
   *
   * @param string $s the string to clean up
   *
   * @return string The cleaned string, safe for use in URLs
   */
  public static function urlSafe($s) {
    $clean = iconv('UTF-8', 'ASCII', strtolower(trim($s)));
    $clean = preg_replace('#[^.a-zA-Z0-9/_|+ -]#', '', $clean);
    $clean = preg_replace('#[/_|+ -]+#', '-', $clean);

    return $clean;
  }

}
