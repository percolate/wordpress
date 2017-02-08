<?php

/**
 * PercolateHelpers
 */
class PercolateHelpers
{

  /**
   * Get the original Schema ID
   *   we have schema versioning now, and post[schema_id] contains the version too
   *   eg. schema:00000000_11111111
   *
   * @param string $schemaId Schema ID with version number
   *
   * @return string Original Schema ID
   */
  public static function getOriginalSchemaId($schemaId)
  {
    $postSchema = explode("_", $schemaId);
    return $postSchema[0];
  }


  public static function searchInArray($array, $key, $value) {
    $results = array();

    if (is_array($array)) {
      if (isset($array[$key]) && $array[$key] == $value) {
          $results[] = $array;
      }

      foreach ($array as $subarray) {
          $results = array_merge($results, self::searchInArray($subarray, $key, $value));
      }
    }

    return $results;
  }
}
