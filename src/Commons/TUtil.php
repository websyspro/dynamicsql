<?php

namespace Websyspro\DynamicSql\Commons;

use UnitEnum;
use Websyspro\Commons\TList;
use Websyspro\DynamicSql\Shareds\TEqualField;
use Websyspro\DynamicSql\Shareds\TEqualUnitEnum;
use Websyspro\DynamicSql\Shareds\TEqualVar;
use Websyspro\DynamicSql\Shareds\TItemParameter;
use Websyspro\Entity\Shareds\TColumnType;

class TUtil
{
  public static function strToArray(
    string $valueStr  
  ): TList {
    return TList::Create(
      explode(",", preg_replace(
        "/(^\[)|(\]$)/", "", $valueStr
      ))
    )->Mapper(fn(string $str) => trim($str));
  }

  public static function fromEntity(
    TEqualField|TEqualVar|TEqualUnitEnum|string $equal,
    TItemParameter $itemParameter
  ): TEqualField|TEqualVar|TEqualUnitEnum|string {
    $column = $itemParameter->structureTable->Columns()->ListType()->Where(
      fn(TColumnType $columnType) => "\${$itemParameter->name}->{$columnType->name}" === $equal
    );

    if( $column->Count() ){
      return new TEqualField(
        $itemParameter->structureTable, 
        $column->First()->name
      );
    }  

    return $equal;
  }

  public static function fromStatics(
    TEqualField|TEqualVar|TEqualUnitEnum|string $equal,
    TList $static
  ): TEqualField|TEqualVar|TEqualUnitEnum|string {
    if( $equal instanceof TEqualField ){
      return $equal;
    }

    if( preg_match( "/\\$/", $equal ) === 0){
      // "/(!=|==|>=|<=|<>)/"
      if( preg_match( "/(!=|==|>=|<=|<>)/i", $equal ) === 0){
        return new TEqualVar(
          $equal, $static
        );
      }

      return $equal;
    }

    return new TEqualVar(
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
    TEqualField|TEqualVar|TEqualUnitEnum|string $equal
  ): TEqualField|TEqualVar|TEqualUnitEnum|string  {
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
      return new TEqualUnitEnum(
        $constantUnitEnum->value
      );
    }

    return $equal;
  }
}