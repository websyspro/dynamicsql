<?php

namespace Websyspro\DynamicSql\Core;

use Websyspro\Commons\TList;
use Websyspro\DynamicSql\Shareds\TColumn;
use Websyspro\DynamicSql\Shareds\TItemParameter;
use Websyspro\DynamicSql\Shareds\TToken;
use Websyspro\Entity\Shareds\TColumnType;

class TAbstractColumnByFn
extends TAbstractByFn
{
  public function defines(
  ): void {
    $this->defineColumnsToString();
    $this->defineColumnsNormalizeds();
    $this->defineColumnsSplits();
    $this->defineColumnsEntitys();
    $this->defineColumnsCreate();
    $this->defineColumns();
  }
  
  private function defineColumnsToString(
  ): void {
    $this->tokens->Mapper(fn(TToken $token) => $token->getString())->Where(
      fn(string $token) => empty(trim($token)) === false
    );
  }

  private function defineColumnsNormalizeds(
  ): void {
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/(\r?\n)/", "", $token ));
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/\s{2,}/", " ", $token ));
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/(^\s*)|(\s*$)/", "", $token ));
    $this->tokens->Mapper(fn(string $token) => preg_replace( "/(\")|(\')/", "", $token ));
  }

  private function defineColumnsSplits(
  ): void {
    $this->tokens = TList::Create(
      explode(",", $this->tokens->JoinNotSpace())
    );
  }

  private function defineColumnsEntitys(
  ): void {
    $this->getParameters()->ForEach(
      fn(TItemParameter $itemParameter) => (
        $itemParameter->structureTable->Columns()->ListType()->ForEach(
          fn(TColumnType $columnType) => (
            $this->tokens->Mapper(
              fn(string $token) => preg_replace(
                "/\\$$itemParameter->name->$columnType->name/", 
                "{$itemParameter->structureTable->table}.{$columnType->name}", $token 
              ) 
            )
          )
        )
      )
    );
  }

  private function defineColumnsCreate(
  ): void {
    $this->tokens->Mapper(
      fn(string $token) => new TColumn($token) 
    );
  }  

  public function defineColumns(
  ): void {}
}