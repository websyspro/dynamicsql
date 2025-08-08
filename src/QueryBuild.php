<?php

namespace Websyspro\DynamicSql;

use Websyspro\Commons\DataList;
use Websyspro\DynamicSql\Core\GroupByFn;
use Websyspro\DynamicSql\Core\OrderByAscByFn;
use Websyspro\DynamicSql\Core\OrderByDescByFn;
use Websyspro\DynamicSql\Core\SelectByFn;
use Websyspro\DynamicSql\Core\WhereByFn;
use Websyspro\DynamicSql\Enums\EDriverType;
use Websyspro\DynamicSql\Enums\EOrderByPriorityType;
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
  public EDriverType $driverType;
  public int $limit;
  public int $offSet;

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
    return isset($this->select) === true 
        && $this->select->tokens->count() !== 0;
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
    return isset($this->where) === true 
        && $this->where->tokens->count() !== 0;
  }  

  public function groupBy(
    callable $fn
  ): QueryBuild {
    return $this->setProps(
      "groupBy", GroupByFn::create($fn)
    );
  }

  public function hasGroupBy(
  ): bool {
    return isset($this->groupBy) === true 
        && $this->groupBy->tokens->count() !== 0;
  }

  public function orderByAsc(
    callable $fn
  ): QueryBuild {
    return $this->setProps(
      "orderByAsc", OrderByAscByFn::create($fn)
    );
  }

  public function hasOrderByAsc(
  ): bool {
    return isset($this->orderByAsc) === true 
        && $this->orderByAsc->tokens->count() !== 0;
  }  

  public function orderByDesc(
    callable $fn
  ): QueryBuild {
    return $this->setProps(
      "orderByDesc", OrderByDescByFn::create($fn)
    );
  }

  public function hasOrderByDesc(
  ): bool {
    return isset($this->orderByDesc) === true 
        && $this->orderByDesc->tokens->count() !== 0;
  }

  public function paged(
    int $limit,
    int $offSet
  ): QueryBuild {
    $limit = bcmul(
      $offSet, bcsub(
        $limit, 1, 0
      ), 0
    );

    $this->setProps("limit", $limit);
    $this->setProps("offSet", $offSet);
    return $this;
  }

  private function hasPagination(
  ): bool {
    return isset($this->limit) === true 
        && isset($this->offSet) === true;
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
    if($this->hasSelect() === false){
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

    return preg_replace(
      "#And 1 = 1#", "", $this->where->getCompare()->conditionsPrimary->first()
    );
  }

  private function getPagination(
  ): string {
    if($this->hasPagination() === false){
      return "";
    }

    if($this->driverType === EDriverType::mysql){
      return "Limit {$this->limit}, {$this->offSet}";
    } else
    if($this->driverType === EDriverType::postgress){
      return "Limit {$this->limit} OffSet {$this->offSet}";
    } else
    if($this->driverType === EDriverType::sqlserver){
      return "OffSet {$this->limit} Rows Fetch Next {$this->offSet} Rows Only";
    }

    return "";
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

    return $this->where->getCompare()->conditionsSecundary->first();
  }

  private function getGroupBy(
  ): string {
    if($this->hasGroupBy() === false){
      return "";
    }

    $groupBys = $this->groupBy->tokens->mapper(
      fn(Column $column ) => $column->getOrderByString()
    )->joinWithComma();

    return "Group By {$groupBys}";
  }

  private function hasOrderByList(
    string $oderbyProps
  ): bool {
    return isset($this->{$oderbyProps}) === true 
        && $this->{$oderbyProps}->tokens->count() !== 0;
  }

  private function getOrderByList(
    string $oderbyProps,
    string $oderbyType,
    EOrderByPriorityType $orderByPriorityType
  ): array {
    if($this->hasOrderByList($oderbyProps) === false){
      return [];
    }

    if($this->where->getParameters()->exist() === true){
      if($this->where->getParameters()->first() instanceof ItemParameter){
        if($orderByPriorityType === EOrderByPriorityType::Primary){
          $orderBys = $this->{$oderbyProps}->tokens->copy()->where(fn(Column $column) => (
            $column->table === $this->where->getParameters()->first()->structureTable->table
          ));
        } else
        if($orderByPriorityType === EOrderByPriorityType::Secundary){
          $orderBys = $this->{$oderbyProps}->tokens->copy()->where(fn(Column $column) => (
            $column->table !== $this->where->getParameters()->first()->structureTable->table
          ));
        }
      }
    }

    return $orderBys->mapper(
      fn(Column $column) => (
        "{$column->getOrderByString()} {$oderbyType}"
      )
    )->all();
  }

  private function getOrderBy(
    EOrderByPriorityType $orderByPriorityType
  ): string {
    $orderByList = DataList::create(
      array_merge(
        $this->getOrderByList("orderByAsc", "Asc", $orderByPriorityType),
        $this->getOrderByList("orderByDesc", "Desc", $orderByPriorityType)
      )
    );

    return $orderByList->count() !== 0
      ? "Order By {$orderByList->joinWithComma()}"
      : "Order By 1 Asc";
  }

  private function getOrderByPrimary(
  ): string {
    return $this->getOrderBy(EOrderByPriorityType::Primary);
  }

  private function getOrderBySecundary(
  ): string {
    return $this->getOrderBy(EOrderByPriorityType::Secundary);
  }

  private function getSqlBase(
  ): string {
    return (
      "(Select * 
          From {$this->getTable()}
         Where {$this->getWherePrimary()} 
               {$this->getGroupBy()}
               {$this->getOrderByPrimary()}
               {$this->getPagination()}
          ) As {$this->getTable()}"
    );
  }  

  public function get(
    EDriverType|null $driverType = null
  ): string {
    if($driverType !== null){
      $this->driverType = $driverType;
    }

    return (
      "Select {$this->getColumns()} 
         From {$this->getSqlBase()} 
              {$this->getLeftJoins()} 
        Where {$this->getWheresSecundary()}
              {$this->getOrderBySecundary()}"
    );
  }
  
  public static function create(
    string $class
  ): QueryBuild {
    return new static($class);
  }
}