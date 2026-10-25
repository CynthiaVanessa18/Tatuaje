<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/TestimonialRepository.php';

$testimonialRepository = new TestimonialRepository(conectarBaseDatos());
$testimonials = $testimonialRepository->published();
$testimonialSummary = $testimonialRepository->publicSummary();
