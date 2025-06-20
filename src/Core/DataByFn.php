<?php

namespace Websyspro\DynamicSql\Core;

use UnitEnum;
use Websyspro\Commons\DataList;
use Websyspro\DynamicSql\Commons\Util;
use Websyspro\DynamicSql\Interfaces\ICompare;
use Websyspro\DynamicSql\Interfaces\IEqualUnitEnum;
use Websyspro\DynamicSql\Shareds\Compare;
use Websyspro\DynamicSql\Shareds\Equal;
use Websyspro\DynamicSql\Shareds\EqualVar;
use Websyspro\DynamicSql\Shareds\EvaluateFromString;
use Websyspro\DynamicSql\Shareds\ItemParameter;
use Websyspro\DynamicSql\Shareds\Token;

class DataByFn
extends AbstractByFn
{
  private array $equals = [
    "!==", "!=",
    "===", "==", "=",
    ">=", "<=", ">", "<", "<>"
  ];

  private array $conditions = [
    "&&", "and",
    "||", "or"
  ];  

  private int $parentheses = 0;
  private bool $isEquals = false;

  public function defines(
  ): void {
    $this->defineConditionsBlocks();
    $this->defineConditionsSplits();
    $this->defineConditionsNormalizeds();
    $this->defineConditionsToEquals();
    $this->defineConditionsEntitys();
    $this->defineConditionsUnitEnums();
    $this->defineConditionsEvaluates();
    $this->defineConditionsNullables();
    $this->defineConditionsStrings();
    // $this->defineConditionsNegations();
  }

  private function defineConditionsBlocks(
  ): void {
    $this->tokens->ForEach(
      function(Token $token){
        if( in_array($token->getString(), $this->equals )){
          $this->isEquals = true;
        }

        if( in_array($token->getString(), $this->conditions )){
          $this->isEquals = false;
        }

        if( $this->isEquals === true ){
          if( $this->parentheses === 0 ){
            if( $token->getString() === ")" ){
                $this->isEquals = false;
            } 
          }
        }
        
        if( $this->isEquals === true ){
          if( $token->getString() === "(" ) $this->parentheses++;
          if( $token->getString() === ")" ) $this->parentheses--;
        }

        if( $this->isEquals === false ) {
          if( $token->getString() === "(" ){
            $token->string = "__(";
          }

          if( $token->getString() === ")" ){
            $token->string = ")__";
          }
        }
      }
    );
  }

  private function defineConditionsSplits(
  ): void {
    $this->tokens = DataList::Create(
      preg_split( "/(\s{1,}&&\s{1,})|(\s{1,}and\s{1,})|(\s{1,}\|\|\s{1,})|(\s{1,}or\s{1,})|(__\()|(\)__)/i", (
        $this->tokens->Mapper(fn(Token $token) => $token->getString())->JoinNotSpace()
      ), -1, ( PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY ))
    )->Where(fn(string $token) => empty( trim( $token )) === false);
  }

  private function defineConditionsNormalizeds(    
  ): void {
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/(\r?\n)/", "", $token ));
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/\s{2,}/", " ", $token ));
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/(^\s*)|(\s*$)/", "", $token ));
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/^__\($/", "(", $token ));
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/^\)__$/", ")", $token ));
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/(^&&$)|(^and$)/i", "And", $token ));
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/(^\|\|$)|(^or$)/i", "Or", $token ));
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/!==/i", "!=", $token ));
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/(===|==)/i", "==", $token ));
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/==/i", "=", $token ));
  }

  // private function defineConditionsNegations(
  // ): void {
  //   $this->tokens->Mapper(
  //     function(string $token){
  //       if(preg_match("/^!\\$\.*/", $token)){
  //         return sprintf( "%s == false", (
  //           preg_replace("/^!/", "", $token)
  //         ));
  //       }

  //       return $token;
  //     }
  //   );
  // }

  private function defineConditionsToEquals(
  ): void {
    $this->tokens->Mapper(
      fn(string $token) => (
        new Compare($token)
      )
    );
  }

  private function defineConditionsEntitys(
  ): void {
    $this->tokens->Mapper(
      function(Compare $compare){
        $compare->equals->Mapper(
          fn(DataList $equalItems) => DataList::Create([
            preg_replace(
              "/^\\$\w*->/", "", $equalItems->First()
            ), new EqualVar($equalItems->Last(), $this->getStatics())
          ])
        );  
        
        return $compare;
      }
    );
  }

  private function defineConditionsUnitEnums(
  ): void {
    $this->tokens->Mapper(
      function(Compare $compare){
        $compare->equals->Mapper(
          function(DataList $equalItems){
            $hasUnitEnum = Util::fromUnitEnum(
              $equalItems->Last()->value
            );

            return DataList::Create([
              $equalItems->First(), (
                $hasUnitEnum instanceof IEqualUnitEnum
                  ? $hasUnitEnum : $equalItems->Last()
              )
            ]);
          }
        );  
        
        return $compare;
      }
    );
  }

  private function defineConditionsEvaluates(
  ): void {
    $this->tokens->Mapper(
      function(Compare $compare){
        $compare->equals->Mapper(
          function(DataList $equalItems){
            $equalItems->Last()->value = (
              EvaluateFromString::Execute(
                $equalItems->Last()->value
              )
            );

            return DataList::Create([
              $equalItems->First(),
                $equalItems->Last()
            ]);
          }
        );  
        
        return $compare;
      }
    );
  }

  private function defineConditionsNullables(
  ): void {
    $this->tokens->Mapper(
      function(Compare $compare){
        $compare->equals->Mapper(
          function(DataList $equalItems){
            if(preg_match("/null/i", $equalItems->Last()->value) === 1){
              $equalItems->Last()->value = strtoupper(
                $equalItems->Last()->value
              );
            }

            return DataList::Create([
              $equalItems->First(), $equalItems->Last()
            ]);
          }
        );  
        
        return $compare;
      }
    );    
  }

    private function defineConditionsStrings(
  ): void {
    $this->tokens->Mapper(
      function(Compare $compare){
        $compare->equals->Mapper(
          function(DataList $equalItems){
            $equalItems->Last()->value = trim(
              $equalItems->Last()->value, "\"'"
            );

            return DataList::Create([
              $equalItems->First(), $equalItems->Last()
            ]);
          }
        );  
        
        return $compare;
      }
    );    
  }

  public function getData(
  ): array {
    return [
      $this->tokens->Reduce([], function(array $curr, Compare $item){
        return (
          $item->equals->Reduce([], function(array $currItem, DataList $equalItems){
            $currItem[$equalItems->First()] = $equalItems->Last()->value;
            return $currItem;
          })->All()
        );
      })->All()
    ];
  }
}