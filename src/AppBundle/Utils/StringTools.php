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

  /**
   * Look at one redirect rule, as it is written in a text file, and determine
   * the "from" and "to" elements.
   *
   * See StringToolsTest::redirectRuleProvider() for examples
   *
   * @param string $rule
   *
   * @return array
   */
  public static function parseRedirectRule($rule) {
    $rule = trim($rule);

    // ignore comments (lines beginning with #)
    if (preg_match('_^#_', $rule)) {
      return NULL;
    }

    // Split by spaces
    $redirectParts = array_values(array_filter(explode(' ', $rule)));

    // Trim slashes from results
    $redirectParts = array_map(function($v) {
      return trim($v,'/');
    }, $redirectParts);

    $from = $redirectParts[0] ?? NULL;
    $to = $redirectParts[1] ?? NULL;

    return ($from && $to) ? ['from' => $from, 'to' => $to] : NULL;
  }

}
