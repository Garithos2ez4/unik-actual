<?php
namespace App\Repositories;

interface CalculadoraRepositoryInterface
{
    public function get();
    public function update(array $data);
    public function findById();
    public function updateTasaFija(array $data);
    public function updateTC($tc);
}
