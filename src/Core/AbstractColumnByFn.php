<?php

namespace Websyspro\DynamicSql\Core;

use Websyspro\Commons\Collection;
use Websyspro\DynamicSql\Shareds\Column;
use Websyspro\DynamicSql\Shareds\ItemParameter;
use Websyspro\DynamicSql\Shareds\Token;
use Websyspro\Entity\Shareds\ColumnType;

class AbstractColumnByFn
extends AbstractByFn
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
    $this->tokens->Mapper(fn(Token $token) => $token->getString())->Where(
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
    $this->tokens = Collection::Create(
      explode(",", $this->tokens->JoinNotSpace())
    );
  }

  private function defineColumnsEntitys(
  ): void {
    $this->getParameters()->ForEach(
      fn(ItemParameter $itemParameter) => (
        $itemParameter->structureTable->Columns()->ListType()->ForEach(
          fn(ColumnType $columnType) => (
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
      fn(string $token) => new Column($token) 
    );
  }  

  public function defineColumns(
  ): void {}
}