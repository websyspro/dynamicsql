<?php

namespace Websyspro\DynamicSql\Enums;

enum EqualType
{
  case Equal;
  case StartGroup;
  case EndGroup;
  case Logical;
}