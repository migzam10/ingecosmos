<?php

namespace Tests\Feature;

use App\Models\AnexoOt;
use App\Models\EmpresaCliente;
use App\Models\MarcaVehiculo;
use App\Models\OrdenTrabajo;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Anexos PDF de la OT: subir, listar y eliminar. Solo ADMIN/COORDINADOR.
 */
class AnexoOtTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(array $roles): User
    {
        return User::create([
            'name'     => 'U ' . implode('-', $roles),
            'email'    => strtolower(implode('', $roles)) . random_int(1000, 9999) . '@test.local',
            'password' => bcrypt('secret'),
            'roles'    => $roles,
            'activo'   => true,
        ]);
    }

    private function crearOT(): OrdenTrabajo
    {
        $veh = Vehiculo::first() ?? Vehiculo::create([
            'placa'    => 'TST' . random_int(100, 999),
            'id_marca' => MarcaVehiculo::value('id'),
        ]);

        return OrdenTrabajo::create([
            'numero_ot'          => 900000 + random_int(1, 99999),
            'area'               => 'MECANICA',
            'id_vehiculo'        => $veh->id,
            'id_empresa_cliente' => EmpresaCliente::value('id'),
            'estado_proceso'     => 'EN_PROCESO',
            'estado_semaforo'    => 'A_TIEMPO',
            'fecha_ingreso'      => now()->subDays(3)->toDateString(),
            'creado_por'         => User::value('id'),
        ]);
    }

    public function test_coordinador_sube_y_elimina_anexo(): void
    {
        Storage::fake('public');
        $ot = $this->crearOT();

        $this->actingAs($this->usuario(['COORDINADOR']))
            ->post(route('anexos.store', $ot), [
                'titulo'  => 'Peritaje CIA',
                'archivo' => UploadedFile::fake()->create('peritaje.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $anexo = $ot->anexos()->first();
        $this->assertNotNull($anexo);
        $this->assertSame('Peritaje CIA', $anexo->titulo);
        Storage::disk('public')->assertExists($anexo->ruta);

        // Eliminar
        $this->actingAs($this->usuario(['ADMIN']))
            ->delete(route('anexos.destroy', $anexo))
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($anexo->ruta);
        $this->assertNull(AnexoOt::find($anexo->id));
    }

    public function test_rechaza_archivo_no_pdf(): void
    {
        Storage::fake('public');
        $ot = $this->crearOT();

        $this->actingAs($this->usuario(['ADMIN']))
            ->from(route('ordenes.show', $ot))
            ->post(route('anexos.store', $ot), [
                'titulo'  => 'Foto',
                'archivo' => UploadedFile::fake()->image('foto.jpg'),
            ])
            ->assertSessionHasErrors('archivo');

        $this->assertSame(0, $ot->anexos()->count());
    }

    public function test_recepcion_no_puede_subir_anexos(): void
    {
        $ot = $this->crearOT();

        $this->actingAs($this->usuario(['RECEPCION']))
            ->post(route('anexos.store', $ot), [
                'titulo'  => 'Intento',
                'archivo' => UploadedFile::fake()->create('x.pdf', 50, 'application/pdf'),
            ])
            ->assertForbidden();
    }
}
