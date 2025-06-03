<?php

namespace Websyspro\DynamicSql\Shareds;

use Websyspro\Entity\Core\TStructureTable;
use Websyspro\Entity\Enums\TColumnType;
use Websyspro\Entity\Shareds\TProperties;

class TEqualField
{
  public string $table;
  public TColumnType $columnType;

  public function __construct(
    public TStructureTable $structureTable,
    public string $name
  ){
    $this->ParseTable();
    $this->ParseType();
    $this->ParseClear();
  }

  public function ParseTable(
  ): void {
    $this->structureTable;

    $this->table = (
      $this->structureTable->table
    );
  }

  public function ParseType(
  ): void {
    $this->columnType = $this->structureTable->Columns()->List()->Where(
      fn(TProperties $properties) => $properties->name === $this->name
    )->First()->items->First()->columnType;
  }

  public function ParseClear(
  ): void {
    unset($this->structureTable);
  }
}