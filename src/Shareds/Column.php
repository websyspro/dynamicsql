<?php

namespace Websyspro\DynamicSql\Shareds;

use Websyspro\Commons\DataList;

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

  public function toString(
    DataList $parameters
  ): string {
    [ $aliasFromParameter ] = $parameters->copy()->where(
      fn(ItemParameter $ip) => $ip->structureTable->table === $this->table 
    )->all();

    if($aliasFromParameter instanceof ItemParameter){
      if(isset($this->method) === true){
        $columnAlias = sprintf( "%s(%s.%s) As %s_%s", ...[
          $this->method, $this->table, $this->name, $aliasFromParameter->name, $this->name
        ]);
      } else {
        $columnAlias = sprintf( "%s.%s As %s_%s", ...[
          $this->table, $this->name, $aliasFromParameter->name, $this->name
        ]);
      }

      if(isset($this->surName) === true){
        $columnAlias = sprintf( "%s.%s As %s", ...[
          $this->table, $this->name, $this->surName
        ]);   
      }
    }

    return $columnAlias;
  }

  public function getOrderByString(
  ): string {
    return sprintf( "%s.%s", ...[
      $this->table, $this->name
    ]);
  }
}