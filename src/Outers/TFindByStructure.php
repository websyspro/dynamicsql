<?php

namespace Websyspro\DynamicSql;

use Websyspro\Commons\TList;

class TFindByStructure
{
  public string $condition;

  public function __construct(
    public TList $finds,
    public TList $params,
    public TList $statics,
    public string $body
  ){}

  public function getTables(
  ): array {
    return $this->params->All();
  }

  public function getConditions(
  ): string {
    return $this->condition;
  }
}