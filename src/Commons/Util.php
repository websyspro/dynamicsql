<?php

namespace Websyspro\DynamicSql\Commons;

use UnitEnum;
use Websyspro\Commons\Collection;
use Websyspro\DynamicSql\Shareds\EqualField;
use Websyspro\DynamicSql\Shareds\EqualUnitEnum;
use Websyspro\DynamicSql\Shareds\EqualVar;
use Websyspro\DynamicSql\Shareds\ItemParameter;
use Websyspro\Entity\Shareds\ColumnType;

class Util
{
  public static function strToArray(
    string $valueStr  
  ): Collection {
    return Collection::Create(
      explode(",", preg_replace(
        "/(^\[)|(\]$)/", "", $valueStr
      ))
    )->Mapper(fn(string $str) => trim($str));
  }

  public static function fromEntity(
    EqualField|EqualVar|EqualUnitEnum|string $equal,
    ItemParameter $itemParameter
  ): EqualField|EqualVar|EqualUnitEnum|string {
    $column = $itemParameter->structureTable->Columns()->ListType()->Where(
      fn(ColumnType $columnType) => "\${$itemParameter->name}->{$columnType->name}" === $equal
    );

    if( $column->Count() ){
      return new EqualField(
        $itemParameter->structureTable, 
        $column->First()->name
      );
    }  

    return $equal;
  }

  public static function fromStatics(
    EqualField|EqualVar|EqualUnitEnum|string $equal,
    Collection $static
  ): EqualField|EqualVar|EqualUnitEnum|string {
    if( $equal instanceof EqualField ){
      return $equal;
    }

    if( preg_match( "/\\$/", $equal ) === 0){
      // "/(!=|==|>=|<=|<>)/"
      if( preg_match( "/(!=|==|>=|<=|<>)/i", $equal ) === 0){
        return new EqualVar(
          $equal, $static
        );
      }

      return $equal;
    }

    return new EqualVar(
      $equal, $static
    );
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
          $flattened = static::ParseBodyStaticsUnion((array)$value, $newKey);
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

  public static function fromUnitEnum(
    EqualField|EqualVar|EqualUnitEnum|string $equal
  ): EqualField|EqualVar|EqualUnitEnum|string  {
    if( is_string( $equal ) === false ){
      return $equal;
    }

    $hasConstante = preg_match(
      "/^[A-Za-z_][A-Za-z0-9_]*::[A-Za-z_][A-Za-z0-9_]*$/", $equal
    );

    if( $hasConstante === 0 ){
      return $equal; 
    }

    $constantUnitEnum = (
      constant(
        $equal
      )
    );


    if( $constantUnitEnum instanceof UnitEnum ){
      return new EqualUnitEnum(
        $constantUnitEnum->value
      );
    }

    return $equal;
  }
}