<?php

namespace Database\Seeders;

use App\Models\Countries;
use App\Models\States;
use App\Models\Cities;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Lista de países sudamericanos con sus códigos, estados y ciudades
        $data = [
            [
                'id' => 1,
                'country_name' => 'Argentina',
                'country_code' => 'ARG',
                'states' => [
                    ['state_name' => 'Buenos Aires', 'cities' => ['La Plata', 'Mar del Plata', 'Bahía Blanca', 'Tandil', 'Olavarría']],
                    ['state_name' => 'Córdoba', 'cities' => ['Córdoba', 'Villa Carlos Paz', 'Alta Gracia', 'Río Cuarto', 'Villa María']],
                    ['state_name' => 'Santa Fe', 'cities' => ['Rosario', 'Santa Fe', 'Rafaela', 'Venado Tuerto', 'Reconquista']],
                    ['state_name' => 'Mendoza', 'cities' => ['Mendoza', 'San Rafael', 'Godoy Cruz', 'Maipú', 'Luján de Cuyo']],
                    ['state_name' => 'Tucumán', 'cities' => ['San Miguel de Tucumán', 'Tafí Viejo', 'Yerba Buena', 'Concepción', 'Famaillá']],
                ]
            ],
            [
                'id' => 2,
                'country_name' => 'Brasil',
                'country_code' => 'BRA',
                'states' => [
                    ['state_name' => 'São Paulo', 'cities' => ['São Paulo', 'Campinas', 'Santos', 'Ribeirão Preto', 'Sorocaba']],
                    ['state_name' => 'Rio de Janeiro', 'cities' => ['Rio de Janeiro', 'Niterói', 'Petrópolis', 'Volta Redonda', 'Nova Iguaçu']],
                    ['state_name' => 'Minas Gerais', 'cities' => ['Belo Horizonte', 'Uberlândia', 'Juiz de Fora', 'Montes Claros', 'Betim']],
                    ['state_name' => 'Bahía', 'cities' => ['Salvador', 'Feira de Santana', 'Vitória da Conquista', 'Ilhéus', 'Porto Seguro']],
                    ['state_name' => 'Paraná', 'cities' => ['Curitiba', 'Londrina', 'Maringá', 'Foz do Iguaçu', 'Ponta Grossa']],
                ]
            ],
            [
                'id' => 3,
                'country_name' => 'Chile',
                'country_code' => 'CHL',
                'states' => [
                    ['state_name' => 'Santiago Metropolitan', 'cities' => ['Santiago', 'Puente Alto', 'La Florida', 'Maipú', 'Las Condes']],
                    ['state_name' => 'Valparaíso', 'cities' => ['Valparaíso', 'Viña del Mar', 'Quilpué', 'Quillota', 'San Antonio']],
                    ['state_name' => 'Bío-Bío', 'cities' => ['Concepción', 'Los Ángeles', 'Talcahuano', 'Chillán', 'Coronel']],
                    ['state_name' => 'Araucanía', 'cities' => ['Temuco', 'Villarrica', 'Pucón', 'Angol', 'Lautaro']],
                    ['state_name' => 'Antofagasta', 'cities' => ['Antofagasta', 'Calama', 'Tocopilla', 'Mejillones', 'Taltal']],
                ]
            ],
            [
                'id' => 0,
                'country_name' => 'Paraguay',
                'country_code' => 'PRY',
                'states' => [
                    ['state_name' => 'Central', 'cities' => ['Asunción', 'Luque', 'San Lorenzo', 'Lambaré', 'Fernando de la Mora']],
                    ['state_name' => 'Alto Paraná', 'cities' => ['Ciudad del Este', 'Hernandarias', 'Minga Guazú', 'Presidente Franco', 'Itaipú']],
                    ['state_name' => 'Itapúa', 'cities' => ['Encarnación', 'Hohenau', 'Bella Vista', 'Capitán Meza', 'Fram']],
                    ['state_name' => 'Caaguazú', 'cities' => ['Coronel Oviedo', 'Caaguazú', 'J. Eulogio Estigarribia', 'Repatriación', 'San Joaquín']],
                    ['state_name' => 'Concepción', 'cities' => ['Concepción', 'Horqueta', 'Belén', 'Loreto', 'Yby Yaú']],
                ]
            ],
            [
                'id' => 4,
                'country_name' => 'Colombia',
                'country_code' => 'COL',
                'states' => [
                    ['state_name' => 'Bogotá', 'cities' => ['Bogotá', 'Soacha', 'Chía', 'Zipaquirá', 'Facatativá']],
                    ['state_name' => 'Antioquia', 'cities' => ['Medellín', 'Bello', 'Envigado', 'Itagüí', 'Rionegro']],
                    ['state_name' => 'Valle del Cauca', 'cities' => ['Cali', 'Palmira', 'Buenaventura', 'Tuluá', 'Buga']],
                    ['state_name' => 'Atlántico', 'cities' => ['Barranquilla', 'Soledad', 'Malambo', 'Sabanalarga', 'Baranoa']],
                    ['state_name' => 'Santander', 'cities' => ['Bucaramanga', 'Floridablanca', 'Girón', 'Piedecuesta', 'Barrancabermeja']],
                ]
            ],
        ];

        // Iterar sobre los datos y crear países, estados y ciudades
        foreach ($data as $countryData) {
            $country = Countries::firstOrCreate([
                'id' => $countryData['id'],
                'country_name' => $countryData['country_name'], 
                'country_code' => $countryData['country_code'],
                'created_at' => now(), 
                'updated_at' => now()
            ]);

            foreach ($countryData['states'] as $stateData) {
                $state = States::firstOrCreate([
                    'state_name' => $stateData['state_name'], 
                    'country_id' => $country->id, 
                    'created_at' => now(), 
                    'updated_at' => now()
                ]);

                foreach ($stateData['cities'] as $cityName) {
                    Cities::firstOrCreate([
                        'city_name' => $cityName, 
                        'state_id' => $state->id, 
                        'city_code'=>'',
                        'created_at' => now(), 
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }
}
