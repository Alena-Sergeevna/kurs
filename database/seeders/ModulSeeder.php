<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Modul;

class ModulSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $moduls = [
            [
                'id' => 772,
                'name' => 'Разработка, администрирование и защита баз данных',
            ],
            [
                'id' => 773,
                'name' => 'Разработка и интеграция модулей программного обеспечения',
            ],
            [
                'id' => 774,
                'name' => 'Проектирование и разработка веб-приложений',
            ],
        ];

        foreach ($moduls as $modul) {
            Modul::updateOrCreate(
                ['id' => $modul['id']],
                ['name' => $modul['name']]
            );
        }
    }
}
