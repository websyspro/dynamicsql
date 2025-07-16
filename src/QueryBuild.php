<?php

namespace Websyspro\DynamicSql;

use Websyspro\Commons\DataList;
use Websyspro\DynamicSql\Core\GroupByFn;
use Websyspro\DynamicSql\Core\OrderByAscByFn;
use Websyspro\DynamicSql\Core\OrderByDescByFn;
use Websyspro\DynamicSql\Core\SelectByFn;
use Websyspro\DynamicSql\Core\WhereByFn;
use Websyspro\DynamicSql\Interfaces\ICompare;
use Websyspro\DynamicSql\Shareds\Column;
use Websyspro\DynamicSql\Shareds\ItemParameter;
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

  private function getTable(
  ): string {
    return $this->table;
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

  public function hasSelect(
  ): bool {
    return isset($this->select);
  }

  public function where(
    callable $fn
  ): QueryBuild {
    return $this->setProps(
      "where", WhereByFn::create($fn)
    );
  }

  public function hasWhere(
  ): bool {
    return isset($this->where);
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

  private function getColumnsFromWhere(
  ): string {
    if($this->hasWhere() === false){
      return "*";
    }
    
    $columns = $this->where->getParameters()->copy()->mapper(
      fn(ItemParameter $ip) => $ip->structureTable->columns()->listNames()->mapper(
        fn(string $column) => sprintf( "%s.%s As %s_%s", ...[
          $ip->structureTable->table, $column, $ip->name, $column
        ])
      )->joinWithComma()
    );

    return $columns->joinWithComma();
  }

  private function getColumns(
  ): string {
    if(isset($this->select) === false){
      return $this->getColumnsFromWhere();
    }

    if($this->select->tokens->count() === 0){
      return $this->getColumnsFromWhere();
    }

    return $this->select->tokens->copy()->mapper(
      fn(Column $column) => $column->toString(
         $this->select->getParameters()
      )
    )->JoinWithComma();
  }

  private function getWherePrimary(
  ): string {
    if($this->hasWhere() === false){
      return "Where 1=1";
    }

    return $this->where->getCompare()->conditionsPrimary->first();
  }

  private function getPaginator(
  ): string {
    return "Limit 0, 12";
  }

  private function getSqlBase(
  ): string {
    return sprintf( 
      "(Select * From %s Where %s %s %s %s) As %s", ...[
        //$this->getColumns(),
        $this->getTable(),
        $this->getWherePrimary(),
        $this->getGroupBy(),
        $this->getOrderBy(),
        $this->getPaginator(),
        $this->getTable()
      ]
    );
  }

  private function getLeftJoins(
  ): string {
    if($this->hasWhere() === false){
      return "";
    }

    return $this->where->getCompare()->leftJoins->joinWithSpace();
  }

  private function getWheresSecundary(
  ): string {
    if($this->hasWhere() === false){
      return "1=1";
    }

    if($this->where->getCompare()->conditionsSecundary->count() === 0 ){
      return "1=1";
    }

    return $this->where->getCompare()->conditionsSecundary->first();
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
      fn(Column $column ) => $column->getOrderByString()
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
      fn(Column $column) => sprintf( "%s {$oderbyType}", $column->getOrderByString())
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
      "Select %s From %s %s Where %s %s", ...[
        $this->getColumns(),
        $this->getSqlBase(),
        $this->getLeftJoins(),
        $this->getWheresSecundary()
      ]
    );
  }
  
  public static function create(
    string $class
  ): QueryBuild {
    return new static($class);
  }
}