<?php

namespace Websyspro\DynamicSql;

use ReflectionParameter;
use Websyspro\Commons\Collection;
use Websyspro\Commons\Reflect;
use Websyspro\Commons\Util;
use Websyspro\DynamicSql\Shareds\ItemParameter;
use Websyspro\DynamicSql\Utils\ArrowFN;

class ColumnsByFN
extends ArrowFN
{
  public Collection $parameters;
  public Collection $columns;

  public function init(
  ): void {
    $this->ParseFill();
    $this->ParseParameters();
    $this->ParseFIeldsSplit();
    $this->ParseEntityFields();
    $this->ParseClear();
  }

  public function ParseParameters(
  ): void {
    $this->parameters = Collection::Create(
      Reflect::FN($this->bodyFN)->getParameters()
    );

    $this->parameters->Mapper(
      fn(ReflectionParameter $rp) => (
        new ItemParameter(
          $rp->getType()->getName(), 
          $rp->getName()
        )
      )
    );    
  }

  public function ParseFIeldsSplit(
  ): void {
    $this->columns = (
      Collection::Create(
        Util::SplitWithComma(
          $this->body->First()
        )
      )
    );

    $this->columns->Mapper(
      fn(string $column) => preg_replace(
        ["/^\\$/", "/->/"], ["", "."], trim($column)
      )
    );
  }

  public function ParseEntityFields(
  ): void {
    $this->parameters->ForEach(
      function(ItemParameter $itemParameter){
        $this->columns->Mapper(fn(string $column) => (
          preg_replace("/$itemParameter->name/", $itemParameter->structureTable->table, $column)
        ));
      }
    );
  }

  public function Columns(
  ): Collection {
    return $this->columns;
  }
}