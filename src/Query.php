<?php

namespace Websyspro\DynamicSql;

use Websyspro\DynamicSql\ByFns\GroupByFN;
use Websyspro\DynamicSql\ByFns\OrderByAscByFN;
use Websyspro\DynamicSql\ByFns\OrderByDescByFN;
use Websyspro\DynamicSql\ByFns\SelectByFN;
use Websyspro\DynamicSql\ByFns\WhereByFN;
use Websyspro\DynamicSql\Interfaces\ColumnsProps;
use Websyspro\DynamicSql\Interfaces\WhereProps;
use Websyspro\Entity\Core\StructureTable;

class Query
{
  public WhereProps $wheres;
  public ColumnsProps $columnsGroupBy;
  public ColumnsProps $columnsOrderByAscs;
  public ColumnsProps $columnsOrderByDescs;
  public ColumnsProps $columnsSelects;

  public function __construct(
    public string $entity
  ){}

  public static function Build(
    string $entity
  ): static {
    return new static($entity);
  }

  public function UpdateProp(
    string $property,
    WhereProps | ColumnsProps $propertyValue
  ): Query {
    $this->{$property} = $propertyValue;
    return $this;
  }

  public function Where(
    callable $fn  
  ): self {
    return $this->UpdateProp( "wheres", (
      WhereByFN::Parse($fn)->Where()
    ));
  }

  public function GroupBy(
    callable $fn
  ): self {
    return $this->UpdateProp( "columnsGroupBy", (
      GroupByFN::Parse($fn)->Columns()
    ));
  }

  public function OrderByAsc(
    callable $fn
  ): Query {
    return $this->UpdateProp( "columnsOrderByAscs", (
      OrderByAscByFN::Parse($fn)->Columns()
    ));
  }

  public function OrderByDesc(
    callable $fn
  ): Query {
    return $this->UpdateProp( "columnsOrderByDescs", (
      OrderByDescByFN::Parse($fn)->Columns()
    ));
  }  

  public function Select(
    callable $fn
  ): Query {
    return $this->UpdateProp( "columnsSelects", (
      SelectByFN::Parse($fn)->Columns()
    ));
  }

  public function ValidProp(
    string $property
  ): bool {
    if(isset($this->{$property}) === false) return false;
    if($this->{$property}->columns->Count() === 0) return false;

    return true;
  }

  public function ColumnsTables(
  ): string {
    if($this->ValidProp("columnsSelects")){
      return $this->columnsSelects->columns->JoinWithComma();
    }

    return "*";
  }

  public function FromTables(
  ): string {
    if(isset($this->wheres) === true){
      if($this->wheres->conditions->Count() !== 0){
        return $this->wheres->table->JoinWithComma();
      }
    }

    return (new StructureTable($this->entity))->table;
  }

  public function WheresTables(
  ): string {
    if(isset($this->wheres) === true){
      if($this->wheres->conditions->Count() !== 0){
        return "Where {$this->wheres->conditions->JoinWithSpace()}";
      }
    }

    return "Where 1=1";
  }

  public function Sql(
  ): string {
    return sprintf(
      "Select %s From %s %s", ...[
        $this->ColumnsTables(),
        $this->FromTables(),
        $this->WheresTables()
      ]
    );
  }
}