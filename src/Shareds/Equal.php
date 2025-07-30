<?php

namespace Websyspro\DynamicSql\Shareds;

use UnitEnum;
use Websyspro\Commons\DataList;
use Websyspro\Entity\Enums\ColumnType;
use Websyspro\DynamicSql\Commons\Util;
use Websyspro\DynamicSql\Enums\EqualType;
use Websyspro\DynamicSql\Interfaces\IEqualUnitEnum;
use Websyspro\Entity\Core\Shareds\ForeignKeyItem;
use Websyspro\Entity\Interfaces\IColumnType;

class Equal
{
  public DataList $equals;
  public EqualType $equalType;
  public string $leftJoin;
  public bool $isLeftJoin;
  
  public function __construct(
    public string $value,
    public DataList $parameters,
    public DataList $statics,
    public bool $isParse,
  ){
    $this->define();
    $this->defineEqualType();
    $this->defineEntity();
    $this->defineStatic();
    $this->defineEnum();
    $this->defineEvaluate();
    $this->defineNulls();
    $this->defineParse();
    $this->defineOneToOnes();
    $this->defineClear();
  }

  private function define(
  ): void {
    $this->isLeftJoin = false;
    $this->equals = DataList::create(
      preg_split("/(!=|==|>=|<=|<>)/", $this->value, 2, (
        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
      ))
    );
    
    $this->equals->mapper(
      fn(string $equal) => trim($equal)
    );
  }

  private function defineEqualType(
  ): void {
    if( preg_match("/(^And$)|(^Or$)/", $this->value )){
      $this->equalType = EqualType::Logical;
    } else
    if( preg_match("/(^\\($)/", $this->value )){
      $this->equalType = EqualType::StartGroup;
    } else
    if( preg_match("/(^\\)$)/", $this->value )){
      $this->equalType = EqualType::EndGroup;
    } else
    if( preg_match("/(!=|==|>=|<=|<>)/", $this->value )){
      $this->equalType = EqualType::Equal;
    }
  }

  public function defineEntityParse(
    EqualField|EqualVar|IEqualUnitEnum|string $equal,
    ItemParameter $itemParameter
  ): EqualField|EqualVar|IEqualUnitEnum|string {
    $column = $itemParameter->structureTable->columns()->listType()->where(
      fn(IColumnType $columnType) => (
        "\${$itemParameter->name}->{$columnType->name}" === $equal
      )
    );

    if( $column->count() ){
      return new EqualField(
        $itemParameter->structureTable, $column->first()->name
      );
    }  

    return $equal;    
  }

  public function defineEntity(
  ): void {
    if($this->equalType === EqualType::Equal){
      $this->parameters->forEach(
        fn(ItemParameter $itemParameter) => (
          $this->equals->mapper(
            fn(EqualField|EqualVar|IEqualUnitEnum|string $equal) => (
              $this->defineEntityParse($equal, $itemParameter)
            )
          )
        )
      );
    }
  }

  public function defineStaticParse(
    EqualField|EqualVar|IEqualUnitEnum|string $equal
  ): EqualField|EqualVar|IEqualUnitEnum|string {
    if( $equal instanceof EqualField ){
      return $equal;
    }

    if(preg_match("/\\$/", $equal) === 1){
      if(preg_match( "/(!=|==|>=|<=|<>)/i", $equal) === 0){
        $hasStatic = $this->statics->Copy()->whereByKey(
          fn(string $key) => preg_match("/(\{\\$$key\})|(\\$$key)/", $equal) === 1
        );

        if($hasStatic->exist()){
          $hasStatic->mapper(
            function(mixed $txt, string $key) use($equal) {
              return new EqualVar(
                $this->statics, preg_replace(
                  "/(\{\\$$key\})|(\\$$key)/", strtr($txt, ['$' => '\\$']), $equal
                )
              );
            }
          );

          return $hasStatic->first();          
        }
      }

      return $equal;
    }

    return new EqualVar(
      $this->statics, $equal
    );
  }  

  public function defineStatic(
  ): void {
    if($this->equalType === EqualType::Equal){
      $this->equals->mapper(fn(EqualField|EqualVar|IEqualUnitEnum|string $equal) => (
        $this->defineStaticParse($equal)
      ));
    }
  }

  public function defineEnumParse(
    EqualField|EqualVar|IEqualUnitEnum|string $equal
  ): EqualField|EqualVar|IEqualUnitEnum|string  {
    if($equal instanceof EqualVar === false){
      return $equal;
    }

    $hasConstante = preg_match(
      "/^[A-Za-z_][A-Za-z0-9_]*::[A-Za-z_][A-Za-z0-9_]*(->(value|name))?$/", $equal->value
    );

    if($hasConstante === 0){
      return $equal; 
    }

    $hasConstanteValue = preg_match("/->value$/", $equal->value);
    $hasConstanteName = preg_match("/->name$/", $equal->value);

    $constantUnitEnum = (
      constant(
        preg_replace(
          "/->(value|name)*$/", "", $equal->value
        )
      )
    );

    if($hasConstanteValue === 1){
      if($constantUnitEnum instanceof UnitEnum){
        return new IEqualUnitEnum(
          $constantUnitEnum->value
        );
      }
    } else
    if($hasConstanteName === 1){
      if($constantUnitEnum instanceof UnitEnum){
        return new IEqualUnitEnum(
          $constantUnitEnum->name
        );
      }      
    } else 
    if($hasConstanteValue === 0 && $hasConstanteName === 0){
      return new IEqualUnitEnum(
        $constantUnitEnum->value
      );
    }

    return $equal;
  }  

  public function defineEnum(
  ): void {
    if($this->equalType === EqualType::Equal){
      $this->equals->mapper(
        fn(EqualField|EqualVar|IEqualUnitEnum|string $equal) => (
          $this->defineEnumParse($equal)
        )
      );      
    }
  }

  public function defineEvaluate(
  ): void {
    if($this->equalType === EqualType::Equal){
      $this->equals->forEach(
        function(EqualField|EqualVar|IEqualUnitEnum|string $equal){
          if($equal instanceof EqualVar){
            $equal->value = EvaluateFromString::execute($equal->value);
          }
        }
      );
    }
  }

  public function defineNulls(
  ): void {
    if($this->equalType === EqualType::Equal){
      $this->equals->forEach(
        function(EqualField|EqualVar|IEqualUnitEnum|string $equal){
          if($equal instanceof EqualVar){
            if(preg_match( "/null/i", $equal->value)){
              $equal->value = strtoupper(
                $equal->value
              );
            }
          }
        }
      );
    }    
  }

  private function hasField(
    EqualField|EqualVar|IEqualUnitEnum|string $equal
  ): bool {
    return $equal instanceof EqualField;
  }

  private function hasParsed(
    EqualField|EqualVar|IEqualUnitEnum|string $equal
  ): bool {
    return $equal instanceof EqualVar
        || $equal instanceof IEqualUnitEnum;
  }
  
  private function defineParseValues(
    ColumnType $columnType,
    string $value
  ): string {
    $hasArray = preg_match(
      "/^\[\s*(.*?)\s*\]/", $value
    ) === 1;

    if( $hasArray ){
      $value = (
        sprintf("(%s)", (
          Util::strToArray($value)->mapper(
            fn(string $str) => (
              $columnType->encode(
                $str
              )
            )
          )->joinWithComma()
        ))
      );
    } else {
      $value = (
        $columnType->encode(
          preg_replace(
            "/(^\")|(\"$)|(^')|('$)/", "", (
              $value
            )
          )
        )
      );
    }

    return $value;
  }

  public function defineParse(
  ): void {
    if($this->isParse === true){
      if( $this->equalType === EqualType::Equal ){
        [ $equalA, $_, $equalC ] = (
          $this->equals->all()
        ); 

        if($this->hasField($equalA)){
          if($this->hasParsed($equalC)){
            $equalC->value = $this->defineParseValues(
              $equalA->columnType, $equalC->value
            );
          }
        }

        if($this->hasField($equalC)){
          if($this->hasParsed($equalA)){
            $equalA->value = $this->defineParseValues(
              $equalC->columnType, $equalA->value
            );
          }
        }
      } 
    }
  }

  private function getCompareIsField(
    EqualField $equal,
    ColumnType|null $columnType,
    string|null $value
  ): string {
    if($value === null || $columnType !== ColumnType::datetime){
      return "{$equal->table}.{$equal->name}";
    }

    if(strlen($value) === 12){
      return "Date({$equal->table}.{$equal->name})";
    }

    return "{$equal->table}.{$equal->name}";
  }

  private function getCompareToCompare(
    EqualField|EqualVar|IEqualUnitEnum|string|null $equal,
    EqualField|EqualVar|IEqualUnitEnum|string $compare
  ): string {
    if( is_null( $equal ) === false ){
      if( preg_match("/(^NULL$)|(^\((.*?)\s*\)$)|(%)/", $equal->value )){
        if( preg_match( "/^NULL$/", $equal->value )){
          return $compare->value === "==" ? "Is" : "Not";
        }

        if( preg_match( "/^\((.*?)\s*\)$/", $equal->value )){
          return $compare->value === "==" ? "In" : "Not In";
        }

        if( preg_match( "/%/", $equal->value )){
          return $compare->value === "==" ? "Like" : "Not Like";
        }
      }
    }

    return preg_replace([ "/==/" ], [ "=" ], $compare->value);
  }

  public function getCompare(
    string|null $tableBase = null
  ): string|null {
    if($tableBase !== null){
      if($this->equalType !== EqualType::Equal){
        return $this->equals->First(); 
      }

      if($this->isLeftJoin === true){
        return "1 = 1";
      }

      [ $equalA, $equalB, $equalC ] = (
        $this->equals->all()
      );      

      $hasCompareBaseA = $this->hasField($equalA) === true && $this->hasParsed($equalC) === true;
      $hasCompareBaseC = $this->hasField($equalC) === true && $this->hasParsed($equalA) === true;

      if($hasCompareBaseA === true || $hasCompareBaseC === true){
        if($this->hasField($equalA)){
          if($this->hasParsed($equalC)){
            return sprintf("%s %s %s", 
              $this->getCompareIsField(
                $equalA, $equalA->columnType, $equalC->value
              ), 
              $this->getCompareToCompare(
                $equalC, $equalB
              ), $equalC->value
            );  
          }
        }

        if($this->hasField($equalC)){
          if($this->hasParsed($equalA)){
            return sprintf("%s %s %s", 
              $this->getCompareIsField(
                $equalC, $equalC->columnType, $equalA->value
              ), 
              $this->getCompareToCompare(
                $equalA, $equalB
              ), $equalA->value
            );       
          }
        }
      }

      return null;
    } else {
      if($this->equalType !== EqualType::Equal){
        return $this->equals->First(); 
      } else {
        [ $equalA, $equalB, $equalC ] = (
          $this->equals->all()
        ); 

        if($this->hasField($equalA)){
          if($this->hasParsed($equalC)){
            return sprintf("%s %s %s", 
              $this->getCompareIsField(
                $equalA, $equalA->columnType, $equalC->value
              ), 
              $this->getCompareToCompare(
                $equalC, $equalB
              ), $equalC->value
            );
          }
        }

        if($this->hasField($equalC)){
          if($this->hasParsed($equalA)){
            return sprintf("%s %s %s", 
              $this->getCompareIsField(
                $equalC, $equalC->columnType, $equalA->value
              ), 
              $this->getCompareToCompare(
                $equalA, $equalB
              ), $equalA->value
            );       
          }
        }

        if($this->hasField($equalA)){
          if($this->hasField($equalC)){
            return sprintf("%s %s %s", 
              $this->getCompareIsField(
                $equalA, null, null
              ), 
              $this->getCompareToCompare(
                null, $equalB
              ), $this->getCompareIsField(
                $equalC, null, null
              )
            );       
          }
        }      

        return null;
      }
    }
  }

  public function defineOneToOnes(
  ): void {
    if($this->equals->count() === 3){
      [$equalA, $equalC] = [
        $this->equals->first(),
        $this->equals->last()
      ];
      
      if($equalA instanceof EqualField && $equalC instanceof EqualField){
        $foreignKeysA = $equalA->structureTable->foreignKeys()->listNames($equalA->structureTable->table);
        $foreignKeysC = $equalC->structureTable->foreignKeys()->listNames($equalC->structureTable->table);
        
        $hasLeftJoinInEqualA = $foreignKeysA->where(
          fn(ForeignKeyItem $fk) => (
            $fk->foreignKeyReferenceItem->table === $equalC->table &&
            $fk->foreignKeyReferenceItem->key === $equalC->name
          )
        ); 

        $hasLeftJoinInEqualC = $foreignKeysC->where(
          fn(ForeignKeyItem $fk) => (
            $fk->foreignKeyReferenceItem->table === $equalA->table &&
            $fk->foreignKeyReferenceItem->key === $equalA->name
          )
        );

        $leftJoin = $hasLeftJoinInEqualA->exist() === true
          ? $hasLeftJoinInEqualA->first()
          : $hasLeftJoinInEqualC->first();

        if($leftJoin instanceof ForeignKeyItem){
          if($this->parameters->first()->structureTable->table === $leftJoin->foreignKeyReferenceItem->table){
            $this->leftJoin = (
              "Left Join {$leftJoin->table} 
                      On {$leftJoin->table}.{$leftJoin->key} = {$leftJoin->foreignKeyReferenceItem->table}.{$leftJoin->foreignKeyReferenceItem->key}"
            );
          } else {
            $this->leftJoin = (
              "Left Join {$leftJoin->foreignKeyReferenceItem->table} 
                      On {$leftJoin->foreignKeyReferenceItem->table}.{$leftJoin->foreignKeyReferenceItem->key} = {$leftJoin->table}.{$leftJoin->key}"
            );          
          }
            
          $this->isLeftJoin = true;         
        }
      } else $this->isLeftJoin = false;
    } else $this->isLeftJoin = false;
  }

  public function defineClear(
  ): void {
    unset($this->parameters);
    unset($this->statics);
  }
}