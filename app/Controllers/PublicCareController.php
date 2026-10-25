<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/CareRepository.php';

$careRepository = new CareRepository(conectarBaseDatos());
$careInstructions = $careRepository->published();
