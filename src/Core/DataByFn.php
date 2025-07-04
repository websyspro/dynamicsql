<?php

namespace Websyspro\DynamicSql\Core;

use Websyspro\Commons\DataList;
use Websyspro\DynamicSql\Shareds\Equal;
use Websyspro\DynamicSql\Shareds\Token;

class DataByFn
extends AbstractByFn
{
  public function defines(
  ): void {
    $this->defineConditionsBlocks();
    $this->defineConditionsSplits();
    $this->defineConditionsNormalizeds();
    $this->defineConditionsToEquals();
  }

  private function defineConditionsBlocks(
  ): void {
    $this->tokens->mapper(fn(Token $token) => (
      $token->string === "," ? "$||" : $token->string
    ));
  }

  private function defineConditionsSplits(
  ): void {
    $this->tokens = DataList::create(
      preg_split( "/\\$\|\|/i", ($this->tokens->joinNotSpace()), -1, (
        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY 
      ))
    );
  }

  private function defineConditionsNormalizeds(    
  ): void {
    $this->tokens->mapper(fn(string $token) => preg_replace( "/(\r?\n)/", "", $token ));
    $this->tokens->mapper(fn(string $token) => preg_replace( "/\s{2,}/", " ", $token ));
    $this->tokens->mapper(fn(string $token) => preg_replace( "/(^\s*)|(\s*$)/", "", $token ));
    $this->tokens->mapper(fn(string $token) => preg_replace( "/(===|==|=)/i", "==", $token ));
  }

  private function defineConditionsToEquals(
  ): void {
    $this->tokens->mapper(
      function(string $token){
        return new Equal(
          $token, 
          $this->getParameters(), 
          $this->getStatics()
        );
      }
    );
  }

  public function arrayFromFn(
  ): array {
    return (
      $this->tokens->reduce(
        [], function(array $curr, Equal $equal){
          [$equalField, $equalVar] = [
            $equal->equals->first(),
            $equal->equals->last()
          ];

          $curr[$equalField->name] = $equalVar->value;
          return $curr;
        }
      )
    )->All();
  }
}