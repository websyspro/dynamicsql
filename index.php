<?php

use Websyspro\DynamicSql\TFindByFN;

require_once "./vendor/autoload.php";

class BaseEntity
{
  public int $Id;
  public bool $Actived;
  public int $ActivedBy;
  public DateTime $ActivedAt;
  public int $CreatedBy;
  public DateTime $CreatedAt;
  public int $UpdatedBy;
  public DateTime $UpdatedAt;
  public bool $Deleted;
  public int $DeletedBy;
  public DateTime $DeletedAt;
}

class Document
extends BaseEntity
{
  public string $Type;
  public string $State;
  public int $BoxId;
  public int $OperatorId;
  public int $CustomerId;
  public float $Value;
  public float $ValueInPix;
  public float $ValueInDebitCard;
  public float $ValueInCreditCard;
  public float $InstallmentsFromCreditCard;
  public float $ValueInCash;
  public float $AmountReceived;
  public float $ValueChange;
  public string $Observations;
}

class DocumentItem
extends BaseEntity
{
  public string $DocumentId;
  public string $ProductId;
  public float $Value;
  public float $Amount;
  public float $Discount;
  public float $TotalValue;
}

$stdClassSubB = new stdClass;
$stdClassSubB->TestB = "TestB";

$stdClassSub = new stdClass;
$stdClassSub->Sub = "SOUSA";
$stdClassSub->Test = $stdClassSubB->TestB;


$stdClass = new stdClass;
$stdClass->test = $stdClassSub->Test;
$stdClass->name = [
  "Test" => $stdClassSub
];

$CreatedAt = "29/04/2025 20:54:32";
$CustomerList = [
  "optionsValue" => [
    "Item1" => [
      "Item2" => [
        "Item3" => [1, 2, 3]
      ]
    ]
  ]
];

$findByFN = new TFindByFN(
  fn(Document $d, DocumentItem $i) => (
    $d->Id === 72 &&
    $d->BoxId === 2 && 
    $d->Id === $i->DocumentId &&
    $d->CreatedAt == $CreatedAt ||
    $d->CustomerId != $CustomerList["optionsValue"]["Item1"]["Item2"]["Item3"]
  )
);


print_r($findByFN->getConditions());