<?php

namespace Websyspro\DynamicSql\Shareds;

class TToken
{
  public int $type;
  public string $line;
  public string $string;
  public string $typeString;

  public function __construct(
    public array|string $token
  ){
    $this->fromArray();
    $this->fromString();
    $this->clearToken();
  }

  private function fromArray(
  ): void {
    if (is_array( $this->token )){
      [ $this->type, $this->string, $this->line
      ] = $this->token;

      $this->typeString = (
        token_name(
          $this->type
        )
      );
    }
  }

  private function fromString(
  ): void {
    if (is_string( $this->token )){
      $this->string = $this->token;
      $this->type = -1;
    }    
  }

  private function clearToken(
  ): void {
    unset($this->token);
  }

  public function getString(
  ): string {
    return $this->string;
  }

  public function getLower(
  ): string {
    return strtoupper(
      $this->string
    );
  }
}