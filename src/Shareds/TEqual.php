<?php

namespace Websyspro\DynamicSql\Shareds;

use Websyspro\Commons\TList;
use Websyspro\DynamicSql\Commons\TUtil;
use Websyspro\DynamicSql\Enums\TEqualType;
use Websyspro\Entity\Enums\TColumnType;

class TEqual
{
  public TList $equals;
  public TEqualType $equalType;

  public function __construct(
    public string $value
  ){
    $this->define();
    $this->defineEqualType();
  }

  private function define(
  ): void {
    $this->equals = TList::Create(
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
      $this->equalType = TEqualType::Logical;
    } else
    if( preg_match("/(^\\($)/", $this->value )){
      $this->equalType = TEqualType::StartGroup;
    } else
    if( preg_match("/(^\\)$)/", $this->value )){
      $this->equalType = TEqualType::EndGroup;
    } else
    if( preg_match("/(!=|==|>=|<=|<>)/", $this->value )){
      $this->equalType = TEqualType::Equal;
    }
  }

  public function defineEntity(
    TList $parameters
  ): void {
    if( $this->equalType === TEqualType::Equal ){
      $parameters->ForEach( fn(TItemParameter $itemParameter) => (
        $this->equals->Mapper( fn(TEqualField|TEqualVar|TEqualUnitEnum|string $equal) => (
          TUtil::fromEntity( $equal, $itemParameter )
        ))
      ));
    }
  }

  public function defineStatics(
    TList $statics
  ): void {
    if( $this->equalType === TEqualType::Equal ){
      $this->equals->Mapper( fn(TEqualField|TEqualVar|TEqualUnitEnum|string $equal) => (
        TUtil::fromStatics( $equal, $statics )
      ));
    }
  }

  public function defineUnitEnums(
  ): void {
    if( $this->equalType === TEqualType::Equal ){
      $this->equals->Mapper( fn(TEqualField|TEqualVar|TEqualUnitEnum|string $equal) => (
        TUtil::fromUnitEnum( $equal )
      ));      
    }
  }

  public function defineEvaluates(
  ): void {
    if( $this->equalType === TEqualType::Equal ){
      $this->equals->ForEach(
        function(TEqualField|TEqualVar|TEqualUnitEnum|string $equal){
          if( $equal instanceof TEqualVar ){
            $equal->value = TEvaluateFromString::Execute( $equal->value );
          }
        }
      );
    }
  }

  public function defineNullables(
  ): void {
    if( $this->equalType === TEqualType::Equal ){
      $this->equals->ForEach(
        function(TEqualField|TEqualVar|TEqualUnitEnum|string $equal){
          if( $equal instanceof TEqualVar ){
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
    TEqualField|TEqualVar|TEqualUnitEnum|string $equal
  ): bool {
    return $equal instanceof TEqualField;
  }

  private function hasParsed(
    TEqualField|TEqualVar|TEqualUnitEnum|string $equal
  ): bool {
    return $equal instanceof TEqualVar
        || $equal instanceof TEqualUnitEnum;
  }
  
  private function defineParse(
    TColumnType $columnType,
    string $value
  ): string {
    $hasArray = preg_match(
      "/^\[\s*(.*?)\s*\]/", $value
    ) === 1;

    if( $hasArray ){
      $value = (
        sprintf("(%s)", (
          TUtil::strToArray($value)->Mapper(
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
    if( $this->equalType === TEqualType::Equal ){
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
    TEqualField $equal
  ): string {
    return "{$equal->table}.{$equal->name}";
  }

  private function getCompareToCompare(
    TEqualField|TEqualVar|TEqualUnitEnum|string|null $equal,
    TEqualField|TEqualVar|TEqualUnitEnum|string $compare
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
    if($this->equalType !== TEqualType::Equal){
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