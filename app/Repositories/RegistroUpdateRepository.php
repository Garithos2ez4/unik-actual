<?php
namespace App\Repositories;

use App\Models\registroUpdate;

class RegistroUpdateRepository implements RegistroUpdateRepositoryInterface
{
    public function get()
    {
        return registroUpdate::where('columna', 'tasaCambio')->first();
    }

    public function update()
    {
        $registro = $this->get();
        if ($registro) {
            $registro->update([
                'ultimaFecha' => now(),
            ]);
        } else {
            registroUpdate::create([
                'columna' => 'tasaCambio',
                'ultimaFecha' => now(),
                'cantidadUpdate' => 1
            ]);
        }
    }
}
