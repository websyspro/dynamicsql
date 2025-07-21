<?php

namespace Websyspro\DynamicSql\Test\Entitys;

use Websyspro\Entity\Core\Bases\BaseEntity;
use Websyspro\Entity\Decorations\Columns\Decimal;
use Websyspro\Entity\Decorations\Columns\Number;
use Websyspro\Entity\Decorations\Columns\Text;
use Websyspro\Entity\Decorations\Constraints\OneToOne;
use Websyspro\Entity\Decorations\Statistics\Index;

class DocumentEntity
extends BaseEntity
{
  #[Text(1)]
  #[Index(1)]
  public string $Type;

  #[Text(1)]
  public string $State;

  #[Number()]
  #[Index(2)]
  #[OneToOne(BoxEntity::class)]
  public int $BoxId;

  #[Number()]
  #[Index(2)]
  #[OneToOne(OperatorEntity::class)]
  public int $OperatorId;

  #[Number()]
  #[OneToOne(CustomerEntity::class)]
  public int $CustomerId;

  #[Decimal(10,2)]
  public float $Value;

  #[Decimal(10,2)]
  public float $ValueInPix;

  #[Decimal(10,2)]
  public float $ValueInDebitCard;

  #[Decimal(10,2)]
  public float $ValueInCreditCard;

  #[Decimal(10,2)]
  public float $InstallmentsFromCreditCard;

  #[Decimal(10,2)]
  public float $ValueInCash;

  #[Decimal(10,2)]
  public float $AmountReceived;

  #[Decimal(10,2)]
  public float $ValueChange;

  #[Text(255)]
  public string $Observations;
}