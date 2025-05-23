<?php

namespace Websyspro\DynamicSql\Shareds;

class Token
{
  public function __construct(
    public string $token,
    public string $value
  ){
    $this->token = (
      token_name($token)
    );
  }
}