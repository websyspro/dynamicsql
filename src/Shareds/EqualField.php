<?php

namespace Websyspro\DynamicSql\Shareds;

use Websyspro\Entity\Enums\ColumnType;
use Websyspro\Entity\Core\StructureTable;
use Websyspro\Entity\Interfaces\IProperties;

class EqualField
{
  public string $table;
  public ColumnType $columnType;

  public function __construct(
    public StructureTable $structureTable,
    public string $name
  ){
    $this->ParseTable();
    $this->ParseType();
    $this->ParseClear();
  }

  public function ParseTable(
  ): void {
    $this->table = (
      $this->structureTable->table
    );
  }

  public function ParseType(
  ): void {
    $this->columnType = $this->structureTable->Columns()->List()->Where(
      fn(IProperties $properties) => $properties->name === $this->name
    )->First()->items->First()->columnType;
  }

  public function ParseClear(
  ): void {
    unset($this->structureTable);
  }
}