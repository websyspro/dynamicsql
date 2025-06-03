<?php

namespace Websyspro\DynamicSql;

use Websyspro\Commons\TList;
use Websyspro\DynamicSql\Core\TGroupByFn;
use Websyspro\DynamicSql\Core\TOrderByAscByFn;
use Websyspro\DynamicSql\Core\TOrderByDescByFn;
use Websyspro\DynamicSql\Core\TSelectByFn;
use Websyspro\DynamicSql\Core\TWhereByFn;
use Websyspro\DynamicSql\Shareds\TColumn;
use Websyspro\Entity\Core\TStructureTable;

class TQueryBuild
{
  public string $table;
  public TSelectByFn $select;
  public TWhereByFn $where;
  public TGroupByFn $groupBy;
  public TOrderByAscByFn $orderByAsc;
  public TOrderByDescByFn $orderByDesc;

  public function __construct(
    public string $class
  ){
    $this->defineTable();
  }

  private function defineTable(
  ): void {
    $this->table = (
      new TStructureTable($this->class)
    )->table;
  }

  private function SetProps(
    string $name,
    mixed $prop
  ): TQueryBuild {
    $this->{$name} = $prop;
    return $this;
  }

  public function Select(
    callable $fn
  ): TQueryBuild {
    return $this->SetProps(
      "select", TSelectByFn::Create($fn)
    );
  }

  public function Where(
    callable $fn
  ): TQueryBuild {
    return $this->SetProps(
      "where", TWhereByFn::Create($fn)
    );
  }

  public function GroupBy(
    callable $fn
  ): TQueryBuild {
    return $this->SetProps(
      "groupBy", TGroupByFn::Create($fn)
    );
  }

  public function OrderByAsc(
    callable $fn
  ): TQueryBuild {
    return $this->SetProps(
      "orderByAsc", TOrderByAscByFn::Create($fn)
    );
  }

  public function OrderByDesc(
    callable $fn
  ): TQueryBuild {
    return $this->SetProps(
      "orderByDesc", TOrderByDescByFn::Create($fn)
    );
  }

  private function GetColumns(
  ): string {
    if( $this->select->tokens->Count() === 0 ){
      return "*";  
    }

    return $this->select->tokens->Mapper(
      fn(TColumn $column ) => $column->ToString()
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
      fn(TColumn $column ) => $column->ToString()
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
      fn(TColumn $column) => sprintf( "%s {$oderbyType}", $column->ToString())
    )->All();
  }

  private function GetOrderBy(
  ): string {
    $orderByAsc = $this->GetOrderByList( "orderByAsc", "Asc" );
    $orderByDesc = $this->GetOrderByList( "orderByDesc", "Desc" );

    return sprintf( "Order By %s", TList::Create(
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
  ): TQueryBuild {
    return new static($class);
  }
}