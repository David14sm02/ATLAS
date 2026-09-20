<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EntidadesSeeder extends Seeder
{
    public function run(): void
    {
        $entidades = [
            ['clave_inegi' => '01', 'nombre' => 'Aguascalientes', 'abreviatura' => 'AGS'],
            ['clave_inegi' => '02', 'nombre' => 'Baja California', 'abreviatura' => 'BC'],
            ['clave_inegi' => '03', 'nombre' => 'Baja California Sur', 'abreviatura' => 'BCS'],
            ['clave_inegi' => '04', 'nombre' => 'Campeche', 'abreviatura' => 'CAMP'],
            ['clave_inegi' => '05', 'nombre' => 'Coahuila de Zaragoza', 'abreviatura' => 'COAH'],
            ['clave_inegi' => '06', 'nombre' => 'Colima', 'abreviatura' => 'COL'],
            ['clave_inegi' => '07', 'nombre' => 'Chiapas', 'abreviatura' => 'CHIS'],
            ['clave_inegi' => '08', 'nombre' => 'Chihuahua', 'abreviatura' => 'CHIH'],
            ['clave_inegi' => '09', 'nombre' => 'Ciudad de México', 'abreviatura' => 'CDMX'],
            ['clave_inegi' => '10', 'nombre' => 'Durango', 'abreviatura' => 'DGO'],
            ['clave_inegi' => '11', 'nombre' => 'Guanajuato', 'abreviatura' => 'GTO'],
            ['clave_inegi' => '12', 'nombre' => 'Guerrero', 'abreviatura' => 'GRO'],
            ['clave_inegi' => '13', 'nombre' => 'Hidalgo', 'abreviatura' => 'HGO'],
            ['clave_inegi' => '14', 'nombre' => 'Jalisco', 'abreviatura' => 'JAL'],
            ['clave_inegi' => '15', 'nombre' => 'México', 'abreviatura' => 'MEX'],
            ['clave_inegi' => '16', 'nombre' => 'Michoacán de Ocampo', 'abreviatura' => 'MICH'],
            ['clave_inegi' => '17', 'nombre' => 'Morelos', 'abreviatura' => 'MOR'],
            ['clave_inegi' => '18', 'nombre' => 'Nayarit', 'abreviatura' => 'NAY'],
            ['clave_inegi' => '19', 'nombre' => 'Nuevo León', 'abreviatura' => 'NL'],
            ['clave_inegi' => '20', 'nombre' => 'Oaxaca', 'abreviatura' => 'OAX'],
            ['clave_inegi' => '21', 'nombre' => 'Puebla', 'abreviatura' => 'PUE'],
            ['clave_inegi' => '22', 'nombre' => 'Querétaro', 'abreviatura' => 'QRO'],
            ['clave_inegi' => '23', 'nombre' => 'Quintana Roo', 'abreviatura' => 'QROO'],
            ['clave_inegi' => '24', 'nombre' => 'San Luis Potosí', 'abreviatura' => 'SLP'],
            ['clave_inegi' => '25', 'nombre' => 'Sinaloa', 'abreviatura' => 'SIN'],
            ['clave_inegi' => '26', 'nombre' => 'Sonora', 'abreviatura' => 'SON'],
            ['clave_inegi' => '27', 'nombre' => 'Tabasco', 'abreviatura' => 'TAB'],
            ['clave_inegi' => '28', 'nombre' => 'Tamaulipas', 'abreviatura' => 'TAMPS'],
            ['clave_inegi' => '29', 'nombre' => 'Tlaxcala', 'abreviatura' => 'TLAX'],
            ['clave_inegi' => '30', 'nombre' => 'Veracruz de Ignacio de la Llave', 'abreviatura' => 'VER'],
            ['clave_inegi' => '31', 'nombre' => 'Yucatán', 'abreviatura' => 'YUC'],
            ['clave_inegi' => '32', 'nombre' => 'Zacatecas', 'abreviatura' => 'ZAC'],
        ];

        foreach ($entidades as &$entidad) {
            $entidad['created_at'] = now();
            $entidad['updated_at'] = now();
        }

        DB::table('cat_entidades')->upsert($entidades, ['clave_inegi'], ['nombre', 'abreviatura', 'updated_at']);
    }
}
