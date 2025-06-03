<?php

namespace Websyspro\DynamicSql\Shareds;

use Websyspro\Commons\TList;
use Websyspro\Entity\Core\TStructureTable;

class TItemParameter
{
  public TList $properts;
  public TStructureTable $structureTable;

  public function __construct(
    public string $entity,
    public string $name
  ){
    $this->ParseParameters();
  }

  public function ParseParameters(
  ): void {
    $this->structureTable = (
      new TStructureTable($this->entity)
    );
  }
}