<?php

namespace App\Services\Deltron\Classifiers;

use App\Services\Deltron\Categories\Cat01_Laptops\LaptopsCategoryMapper;

/**
 * Wrapper de retrocompatibilidad que delega a Cat01_Laptops\LaptopsCategoryMapper.
 */
class LaptopsClassifier extends LaptopsCategoryMapper {}
