<?php

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

// $searach = "VERONICA";

$date = [
  "CreatedAtStart" => "04/04/2023",
  "CreatedAtEnd" => "06/04/2023",
  "State" => "C"
];

$queryBuild = (
  QueryBuild::Create(DocumentEntity::class)
    ->Where(fn(DocumentEntity $d) => (
      $d->CreatedAt <= $date["CreatedAtEnd"] &&
      $d->Observations == "R$ 15,89"
    ))
);

print_r($queryBuild->Get());

// $negation = true;
// $BoxIdCurrent = 123;

// enum EBoxCurrent: int {
//   case Simples = 1;
//   case Administrador = 2;
// }


// $dataByFn = DataByFn::Create(
//   fn(DocumentEntity $d) => [
//     $d->Actived = true,
//     $d->BoxId = EBoxCurrent::Simples->value
//   ]
// )->getData();
