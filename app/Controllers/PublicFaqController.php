<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/FaqRepository.php';

$faqRepository = new FaqRepository(conectarBaseDatos());
$faqs = $faqRepository->published();
$faqCategories = $faqRepository->categories(true);
