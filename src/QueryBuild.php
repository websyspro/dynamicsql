<?php

namespace Websyspro\DynamicSql;

use Websyspro\Commons\DataList;
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
    if(isset($this->select) === false){
      return "*";
    }

    if($this->select->tokens->Count() === 0){
      return "*";  
    }

    return $this->select->tokens->Mapper(
      fn(Column $column ) => $column->ToString()
    )->JoinWithComma();
  }

  private function GetFroms(
  ): string {
    if(isset($this->where) === false){
      return $this->table;
    }

    $compare = $this->where->getCompare();
    if( $compare->froms->Count() === 0 ){
      return $this->table;
    }

    return $compare->froms->JoinWithComma();
  }

  private function GetWheres(
  ): string {
    if(isset($this->where) === false){
      return "1=1";
    }

    $compare = $this->where->getCompare();
    if( $compare->conditions->Count() === 0 ){
      return "1=1";
    }

    return $compare->conditions->JoinNotSpace();
  }

  private function GetGroupBy(
  ): string {
    if(isset($this->groupBy) === false){
      return "";
    }

    if($this->groupBy->tokens->Count() === 0){
      return "";
    }

    return sprintf("Group By %s", $this->groupBy->tokens->Mapper(
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
    $orderByList = DataList::Create(
      array_merge(
        $this->GetOrderByList("orderByAsc", "Asc"),
        $this->GetOrderByList("orderByDesc", "Desc")
      )
    );

    return $orderByList->Count() !== 0
      ? sprintf( "Order By %s", $orderByList->JoinWithComma())
      : "Order By 1 Asc";
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