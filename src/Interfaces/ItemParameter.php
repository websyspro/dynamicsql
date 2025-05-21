<?php

namespace Websyspro\DynamicSql\Interfaces;

use Websyspro\Commons\Collection;
use Websyspro\Entity\Core\StructureTable;

class ItemParameter
{
  public Collection $properts;
  public StructureTable $structureTable;

  public function __construct(
    public string $entity,
    public string $name
  ){
    $this->ParseParameters();
  }

  public function ParseParameters(
  ): void {
    $this->structureTable = (
      new StructureTable($this->entity)
    );
  }
}