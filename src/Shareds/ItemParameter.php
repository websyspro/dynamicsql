<?php

namespace Websyspro\DynamicSql\Shareds;

use Websyspro\Commons\DataList;
use Websyspro\Entity\Core\StructureTable;

class ItemParameter
{
  public DataList $properts;
  public StructureTable $structureTable;

  public function __construct(
    public string $entity,
    public string $name
  ){
    $this->parseParameters();
  }

  public function parseParameters(
  ): void {
    $this->structureTable = (
      new StructureTable($this->entity)
    );
  }
}