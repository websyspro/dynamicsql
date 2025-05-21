<?php

namespace Websyspro\DynamicSql;

use ReflectionClass;
use ReflectionProperty;
use Websyspro\Commons\TList;
use Websyspro\Entity\Core\StructureTable;
use Websyspro\Entity\Shareds\Properties;

class TFindByFNParams
{
  public TList $propertys;
  public string $table;

  public function __construct(
    public string $entity,
    public string $name
  ){
    $this->setPropertys();
  }

  private function setPropertys(
  ): void {
    if(class_exists($this->entity) === false){
      $this->propertys = new TList();
    }

    $reflectionClass = (
      new ReflectionClass($this->entity)
    );

    $this->propertys = new TList(
      $reflectionClass->getProperties(
        ReflectionProperty::IS_PUBLIC
      )
    );

    $structureTable = (
      new StructureTable(
        $this->entity
      )
    );

    $columnsTypes = (
      $structureTable
    )->Columns()->List();

    $this->propertys->Mapper(
      fn(ReflectionProperty $rp) => (
        new TFindByFNParamsStructure(
          $columnsTypes->Copy()->Find(
            fn(Properties $properties) => (
              $properties->name === $rp->getName()
            )
          )->First()->items->First()->columnType,
          $rp->getName()
        )
      )
    );

    $this->table = $structureTable->table;
  }
}