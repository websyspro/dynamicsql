<?php

namespace Websyspro\DynamicSql;

use Websyspro\Entity\Enums\ColumnType;

class TFindByFNParamsStructure
{
  public function __construct(
    public ColumnType $columnType,
    public string $column
  ){}
}