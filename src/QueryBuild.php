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

  private function setProps(
    string $name,
    mixed $prop
  ): QueryBuild {
    $this->{$name} = $prop;
    return $this;
  }

  public function select(
    callable $fn
  ): QueryBuild {
    return $this->setProps(
      "select", SelectByFn::create($fn)
    );
  }

  public function where(
    callable $fn
  ): QueryBuild {
    return $this->setProps(
      "where", WhereByFn::create($fn)
    );
  }

  public function groupBy(
    callable $fn
  ): QueryBuild {
    return $this->setProps(
      "groupBy", GroupByFn::create($fn)
    );
  }

  public function orderByAsc(
    callable $fn
  ): QueryBuild {
    return $this->setProps(
      "orderByAsc", OrderByAscByFn::create($fn)
    );
  }

  public function orderByDesc(
    callable $fn
  ): QueryBuild {
    return $this->setProps(
      "orderByDesc", OrderByDescByFn::create($fn)
    );
  }

  private function getColumns(
  ): string {
    if(isset($this->select) === false){
      return "*";
    }

    if($this->select->tokens->count() === 0){
      return "*";  
    }

    return $this->select->tokens->mapper(
      fn(Column $column ) => $column->toString()
    )->JoinWithComma();
  }

  private function getFroms(
  ): string {
    if(isset($this->where) === false){
      return $this->table;
    }

    $compare = $this->where->getCompare();
    if( $compare->froms->count() === 0 ){
      return $this->table;
    }

    return $compare->froms->joinWithComma();
  }

  private function getWheres(
  ): string {
    if(isset($this->where) === false){
      return "1=1";
    }

    $compare = $this->where->getCompare();
    if( $compare->conditions->count() === 0 ){
      return "1=1";
    }

    return $compare->conditions->joinNotSpace();
  }

  private function getGroupBy(
  ): string {
    if(isset($this->groupBy) === false){
      return "";
    }

    if($this->groupBy->tokens->Count() === 0){
      return "";
    }

    return sprintf("Group By %s", $this->groupBy->tokens->mapper(
      fn(Column $column ) => $column->toString()
    )->joinWithComma());
  }

  private function getOrderByList(
    string $oderbyProps,
    string $oderbyType
  ): array {
    if( isset( $this->{$oderbyProps}) === false ){
      return [];
    }

    if( $this->{$oderbyProps}->tokens->count() === 0 ){
      return [];
    }

    return $this->{$oderbyProps}->tokens->mapper(
      fn(Column $column) => sprintf( "%s {$oderbyType}", $column->toString())
    )->all();
  }

  private function getOrderBy(
  ): string {
    $orderByList = DataList::create(
      array_merge(
        $this->getOrderByList("orderByAsc", "Asc"),
        $this->getOrderByList("orderByDesc", "Desc")
      )
    );

    return $orderByList->count() !== 0
      ? sprintf( "Order By %s", $orderByList->joinWithComma())
      : "Order By 1 Asc";
  }

  public function get(
  ): string {
    return sprintf(
      "Select %s From %s Where %s %s %s", ...[
        $this->getColumns(),
        $this->getFroms(),
        $this->getWheres(),
        $this->getGroupBy(),
        $this->getOrderBy()
      ]
    );
  }
  
  public static function create(
    string $class
  ): QueryBuild {
    return new static($class);
  }
}