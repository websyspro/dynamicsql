<?php

namespace Websyspro\DynamicSql;

use Websyspro\Commons\TList;
use Websyspro\Commons\TUtil;
use Websyspro\Entity\Enums\ColumnType;

class TFindByFNCondition
{
  public string $entity;
  public string $column;
  public string $compare;
  public string $value;

  public function __construct(
    private string $condition
  ){
    $this->parse();
    $this->parseColumn();
    $this->parseClear();
  }

  private function parse(
  ): void {
    $condition = new TList(
      preg_split("/\s{1,}(!==|===|>|>=|<|<=)\s{1,}/", $this->condition, -1, (
        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
      ))
    );

    [$this->column, $this->compare, $this->value] = $condition->Mapper(
      fn(string $item) => preg_replace("/(^\"|^\')|(\"$|\'$)/", "", trim($item))
    )->All();
  }

  private function parseColumn(
  ): void {
    [ $this->entity, $this->column ] = TUtil::SplitWithPoint(
      preg_replace(["/^\\$/", "/\->/"], ["", "."], $this->column)
    ); 
  }

  private function parseClear(
  ): void {
    unset( $this->condition );
  }

  public function setEntity(
    string $entity
  ): self {
    $this->entity = $entity;
    return $this;
  }

  public function setValueToDatetime(
    string $value
  ): string {
    if(strlen($value) === 19){
      if(preg_match("/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}:\d{2}:\d{2})/", $value)){
        $value = preg_replace("/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}:\d{2}:\d{2})/", "$3-$2-$1 $4", $value);
      }
    } else 
    if(strlen($value) === 10){
      if(preg_match("/(\d{2})\/(\d{2})\/(\d{4})/", $value)){
        $value = preg_replace("/(\d{2})\/(\d{2})\/(\d{4})/", "$3-$2-$1", $value);
      }      
    }

    return "'{$value}'";
  }

  public function setValueToDecimal(
    string $value
  ): float {
    return (float)$value;
  }

  public function setValueToFlag(
    string $value
  ): int {
    if( is_bool($value)){
      return $value ? 1 : 0;
    } else
    if( is_string($value)){
      return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    return $value;
  }
  
  public function setValueToText(
    string $value
  ): string {
    return "'{$value}'";
  }
  
  public function valueIsList(
  ): bool {
    return (bool)preg_match("/^\[/", $this->value) 
        && (bool)preg_match("/(\]$)/", $this->value);
  }

  public function setValue(
    TFindByFNParamsStructure $ps
  ): self {
    if(preg_match("/^\\$/", $this->value) === 1){
      return $this;
    }

    if(mb_strtolower(trim($this->value)) === "null"){
      $this->value = "NULL";
      $this->compare = (
        $this->compare === "!==" || $this->compare === "!="
          ? "Not" : "Is"
      );

      return $this;
    }

    if($this->valueIsList()){
      $valueParse = new TList(
        TUtil::SplitWithComma(
          preg_replace( ["/(^\[)|(\]$)/", "/,\s*/" ], [ "", "," ], $this->value)
        )
      );

      $this->value = TUtil::JoinWithComma(
        $valueParse->Mapper(fn(string $value) => (
          $ps->columnType->Encode($value)
        ))->All(), "(%s)"
      );

      $this->compare = (
        $this->compare === "!==" || $this->compare === "!="
          ? "Not In" : "In"
      );
    } else {
      $this->value = $ps->columnType->Encode($this->value);
    }

    return $this;
  }

  public function getEntityByValue(
  ): string | null {
    [ $entity ] = TUtil::SplitWithPoint($this->value);
    return $entity;
  } 

  public function setEntityByValue(
    TFindByFNParams $params
  ): self {
    [$entity, $column] = TUtil::SplitWithPoint($this->value);

    if($entity === $params->name){
      $hasProperts = $params->propertys->Copy()->Find(
        fn(TFindByFNParamsStructure $fps) => $fps->column === $column 
      );

      if($hasProperts->Count() !== 0){
        $this->value = "{$params->table}.{$column}";
      }
    }

    return $this;
  }

  public function setCompare(
    TList $params
  ): self {
    $param = $params->Find(
      fn(TFindByFNParams $fp) => (
        $fp->entity === $this->entity
      )
    );

    if($param->Count() !== 0){
      if($param->First() instanceof TFindByFNParams){
        $paramStructure = $param->First()->propertys->Copy()->Find(
          fn(TFindByFNParamsStructure $fps) => (
            $fps->column === $this->column
          ) 
        );

        if($paramStructure->Count() !== 0){
          if($paramStructure->First()->columnType === ColumnType::Text){
            if(preg_match("/%/", $this->value) === 1){
              $this->compare = preg_replace(
                [ "/(!==)/", "/(===)/" ],
                [ "Not Like", "Like" ], $this->compare
              );
            }
          }
        }
      }
    }

    $this->compare = preg_replace(
      [ "/(!==)/", "/(===)/" ],
      [ "!=", "=" ], $this->compare
    );

    return $this;
  }

  public function getStaticParse(
    array $array,
    string $prefix = ""
  ): array {
    $result = [];

    foreach ($array as $key => $value) {
        $newKey = $prefix === "" ? $key : "{$prefix}.{$key}";

        if (is_array($value) || is_object($value)) {
            $flattened = $this->getStaticParse($value, $newKey);
            foreach ($flattened as $fKey => $fValue) {
                $result[$fKey] = $fValue;
            }
        } else {
            $result[$newKey] = $value;
        }
    }

    return $result;

    return $staticsNew;
  }

  public function setStatic(
    TList $statics
  ): self {
    if(preg_match('/\{\$(.*?)\}/', $this->value)){
      $this->value = preg_replace(["/\{\\$/", "/\}/"], ["$", ""], $this->value);
    }

    if(preg_match("/\\$/", $this->value) === 0){
      return $this;
    }

    $this->value = preg_replace(
      ["/(^\\$)|(\"\])/", 
       "/(\[\")|(\->)/",
       "/\\$/"], ["", ".", ""], $this->value
    );

    $statics = new TList(
      $this->getStaticParse(
        $statics->All()
      )
    );

    $statics->ForEach(
      fn(string $value, string $key) => (
        $this->value = preg_replace(
          "/$key/", $value, $this->value
        )
      )
    );

    return $this;
  }

  public function get(
  ): string {
    return "{$this->entity}.{$this->column} {$this->compare} {$this->value}";
  }
}