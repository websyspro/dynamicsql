<?php

namespace Websyspro\DynamicSql;

class TFindByFNParamsStructure
{
  public function __construct(
    public string $type,
    public string $column
  ){
    $this->type = mb_strtolower(
      $this->type
    );
  }
}