<?php

namespace Websyspro\DynamicSql\Shareds;

use Websyspro\Commons\DataList;
use Websyspro\DynamicSql\Commons\Util;
use Websyspro\DynamicSql\Enums\EqualType;
use Websyspro\DynamicSql\Interfaces\IEqualUnitEnum;
use Websyspro\Entity\Enums\ColumnType;

class Equal
{
  public DataList $equals;
  public EqualType $equalType;

  public function __construct(
    public string $value
  ){
    $this->define();
    $this->defineEqualType();
  }

  private function define(
  ): void {
    $this->equals = DataList::Create(
      preg_split("/(!=|==|>=|<=|<>)/", $this->value, 2, (
        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
      ))
    );
    
    $this->equals->Mapper(
      fn(string $equal) => trim($equal)
    );
  }

  private function defineEqualType(
  ): void {
    if( preg_match("/(^And$)|(^Or$)/", $this->value )){
      $this->equalType = EqualType::Logical;
    } else
    if( preg_match("/(^\\($)/", $this->value )){
      $this->equalType = EqualType::StartGroup;
    } else
    if( preg_match("/(^\\)$)/", $this->value )){
      $this->equalType = EqualType::EndGroup;
    } else
    if( preg_match("/(!=|==|>=|<=|<>)/", $this->value )){
      $this->equalType = EqualType::Equal;
    }
  }

  public function defineEntity(
    DataList $parameters
  ): void {
    if( $this->equalType === EqualType::Equal ){
      $parameters->ForEach( fn(ItemParameter $itemParameter) => (
        $this->equals->Mapper( fn(EqualField|EqualVar|IEqualUnitEnum|string $equal) => (
          Util::fromEntity( $equal, $itemParameter )
        ))
      ));
    }
  }

  public function defineStatics(
    DataList $statics
  ): void {
    if( $this->equalType === EqualType::Equal ){
      $this->equals->Mapper( fn(EqualField|EqualVar|IEqualUnitEnum|string $equal) => (
        Util::fromStatics( $equal, $statics )
      ));
    }
  }

  public function defineUnitEnums(
  ): void {
    if( $this->equalType === EqualType::Equal ){
      $this->equals->Mapper( fn(EqualField|EqualVar|IEqualUnitEnum|string $equal) => (
        Util::fromUnitEnum( $equal )
      ));      
    }
  }

  public function defineEvaluates(
  ): void {
    if( $this->equalType === EqualType::Equal ){
      $this->equals->ForEach(
        function(EqualField|EqualVar|IEqualUnitEnum|string $equal){
          if( $equal instanceof EqualVar ){
            $equal->value = EvaluateFromString::Execute( $equal->value );
          }
        }
      );
    }
  }

  public function defineNullables(
  ): void {
    if( $this->equalType === EqualType::Equal ){
      $this->equals->ForEach(
        function(EqualField|EqualVar|IEqualUnitEnum|string $equal){
          if( $equal instanceof EqualVar ){
            if( preg_match( "/null/i", $equal->value )){
              $equal->value = strtoupper(
                $equal->value
              );
            }
          }
        }
      );
    }    
  }

  private function hasField(
    EqualField|EqualVar|IEqualUnitEnum|string $equal
  ): bool {
    return $equal instanceof EqualField;
  }

  private function hasParsed(
    EqualField|EqualVar|IEqualUnitEnum|string $equal
  ): bool {
    return $equal instanceof EqualVar
        || $equal instanceof IEqualUnitEnum;
  }
  
  private function defineParse(
    ColumnType $columnType,
    string $value
  ): string {
    $hasArray = preg_match(
      "/^\[\s*(.*?)\s*\]/", $value
    ) === 1;

    if( $hasArray ){
      $value = (
        sprintf("(%s)", (
          Util::strToArray($value)->Mapper(
            fn(string $str) => (
              $columnType->Encode(
                $str
              )
            )
          )->JoinWithComma()
        ))
      );
    } else {
      $value = (
        $columnType->Encode(
          preg_replace(
            "/(^\")|(\"$)|(^')|('$)/", "", (
              $value
            )
          )
        )
      );
    }

    return $value;
  }

  public function valueOfDatetime(
    ColumnType $columnType,
    string $value
  ): string {
    if($columnType !== ColumnType::Datetime){
      return $value;
    }

    if(strlen($value) === 10){
      return "{$value} 00:00:00";
    }

    return $value;
  }
  
  public function defineParseValues(
  ): void {
    if( $this->equalType === EqualType::Equal ){
      [ $equalA, $_, $equalC ] = (
        $this->equals->All()
      ); 

      if($this->hasField($equalA)){
        if($this->hasParsed($equalC)){
          $equalC->value = $this->defineParse(
            $equalA->columnType, $this->valueOfDatetime(
              $equalA->columnType, $equalC->value
            )
          );
        }
      }

      if($this->hasField($equalC)){
        if($this->hasParsed($equalA)){
          $equalA->value = $this->defineParse(
            $equalC->columnType, $this->valueOfDatetime(
              $equalC->columnType, $equalA->value
            )
          );
        }
      }
    }    
  }

  private function getCompareIsField(
    EqualField $equal
  ): string {
    return "{$equal->table}.{$equal->name}";
  }

  private function getCompareToCompare(
    EqualField|EqualVar|IEqualUnitEnum|string|null $equal,
    EqualField|EqualVar|IEqualUnitEnum|string $compare
  ): string {
    if( is_null( $equal ) === false ){
      if( preg_match("/(^NULL$)|(^\((.*?)\s*\)$)|(%)/", $equal->value )){
        if( preg_match( "/^NULL$/", $equal->value )){
          return $compare === "==" ? "Is" : "Not";
        }

        if( preg_match( "/^\((.*?)\s*\)$/", $equal->value )){
          return $compare === "==" ? "In" : "Not In";
        }

        if( preg_match( "/%/", $equal->value )){
          return $compare === "==" ? "Like" : "Not Like";
        }
      }
    }

    return preg_replace([ "/==/" ], [ "=" ], $compare);
  }

  public function getCompare(
  ): string|null {
    if($this->equalType !== EqualType::Equal){
      return $this->equals->First(); 
    } else {
      [ $equalA, $equalB, $equalC ] = (
        $this->equals->All()
      );

      if($this->hasField($equalA)){
        if($this->hasParsed($equalC)){
          return sprintf("%s %s %s", 
            $this->getCompareIsField($equalA), 
            $this->getCompareToCompare(
              $equalC, $equalB
            ), $equalC->value
          );
        }
      }

      if($this->hasField($equalC)){
        if($this->hasParsed($equalA)){
          return sprintf("%s %s %s", 
            $this->getCompareIsField($equalC), 
            $this->getCompareToCompare(
              $equalA, $equalB
            ), $equalA->value
          );       
        }
      }

      if($this->hasField($equalA)){
        if($this->hasField($equalC)){
          return sprintf("%s %s %s", 
            $this->getCompareIsField($equalA), 
            $this->getCompareToCompare(
              null, $equalB
            ), $this->getCompareIsField($equalC)
          );       
        }
      }      

      return null;
    }
  }
}