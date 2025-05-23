<?php

namespace Websyspro\DynamicSql\Utils;

use Websyspro\Commons\Collection;
use Websyspro\DynamicSql\Interfaces\Token;
use Websyspro\DynamicSql\Interfaces\TokenString;

class ArrowFNFill
{
  public Collection $tokens;
  public bool $isDoubleArrow = false;
  public bool $isStartedBody = false;
  public int $parentheses = 0;
  public int $brackets = 0;

  public function __construct(
    public string $fullBody
  ){
    $this->defineTypes();
    $this->defineTokenNames();
    $this->defineDoubleArrow();
    $this->defineEndBody();
  } 

  public function defineTypes(
  ): void {
    $this->tokens = Collection::Create(
      token_get_all("<?php {$this->fullBody}")
    );
  }

  public function defineTokenNames(
  ): void {
    $this->tokens->Mapper(
      fn(string | array $tokenList) => (
        is_string($tokenList) 
          ? new TokenString($tokenList) 
          : new Token(...$tokenList) 
      )
    );
  }

  public function defineDoubleArrow(
  ): void {
    $this->tokens->Where(
      function(TokenString | Token $token){
        if($token->token === "T_DOUBLE_ARROW"){
          $this->isDoubleArrow = true;
          $this->isStartedBody = true;
        }

        return $this->isDoubleArrow;
      } 
    )->Slice(
      $this->tokens->Copy()->Slice(1)->First()->token === "T_WHITESPACE" ? 2 : 1
    );
  }

  public function defineEndBody(
  ): void {
    $this->tokens->Where(
      function(TokenString | Token $token){
        if($token->value === "(") $this->parentheses++;
        if($token->value === "[") $this->brackets++;

        if($token->value === ")"){
          if($this->parentheses === 0){
            $this->isStartedBody = false;
          } else --$this->parentheses;
        }

        if($token->value === "]"){
          if($this->brackets === 0){
            $this->isStartedBody = false;
          } else --$this->brackets;
        }

        return $this->isStartedBody;
      }
    );
  }

  public function get(
  ): string {
    return (
      $this->tokens->Mapper(
        fn(TokenString | Token $token) => $token->value
      )->JoinNotSpace()
    );
  }

  public static function parse(
    string $fullBody
  ): ArrowFNFill {
    return new static($fullBody);
  }
}