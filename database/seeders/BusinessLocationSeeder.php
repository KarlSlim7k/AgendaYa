<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessLocation;
use Illuminate\Database\Seeder;

class BusinessLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = Business::where('estado', 'approved')->get();

        // Estilos Modernos - 3 sucursales
        $estilos = $businesses->where('nombre', 'Estilos Modernos')->first();
        if ($estilos) {
            BusinessLocation::create([
                'business_id' => $estilos->id,
                'nombre' => 'Matriz Polanco',
                'direccion' => 'Av. Presidente Masaryk #250, Col. Polanco',
                'ciudad' => 'Ciudad de México',
                'estado' => 'CDMX',
                'codigo_postal' => '11560',
                'telefono' => '+525512345678',
                'zona_horaria' => 'America/Mexico_City',
                'latitud' => 19.4326,
                'longitud' => -99.1906,
                'activo' => true,
            ]);

            BusinessLocation::create([
                'business_id' => $estilos->id,
                'nombre' => 'Sucursal Roma',
                'direccion' => 'Calle Álvaro Obregón #45, Col. Roma Norte',
                'ciudad' => 'Ciudad de México',
                'estado' => 'CDMX',
                'codigo_postal' => '06700',
                'telefono' => '+525512345679',
                'zona_horaria' => 'America/Mexico_City',
                'latitud' => 19.4141,
                'longitud' => -99.1620,
                'activo' => true,
            ]);

            BusinessLocation::create([
                'business_id' => $estilos->id,
                'nombre' => 'Sucursal Condesa',
                'direccion' => 'Av. Michoacán #30, Col. Condesa',
                'ciudad' => 'Ciudad de México',
                'estado' => 'CDMX',
                'codigo_postal' => '06140',
                'telefono' => '+525512345680',
                'zona_horaria' => 'America/Mexico_City',
                'latitud' => 19.4069,
                'longitud' => -99.1711,
                'activo' => true,
            ]);
        }

        // Clínica San Rafael - 4 sucursales
        $clinica = $businesses->where('nombre', 'Clínica San Rafael')->first();
        if ($clinica) {
            BusinessLocation::create([
                'business_id' => $clinica->id,
                'nombre' => 'Clínica Centro',
                'direccion' => 'Eje Central Lázaro Cárdenas #100, Col. Centro',
                'ciudad' => 'Ciudad de México',
                'estado' => 'CDMX',
                'codigo_postal' => '06000',
                'telefono' => '+525587654321',
                'zona_horaria' => 'America/Mexico_City',
                'latitud' => 19.4342,
                'longitud' => -99.1387,
                'activo' => true,
            ]);

            BusinessLocation::create([
                'business_id' => $clinica->id,
                'nombre' => 'Clínica Del Valle',
                'direccion' => 'Av. Insurgentes Sur #1605, Col. Del Valle',
                'ciudad' => 'Ciudad de México',
                'estado' => 'CDMX',
                'codigo_postal' => '03100',
                'telefono' => '+525587654322',
                'zona_horaria' => 'America/Mexico_City',
                'latitud' => 19.3789,
                'longitud' => -99.1670,
                'activo' => true,
            ]);

            BusinessLocation::create([
                'business_id' => $clinica->id,
                'nombre' => 'Clínica Narvarte',
                'direccion' => 'Calle Dr. José María Vértiz #950, Col. Narvarte',
                'ciudad' => 'Ciudad de México',
                'estado' => 'CDMX',
                'codigo_postal' => '03020',
                'telefono' => '+525587654323',
                'zona_horaria' => 'America/Mexico_City',
                'latitud' => 19.3923,
                'longitud' => -99.1538,
                'activo' => true,
            ]);

            BusinessLocation::create([
                'business_id' => $clinica->id,
                'nombre' => 'Clínica Santa Fe',
                'direccion' => 'Av. Vasco de Quiroga #3800, Col. Santa Fe',
                'ciudad' => 'Ciudad de México',
                'estado' => 'CDMX',
                'codigo_postal' => '05348',
                'telefono' => '+525587654324',
                'zona_horaria' => 'America/Mexico_City',
                'latitud' => 19.3577,
                'longitud' => -99.2610,
                'activo' => false, // Esta inactiva temporalmente
            ]);
        }

        // Taller Rodríguez - 2 sucursales
        $taller = $businesses->where('nombre', 'Taller Mecánico Rodríguez')->first();
        if ($taller) {
            BusinessLocation::create([
                'business_id' => $taller->id,
                'nombre' => 'Taller Matriz',
                'direccion' => 'Calz. Ignacio Zaragoza #1200, Col. Agrícola Oriental',
                'ciudad' => 'Ciudad de México',
                'estado' => 'CDMX',
                'codigo_postal' => '08500',
                'telefono' => '+525598765432',
                'zona_horaria' => 'America/Mexico_City',
                'latitud' => 19.4028,
                'longitud' => -99.0824,
                'activo' => true,
            ]);

            BusinessLocation::create([
                'business_id' => $taller->id,
                'nombre' => 'Taller Tlalpan',
                'direccion' => 'Calz. de Tlalpan #3500, Col. Espartaco',
                'ciudad' => 'Ciudad de México',
                'estado' => 'CDMX',
                'codigo_postal' => '04870',
                'telefono' => '+525598765433',
                'zona_horaria' => 'America/Mexico_City',
                'latitud' => 19.2991,
                'longitud' => -99.1372,
                'activo' => true,
            ]);
        }

        // Spa Relax - 2 sucursales
        $spa = $businesses->where('nombre', 'Spa Relax Total')->first();
        if ($spa) {
            BusinessLocation::create([
                'business_id' => $spa->id,
                'nombre' => 'Spa Interlomas',
                'direccion' => 'Blvd. Interlomas #5, Col. Interlomas',
                'ciudad' => 'Huixquilucan',
                'estado' => 'Estado de México',
                'codigo_postal' => '52787',
                'telefono' => '+525523456789',
                'zona_horaria' => 'America/Mexico_City',
                'latitud' => 19.3945,
                'longitud' => -99.2674,
                'activo' => true,
            ]);

            BusinessLocation::create([
                'business_id' => $spa->id,
                'nombre' => 'Spa Coyoacán',
                'direccion' => 'Av. México #120, Col. Coyoacán Centro',
                'ciudad' => 'Ciudad de México',
                'estado' => 'CDMX',
                'codigo_postal' => '04000',
                'telefono' => '+525523456790',
                'zona_horaria' => 'America/Mexico_City',
                'latitud' => 19.3500,
                'longitud' => -99.1622,
                'activo' => true,
            ]);
        }

        $this->command->info('✅ 11 sucursales creadas (10 activas, 1 inactiva)');
    }
}
