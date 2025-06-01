<?php

namespace Websyspro\DynamicSql;

use Websyspro\Commons\Collection;
use Websyspro\DynamicSql\Core\GroupByFn;
use Websyspro\DynamicSql\Core\OrderByAscByFn;
use Websyspro\DynamicSql\Core\OrderByDescByFn;
use Websyspro\DynamicSql\Core\SelectByFn;
use Websyspro\DynamicSql\Core\WhereByFn;
use Websyspro\DynamicSql\Shareds\Column;
use Websyspro\Entity\Core\StructureTable;

class QueryBuild
{
  public string $table;
  public SelectByFn $select;
  public WhereByFn $where;
  public GroupByFn $groupBy;
  public OrderByAscByFn $orderByAsc;
  public OrderByDescByFn $orderByDesc;

  public function __construct(
    public string $class
  ){
    $this->defineTable();
  }

  private function defineTable(
  ): void {
    $this->table = (
      new StructureTable($this->class)
    )->table;
  }

  private function SetProps(
    string $name,
    mixed $prop
  ): QueryBuild {
    $this->{$name} = $prop;
    return $this;
  }

  public function Select(
    callable $fn
  ): QueryBuild {
    return $this->SetProps(
      "select", SelectByFn::Create($fn)
    );
  }

  public function Where(
    callable $fn
  ): QueryBuild {
    return $this->SetProps(
      "where", WhereByFn::Create($fn)
    );
  }

  public function GroupBy(
    callable $fn
  ): QueryBuild {
    return $this->SetProps(
      "groupBy", GroupByFn::Create($fn)
    );
  }

  public function OrderByAsc(
    callable $fn
  ): QueryBuild {
    return $this->SetProps(
      "orderByAsc", OrderByAscByFn::Create($fn)
    );
  }

  public function OrderByDesc(
    callable $fn
  ): QueryBuild {
    return $this->SetProps(
      "orderByDesc", OrderByDescByFn::Create($fn)
    );
  }

  private function GetColumns(
  ): string {
    if( $this->select->tokens->Count() === 0 ){
      return "*";  
    }

    return $this->select->tokens->Mapper(
      fn(Column $column ) => $column->ToString()
    )->JoinWithComma();
  }

  private function GetFroms(
  ): string {
    $compare = $this->where->getCompare();
    if( $compare->froms->Count() === 0 ){
      return $this->table;
    }

    return $compare->froms->JoinWithComma();
  }

  private function GetWheres(
  ): string {
    $compare = $this->where->getCompare();
    if( $compare->conditions->Count() === 0 ){
      return "1=1";
    }

    return $compare->conditions->JoinNotSpace();
  }

  private function GetGroupBy(
  ): string {
    if( $this->groupBy->tokens->Count() === 0 ){
      return "";
    }

    return sprintf( "Group By %s", $this->groupBy->tokens->Mapper(
      fn(Column $column ) => $column->ToString()
    )->JoinWithComma());
  }

  private function GetOrderByList(
    string $oderbyProps,
    string $oderbyType
  ): array {
    if( isset( $this->{$oderbyProps}) === false ){
      return [];
    }

    if( $this->{$oderbyProps}->tokens->Count() === 0 ){
      return [];
    }

    return $this->{$oderbyProps}->tokens->Mapper(
      fn(Column $column) => sprintf( "%s {$oderbyType}", $column->ToString())
    )->All();
  }

  private function GetOrderBy(
  ): string {
    $orderByAsc = $this->GetOrderByList( "orderByAsc", "Asc" );
    $orderByDesc = $this->GetOrderByList( "orderByDesc", "Desc" );

    return sprintf( "Order By %s", Collection::Create(
      array_merge( $orderByAsc, $orderByDesc )
    )->JoinWithComma());
  }

  public function Get(
  ): string {
    return sprintf(
      "Select %s From %s Where %s %s %s", ...[
        $this->GetColumns(),
        $this->GetFroms(),
        $this->GetWheres(),
        $this->GetGroupBy(),
        $this->GetOrderBy()
      ]
    );
  }
  
  public static function Create(
    string $class
  ): QueryBuild {
    return new static($class);
  }
}