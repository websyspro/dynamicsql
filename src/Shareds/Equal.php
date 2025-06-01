<?php

namespace Websyspro\DynamicSql\Shareds;

use Websyspro\Commons\Collection;
use Websyspro\DynamicSql\Commons\Util;
use Websyspro\DynamicSql\Enums\EqualType;
use Websyspro\Entity\Enums\ColumnType;

class Equal
{
  public Collection $equals;
  public EqualType $equalType;

  public function __construct(
    public string $value
  ){
    $this->define();
    $this->defineEqualType();
  }

  private function define(
  ): void {
    $this->equals = Collection::Create(
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
    Collection $parameters
  ): void {
    if( $this->equalType === EqualType::Equal ){
      $parameters->ForEach( fn(ItemParameter $itemParameter) => (
        $this->equals->Mapper( fn(EqualField|EqualVar|EqualUnitEnum|string $equal) => (
          Util::fromEntity( $equal, $itemParameter )
        ))
      ));
    }
  }

  public function defineStatics(
    Collection $statics
  ): void {
    if( $this->equalType === EqualType::Equal ){
      $this->equals->Mapper( fn(EqualField|EqualVar|EqualUnitEnum|string $equal) => (
        Util::fromStatics( $equal, $statics )
      ));
    }
  }

  public function defineUnitEnums(
  ): void {
    if( $this->equalType === EqualType::Equal ){
      $this->equals->Mapper( fn(EqualField|EqualVar|EqualUnitEnum|string $equal) => (
        Util::fromUnitEnum( $equal )
      ));      
    }
  }

  public function defineEvaluates(
  ): void {
    if( $this->equalType === EqualType::Equal ){
      $this->equals->ForEach(
        function(EqualField|EqualVar|EqualUnitEnum|string $equal){
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
        function(EqualField|EqualVar|EqualUnitEnum|string $equal){
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
    EqualField|EqualVar|EqualUnitEnum|string $equal
  ): bool {
    return $equal instanceof EqualField;
  }

  private function hasParsed(
    EqualField|EqualVar|EqualUnitEnum|string $equal
  ): bool {
    return $equal instanceof EqualVar
        || $equal instanceof EqualUnitEnum;
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
  
  public function defineParseValues(
  ): void {
    if( $this->equalType === EqualType::Equal ){
      [ $equalA, $_, $equalC ] = (
        $this->equals->All()
      ); 

      if($this->hasField($equalA)){
        if($this->hasParsed($equalC)){
          $equalC->value = $this->defineParse(
            $equalA->columnType, $equalC->value
          );
        }
      }

      if($this->hasField($equalC)){
        if($this->hasParsed($equalA)){
          $equalA->value = $this->defineParse(
            $equalC->columnType, $equalA->value
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
    EqualField|EqualVar|EqualUnitEnum|string|null $equal,
    EqualField|EqualVar|EqualUnitEnum|string $compare
  ): string {
    if( is_null( $equal ) === false ){
      if( preg_match("/(^NULL$)|(^\((.*?)\s*\)$)/", $equal->value )){
        if( preg_match( "/^NULL$/", $equal->value )){
          return $compare === "==" ? "Is" : "Not";
        }

        if( preg_match( "/^\((.*?)\s*\)$/", $equal->value )){
          return $compare === "==" ? "In" : "Not In";
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