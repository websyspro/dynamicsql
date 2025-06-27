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
    $this->tokens->Mapper(fn(Token $token) => (
      $token->string === "," ? "$||" : $token->string
    ));
  }

  private function defineConditionsSplits(
  ): void {
    $this->tokens = DataList::Create(
      preg_split( "/\\$\|\|/i", ($this->tokens->JoinNotSpace()), -1, (
        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY 
      ))
    );
  }

  private function defineConditionsNormalizeds(    
  ): void {
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/(\r?\n)/", "", $token ));
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/\s{2,}/", " ", $token ));
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/(^\s*)|(\s*$)/", "", $token ));
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/(===|==|=)/i", "==", $token ));
  }

  private function defineConditionsToEquals(
  ): void {
    $this->tokens->Mapper(
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
      $this->tokens->Reduce(
        [], function(array $curr, Equal $equal){
          [$equalField, $equalVar] = [
            $equal->equals->First(),
            $equal->equals->Last()
          ];

          $curr[$equalField->name] = $equalVar->value;
          return $curr;
        }
      )
    )->All();
  }
}