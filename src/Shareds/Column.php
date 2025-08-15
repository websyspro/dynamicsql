<?php

namespace Websyspro\DynamicSql\Shareds;

use Websyspro\Commons\DataList;
use Websyspro\DynamicSql\Enums\EColumnPriorityType;

class Column
{
  public string $table;
  public string $name;
  public string $surName;
  public string $method;

  public function __construct(
    public string $column
  ){
    $this->defineSurNames();
    $this->defineMethods();
    $this->defineNames();
    $this->clear();
  }

  private function defineSurNames(
  ): void {
    $hasSurname = preg_match(
      "/\.*=>\.*/", $this->column
    ) === 1;

    if( $hasSurname === true ){
      [ $this->name, $this->surName 
      ] = preg_split( "/=>/", $this->column );
    } else {
      $this->name = $this->column;
    };
  }

  private function defineMethods(
  ): void {
    $hasFn = preg_match(
      "/^\w+\(/", $this->name
    ) === 1;


    if( $hasFn === true ){
      $this->method = preg_replace(
        "/Field\(\w+\.\w+\)/", "", $this->name
      );
    }
  }

  private function defineNames(
  ): void {
    $this->name = preg_replace(
      "/(^\w+Field\()|(\)$)/", "", trim(
        $this->name
      )
    );

    [ $this->table,$this->name ] = (
      preg_split( "/\./", $this->name )
    );
  }

  private function clear(
  ): void {
    unset($this->column);
  }

  private function hasMethod(
  ): bool {
    return isset($this->method) === true;
  }

  public function toString(
    EColumnPriorityType $eColumnPriorityType
  ): string {
    if($this->hasMethod()){
      $ucFirst = ucfirst($this->name);
    }

    $table = lcfirst(
      $this->table
    );

    if($eColumnPriorityType === EColumnPriorityType::Primary){
      if($this->hasMethod()){
        $column = "{$this->method}({$this->table}.{$this->name}) As {$this->method}{$ucFirst}";
      } else {
        $column = "{$this->table}.{$this->name} As {$this->name}";
      }
    }

    if($eColumnPriorityType === EColumnPriorityType::Secundary){
      if($this->hasMethod()){
        $column = "{$this->table}.{$this->method}{$ucFirst} As {$table}_{$this->method}{$ucFirst}";      
      } else {
        $column = "{$this->table}.{$this->name} As {$table}_{$this->name}";
      }   
    }

    return $column;
  }

  public function toString___(
    DataList $parameters,
    string|null $table = null
  ): string {
    $hasTableBase = (
      is_null($table) === false
    );

    $isPrimary = (
      is_null($table) === false
    );

    [ $aliasFromParameter ] = $parameters->copy()->where(
      fn(ItemParameter $ip) => $ip->structureTable->table === (
        $hasTableBase ? $this->table : $table
      ) 
    )->all();



    if($aliasFromParameter instanceof ItemParameter){
      if(isset($this->method) === true && $hasTableBase === false){
        if( $hasTableBase ){
          $columnAlias = sprintf( "%s(%s.%s) As %s_%s", ...[
            $this->method, $this->table, $this->name, $aliasFromParameter->name, $this->name
          ]);          
        } else {
          $columnAlias = sprintf( "%s(%s.%s) As %s%s", ...[
            $this->method, $this->table, $this->name, $this->method, ucfirst($this->name)
          ]);
        }
      } else {
        if( $hasTableBase ){
          $columnAlias = sprintf( "%s.%s As %s_%s", ...[
            $this->table, $this->name, $aliasFromParameter->name, $this->name
          ]);
        } else {
          $columnAlias = sprintf( "%s.%s As %s", ...[
            $this->table, $this->name, $this->name
          ]);
        }
      }

      if(isset($this->surName) === true){
        $columnAlias = sprintf( "%s.%s As %s", ...[
          $this->table, $this->name, $this->surName
        ]);   
      }
    }

    $isPrimary = (
      $table !== null
    );

    [ $itemParameter ] = $parameters->copy()->where(
      fn(ItemParameter $itemParameter) => (
        $itemParameter->structureTable->table === (
          $isPrimary ? $table : $this->table 
        )
      ) 
    )->all();

    if(isset($this->method) === true){
      $column = sprintf( "%s(%s.%s) As %s%s", ...[
        $this->method, $this->table, $this->name, $this->method, ucfirst($this->name)
      ]);
    } else
    if(isset($this->method) === false){
      $column = sprintf( "%s.%s As %s_%s", ...[
        $this->table, $this->name, $aliasFromParameter->name, $this->name
      ]);
    }

    if(isset($this->surName) === true){
      $column = sprintf( "%s.%s As %s", ...[
        $this->table, $this->name, $this->surName
      ]);   
    }    

    return $column;
  }

  public function getOrderByString(
  ): string {
    return sprintf( "%s.%s", ...[
      $this->table, $this->name
    ]);
  }
}