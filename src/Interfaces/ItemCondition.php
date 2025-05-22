<?php

namespace Websyspro\DynamicSql\Interfaces;

use Stringable;
use Websyspro\Commons\Collection;
use Websyspro\Entity\Shareds\ColumnType;

class ItemCondition
{
  public string | ItemField | ItemValue $equalA;
  public string | ItemField | ItemValue $equals;
  public string | ItemField | ItemValue $equalB;
  
  public function __construct(
    private string $equalstr
  ){
    $this->ParseSplit();
    $this->Clear();
  }

  public function ParseSplit(
  ): void {
    [ $this->equalA, $this->equals, $this->equalB ] = (
      Collection::Create(
        preg_split("/(!==|===|>=|<=)/", $this->equalstr, -1, (
          PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        ))
      )
    )->Mapper(fn(string $equals) => trim($equals))->All();
  }

  public function hasEntity(
    string $parameterName
  ): bool {
    if( is_string($this->equalA) === true ){
      return preg_match("/^\\$$parameterName->/", $this->equalA) === 1;
    }

    if( is_string($this->equalB) === true ){
      return preg_match("/^\\$$parameterName->/", $this->equalB) === 1;
    }

    return false;
  }

  public function entityField(
    ItemParameter $itemParameter
  ): void {
    if($this->hasEntity($itemParameter->name)){
      $itemParameter->structureTable->Columns()->ListType()->ForEach(
        fn(ColumnType $columnType) => (
          Collection::Create([ "equalA", "equalB" ])->Mapper(
            fn(string $equalKey) => $this->isEntityField(
              $itemParameter, $columnType->name, $equalKey
            )
          )
        )
      );
    }
  }

  public function isEntityField(
    ItemParameter $itemParameter,
    string $columnName,
    string $equalKey
  ){
    if(is_string($this->{$equalKey}) === true){
      if(preg_match("/^\\$$itemParameter->name->$columnName$/", $this->{$equalKey}) === 1){
        $this->{$equalKey} = new ItemField(
          $itemParameter->structureTable, $columnName
        );
      }
    }
  }

  public function valuesFields(
    Collection $statics
  ): void {
    if(is_string($this->equalA)){
      $this->equalA = new ItemValue($this->equalA, $statics);
    }


    if(is_string($this->equalB)){
      $this->equalB = new ItemValue($this->equalB, $statics);
    }
  }

  public function Clear(
  ): void {
    unset($this->equalstr);
  }
}