<?php

namespace Websyspro\DynamicSql\ByFns;

use ReflectionParameter;
use Websyspro\Commons\Collection;
use Websyspro\Commons\Reflect;
use Websyspro\DynamicSql\Interfaces\WhereProps;
use Websyspro\DynamicSql\Shareds\ItemCondition;
use Websyspro\DynamicSql\Shareds\ItemParameter;
use Websyspro\DynamicSql\Utils\ArrowFN;
use Websyspro\Entity\Core\StructureTableColumns;
use Websyspro\Entity\Shareds\ColumnType;

class WhereByFN extends ArrowFN
{
  public Collection $bodyStatics;
  public Collection $bodyParameters;
  public Collection $bodyScripts;

  public function init(
  ): void{
    $this->ParseFill();
    $this->ParseBodyStatics();
    $this->ParseBodyParameters();
    $this->ParseBodyConditions();
    $this->ParseBodyConditionsSplit();
    $this->ParseBodyAddSoftDeleteds();
    $this->ParseBodyEntityFields();
    $this->ParseBodyValuesFields();
    $this->ParseBodyValuesEncodes();
    $this->ParseBodyScripts();
    $this->ParseClear();
  }

  public function ParseBodyStatics(
  ): void {
    $this->bodyStatics = Collection::Create(
      $this->ParseBodyStaticsUnion(
        Reflect::FN(
          $this->bodyFN
        )->getStaticVariables()
      )
    );
  }

  public function ParseBodyStaticsUnion(
    array $array,
    string $prefix = ""
  ): array {
  $result = [];

  foreach ($array as $key => $value) {
    $newKey = $prefix === "" ? $key : "{$prefix}.{$key}";

    if (is_array($value) || is_object($value)) {
      if (is_array($value) && array_keys($value) === range(0, count($value) - 1)) {
        $result[$newKey] = json_encode($value);
      } else {
        $flattened = $this->ParseBodyStaticsUnion((array)$value, $newKey);
        foreach ($flattened as $fKey => $fValue) {
            $result[$fKey] = $fValue;
        }
      }
    } else {
      $result[$newKey] = $value;
    }
  }

  return $result;
  }  

  public function ParseBodyParameters(
  ): void {
    $this->bodyParameters = Collection::Create(
      Reflect::FN($this->bodyFN)->getParameters()
    );

    $this->bodyParameters->Mapper(
      fn(ReflectionParameter $rp) => (
        new ItemParameter(
          $rp->getType()->getName(), 
          $rp->getName()
        )
      )
    );
  }

  public function ParseBodyConditions(
  ): void {
    $this->body->Mapper(fn(string $body) => (
      preg_replace([
        "/(&&)|(and)/i",
        "/(\|\|)|(or)/i"
      ], ["And", "Or"], $body)
    ));

    $this->body->Mapper(fn(string $body) => (
      preg_replace([
        "/\s{1,}!=\s{1,}/",
        "/\s{1,}==\s{1,}/",
        "/\s{1,}=\s{1,}/" 
      ], [ " !== ", " === ", " === " ], $body)
    ));
  }

  public function ParseBodyConditionsSplit(
  ): void {
    $this->body = Collection::Create(
      preg_split("/(\s?And\s?)|(\s?Or\s?)/", $this->body->First(), -1, (
        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
      ))
    );
    
    $this->body->Mapper(fn(string $conditions, int $order) => (
      $order % 2 === 1 ? trim($conditions) : new ItemCondition($conditions)
    ));
  }

  public function ParseBodyAddSoftDeleteds(
  ): void {
    $this->bodyParameters->ForEach(
      function(ItemParameter $itemParameter){
        $structureTableColumns = $itemParameter->structureTable->Columns();
        if($structureTableColumns instanceof StructureTableColumns){
          $whereSoftDeleted = $structureTableColumns->ListType()->Where(
            fn(ColumnType $columnType) => in_array($columnType->name, ["Actived", "Deleted"])
          );

          $whereSoftDeleted->Mapper(fn(ColumnType $columnType) => (
            sprintf("\${$itemParameter->name}->{$columnType->name} === %s", (
              $columnType->name === "Actived" ? "true" : "false"
            ))
          ))->ForEach(fn(string $itemCondition) => (
            $this->body->Add("And")->Add(
              new ItemCondition(
                $itemCondition
              )
            )
          ));
        }
      }
    );
  }

  public function ParseBodyEntityFields(
  ): void {
    $this->bodyParameters->ForEach(
      function(ItemParameter $itemParameter){
        $this->body->ForEach(
          fn(ItemCondition | string $itemCondition) => (
            is_string($itemCondition) === false
              ? $itemCondition->entityField($itemParameter)
              : []
          )
        );
      }
    );
  }

  public function ParseBodyValuesFields(
  ): void {
    $this->body->ForEach(
      fn(ItemCondition | string $itemCondition) => (
        is_string($itemCondition) === false 
          ? $itemCondition->valuesFields($this->bodyStatics)
          : []
      )
    );
  }

  public function ParseBodyValuesEncodes(    
  ): void {
    $this->body->ForEach(
      function(ItemCondition | string $itemCondition){
        if($itemCondition instanceof ItemCondition){
          $itemCondition->valuesEncode();
        }
      }
    );
  }

  public function ParseBodyScripts(
  ): void {
    $this->body->ForEach(
      function(ItemCondition | string $itemCondition){
        if(isset($this->bodyScripts) === false){
          $this->bodyScripts = Collection::Create([]);
        }
        
        if($itemCondition instanceof ItemCondition){
          $this->bodyScripts->Add($itemCondition->compare());
        } else if(is_string($itemCondition)){
          $this->bodyScripts->Add($itemCondition);
        }
      }
    );
  }

  public function Where(
  ): WhereProps {
    return new WhereProps(
      $this->bodyParameters->Mapper(
        fn(ItemParameter $itemParameter) => (
          $itemParameter->structureTable->table
        )
      )->Where(
        fn(string $entity) => (
          $this->bodyScripts->Copy()->Where(
            fn(string $script) => (
              preg_match("/^$entity\./", $script) === 1
            )
          )->Count() !== 0
        )
      ), $this->bodyScripts
    );
  }

  public function ParseClear(
  ): void {
    unset($this->bodyFN);
  }  
}