<?php

namespace Websyspro\DynamicSql\Core;

use Websyspro\Commons\DataList;
use Websyspro\DynamicSql\Interfaces\ICompare;
use Websyspro\DynamicSql\Shareds\Equal;
use Websyspro\DynamicSql\Shareds\ItemParameter;
use Websyspro\DynamicSql\Shareds\Token;

class WhereByFn
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
    $this->defineConditionsNegations();
    $this->defineConditionsToEquals();
    $this->defineConditionsEntitys();
    $this->defineConditionsStaticss();
    $this->defineConditionsUnitEnums();
    $this->defineConditionsEvaluates();
    $this->defineConditionsNullables();
    $this->defineConditionsParseValues();
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
  }

  private function defineConditionsNegations(
  ): void {
    $this->tokens->Mapper(
      function(string $token){
        if(preg_match("/^!\\$\.*/", $token)){
          return sprintf( "%s == false", (
            preg_replace("/^!/", "", $token)
          ));
        }

        return $token;
      }
    );
  }

  private function defineConditionsToEquals(
  ): void {
    $this->tokens->Mapper(
      fn(string $token) => (
        new Equal($token)
      )
    );
  }

  private function defineConditionsEntitys(
  ): void {
    $parameters = (
      $this->getParameters()
    );

    $this->tokens->ForEach(
      fn(Equal $token) => (
        $token->defineEntity(
          $parameters
        )
      )
    );
  }

  private function defineConditionsStaticss(
  ): void {
    $statics = (
      $this->getStatics()
    );

    $this->tokens->ForEach(
      fn(Equal $token) => (
        $token->defineStatics(
          $statics
        )
      )
    );
  }

  private function defineConditionsUnitEnums(
  ): void {
    $this->tokens->ForEach(
      fn(Equal $token) => (
        $token->defineUnitEnums()
      )
    );
  }

  private function defineConditionsEvaluates(
  ): void {
    $this->tokens->ForEach(
      fn(Equal $token) => (
        $token->defineEvaluates()
      )
    );
  }

  private function defineConditionsNullables(
  ): void {
    $this->tokens->ForEach(
      fn(Equal $token) => (
        $token->defineNullables()
      )
    );    
  }

  private function defineConditionsParseValues(
  ): void {
    $this->tokens->ForEach(
      fn(Equal $token) => (
        $token->defineParseValues()
      )
    );
  }

  public function getCompare(
  ): ICompare {
    return new ICompare(
      DataList::Create(
        $this->getParameters()->Mapper(
          fn( ItemParameter $itemParameter ) => (
            $itemParameter->structureTable->table
          )
        )->All()
      ),
      DataList::Create([
        $this->tokens->Copy()->Mapper(
          function(Equal $token){
            return $token->getCompare();
          }
        )->JoinWithSpace()
      ])
    );
  }
}