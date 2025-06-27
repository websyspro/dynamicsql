<?php

use Websyspro\DynamicSql\Core\DataByFn;
use Websyspro\DynamicSql\Core\WhereByFn;
use Websyspro\DynamicSql\QueryBuild;
use Websyspro\DynamicSql\Test\Entitys\DocumentEntity;

// $ids = [1, 2, 3];
// $listWhite = [456,897];
// $userId = 45;

// $dates = new stdClass();
// $dates->between = new stdClass();
// $dates->between->dataA = "10/05/2025";
// $dates->between->dataB = "17/05/2025 15:43:33";

enum EUserPerfil: int {
  case Simples = 0;
  case Administrador = 1;
};

enum EDeleted: int {
  case Not = 0;
  case Yes = 1;
};

// $searach = "VERONICA";

$date = [
  "CreatedAtStart" => "04/04/2023",
  "CreatedAtEnd" => "06/04/2023",
  "State" => "C"
];

$Observations = "Valor";

$queryBuild = (
  QueryBuild::Create(DocumentEntity::class)
    ->Where(fn(DocumentEntity $d) => (
      $d->CreatedAt <= $date["CreatedAtEnd"] &&
      $d->Observations == "{$Observations}%"
    ))
);

print_r($queryBuild);

// $whereByFn_ = WhereByFn::Create(
//   fn(DocumentEntity $d) => (
//     $d->Observations != strtoupper("VALOR%") &&
//     $d->CreatedAt <= $date["CreatedAtEnd"] &&
//     $d->Observations == "R$ 15,89" && (
//       $d->Actived == true &&
//       $d->DeletedAt == NULL
//     ) || (
//       $d->Deleted == EDeleted::Yes
//     )
//   )
// );

// print_r($whereByFn_->getCompare());

// $negation = true;
// $BoxIdCurrent = 123;


// $dataByFn = DataByFn::Create(
//   fn(DocumentEntity $d) => [
//     $d->Observations == "super-admin",
//     $d->CreatedAt == "06/04/2023",
//     $d->DeletedAt == null,
//     $d->Actived == true,
//     $d->BoxId == 1
//   ]
// );

// print_r($dataByFn->arrayFromFn());
