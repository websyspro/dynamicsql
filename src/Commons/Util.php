<?php

namespace Websyspro\DynamicSql\Commons;

use Websyspro\Commons\DataList;

class Util
{
  public static function strToArray(
    string $valueStr  
  ): DataList {
    return DataList::create(
      explode(",", preg_replace(
        "/(^\[)|(\]$)/", "", $valueStr
      ))
    )->mapper(fn(string $str) => trim($str));
  }
  
  public static function ParseBodyStaticsUnion(
    array $array,
    string $prefix = ""
  ): array {
    $result = [];

    foreach ($array as $key => $value) {
      $newKey = $prefix === "" ? $key : "{$prefix}.{$key}";

      if (is_array($value) || is_object($value)) {
        if (is_array($value) && array_keys($value) === range(0, count($value) - 1)) {
          $result[$newKey] = json_encode($value);
        } else {
          $flattened = Util::ParseBodyStaticsUnion((array)$value, $newKey);
          foreach ($flattened as $fKey => $fValue) {
              $result[$fKey] = $fValue;
          }
        }
      } else {
        $result[$newKey] = $value;
      }
    }

    return $result;
  }
}