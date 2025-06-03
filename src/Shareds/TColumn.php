<?php

namespace Websyspro\DynamicSql\Shareds;

class TColumn
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

  public function ToString(
  ): string {
    $name = isset( $this->method )
      ? sprintf( "%s(%s.%s)", ...[
        $this->method, $this->table, $this->name
      ]) : sprintf( "%s.%s", $this->table, $this->name );

    if( isset( $this->surName )) {
      $name = "{$name} as {$this->surName}";      
    }

    return $name;
  }
}