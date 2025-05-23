<?php

namespace Websyspro\DynamicSql\Shareds;

use Websyspro\Commons\Collection;
use Websyspro\Commons\Util;
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
      $this->equalA = new ItemValue(
        $this->equalA, $statics
      );
    }

    if(is_string($this->equalB)){
      $this->equalB = new ItemValue(
        $this->equalB, $statics
      );
    }
  }

  public function compareFields(
  ): void {

  }

  public function valuesEncode(
  ): void {
    $this->valuesEncodeApply($this->equalA, $this->equalB);
    $this->valuesEncodeApply($this->equalB, $this->equalA);
  }

  public function valuesEncodeApply(
    string | ItemField | ItemValue $equalA,
    string | ItemField | ItemValue $equalB
  ): void {
    if($equalA instanceof ItemValue){
      if(preg_match("/(^\[)|(\]$)/", $equalA->valueParse)){
        $itemArr = Collection::Create(
          Util::SplitWithComma(
            preg_replace(
              "/(^\[)|(\]$)/", "", $equalA->valueParse
            )
          )
        )->Mapper(fn(string $item) => (
          $equalB->columnType->Encode(
            preg_replace( "/(^\")|(^\')|(\"$)|(\'$)/", "", trim($item))
          )
        ));

        $equalA->valueParse = Util::JoinWithComma(
          $itemArr->All(), "(%s)"
        );
      } else {
        $equalA->valueParse = (
          $equalB->columnType->Encode(
            $equalA->valueParse
          )
        );
      }
    }    
  }

  public function compareEqualsApply(
    string | ItemField | ItemValue $equal,
  ): string {
    if($equal instanceof ItemField){
      return "{$equal->table}.{$equal->name}";
    } else if($equal instanceof ItemValue) {
      return "{$equal->valueParse}";
    }
    
    return "";
  }

  public function compareEqualsFieldLiked(
    string | ItemField | ItemValue $equals,
    string $equalsLike
  ): string {
    if($equals instanceof ItemValue){
      if(preg_match("/%/", $equals->valueParse)){
        $equalsLike = preg_replace(
          [ "/!=/", "/=/" ],
          [ "Not Like", "Like" ], $equalsLike
        );
      }
    }

    return $equalsLike;
  }

  public function compareEqualsFieldListed(
    string | ItemField | ItemValue $equals,
    string $equalsLike
  ): string {
    if($equals instanceof ItemValue){
      if(preg_match("/^\(.*\)$/", $equals->valueParse)){
        $equalsLike = preg_replace(
          ["/!=/", "/=/"], ["Not In", "In"], $equalsLike
        );
      }
    }

    return $equalsLike;
  }  

  public function compareEqualsField(
  ): string {
    $equals = preg_replace(
      ["/!==/", "/===/"], ["!=", "="], $this->equals
    );

    $equals = $this->compareEqualsFieldLiked($this->equalA, $equals);
    $equals = $this->compareEqualsFieldLiked($this->equalB, $equals);
    $equals = $this->compareEqualsFieldListed($this->equalA, $equals);
    $equals = $this->compareEqualsFieldListed($this->equalB, $equals);

    return $equals;
  }

  public function compareEquals(
  ): string {
    $equalA = $this->compareEqualsApply($this->equalA);
    $equals = $this->compareEqualsField($this->equals);
    $equalB = $this->compareEqualsApply($this->equalB);
    
    return Collection::Create([
      $equalA, $equals, $equalB 
    ])->JoinWithSpace();
  }

  public function compare(
  ): string {
    return $this->compareEquals();
  }

  public function Clear(
  ): void {
    unset($this->equalstr);
  }
}