<?php

namespace Websyspro\DynamicSql\Commons;

use UnitEnum;
use Websyspro\Commons\DataList;
use Websyspro\DynamicSql\Shareds\EqualField;
use Websyspro\DynamicSql\Interfaces\IEqualUnitEnum;
use Websyspro\DynamicSql\Shareds\EqualVar;
use Websyspro\DynamicSql\Shareds\ItemParameter;
use Websyspro\Entity\Interfaces\IColumnType;

class Util
{
  public static function strToArray(
    string $valueStr  
  ): DataList {
    return DataList::Create(
      explode(",", preg_replace(
        "/(^\[)|(\]$)/", "", $valueStr
      ))
    )->Mapper(fn(string $str) => trim($str));
  }

  // public static function fromEntity(
  //   EqualField|EqualVar|IEqualUnitEnum|string $equal,
  //   ItemParameter $itemParameter
  // ): EqualField|EqualVar|IEqualUnitEnum|string {
  //   $column = $itemParameter->structureTable->Columns()->ListType()->Where(
  //     fn(IColumnType $columnType) => "\${$itemParameter->name}->{$columnType->name}" === $equal
  //   );

  //   if( $column->Count() ){
  //     return new EqualField(
  //       $itemParameter->structureTable, 
  //       $column->First()->name
  //     );
  //   }  

  //   return $equal;
  // }

  // public static function fromStatics(
  //   EqualField|EqualVar|IEqualUnitEnum|string $equal,
  //   DataList $static
  // ): EqualField|EqualVar|IEqualUnitEnum|string {
  //   if( $equal instanceof EqualField ){
  //     return $equal;
  //   }

  //   if( preg_match( "/\\$/", $equal ) === 0){
  //     if( preg_match( "/(!=|==|>=|<=|<>)/i", $equal ) === 0){
  //       return new EqualVar(
  //         $equal, $static
  //       );
  //     }

  //     return $equal;
  //   }

  //   return new EqualVar(
  //     $equal, $static
  //   );
  // }
  
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

  // public static function fromUnitEnum(
  //   EqualField|EqualVar|IEqualUnitEnum|string $equal
  // ): EqualField|EqualVar|IEqualUnitEnum|string  {
  //   if( is_string( $equal ) === false ){
  //     return $equal;
  //   }

  //   $hasConstante = preg_match(
  //     "/^[A-Za-z_][A-Za-z0-9_]*::[A-Za-z_][A-Za-z0-9_]*->(value|name)*$/", $equal
  //   );

  //   if( $hasConstante === 0 ){
  //     return $equal; 
  //   }

  //   $hasConstanteValue = preg_match("/->value$/", $equal);
  //   $hasConstanteName = preg_match("/->name$/", $equal);

  //   $constantUnitEnum = (
  //     constant(
  //       preg_replace(
  //         "/->(value|name)*$/", "", $equal
  //       )
  //     )
  //   );

  //   if($hasConstanteValue === 1){
  //     if( $constantUnitEnum instanceof UnitEnum ){
  //       return new IEqualUnitEnum(
  //         $constantUnitEnum->value
  //       );
  //     }
  //   } else
  //   if($hasConstanteName === 1){
  //     if( $constantUnitEnum instanceof UnitEnum ){
  //       return new IEqualUnitEnum(
  //         $constantUnitEnum->name
  //       );
  //     }      
  //   }

  //   return $equal;
  // }
}