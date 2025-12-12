<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ModulSubject;

class ModulSubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            [
                'id' => 10216,
                'id_module' => 772,
                'name' => 'МДК.1.1 Проектирование и разработка баз данных',
            ],
            [
                'id' => 10217,
                'id_module' => 772,
                'name' => 'МДК.1.2 Управление базами данных',
            ],
            [
                'id' => 10222,
                'id_module' => 773,
                'name' => 'МДК.2.1 Численные методы',
            ],
            [
                'id' => 10218,
                'id_module' => 773,
                'name' => 'МДК.2.2 Разработка программных модулей',
            ],
            [
                'id' => 10220,
                'id_module' => 773,
                'name' => 'МДК.2.3 Поддержка и тестирование программных модулей',
            ],
            [
                'id' => 10221,
                'id_module' => 773,
                'name' => 'МДК.2.4 Математическое моделирование',
            ],
            [
                'id' => 10219,
                'id_module' => 773,
                'name' => 'МДК.2.5 Осуществление интеграции программных модулей',
            ],
            [
                'id' => 10223,
                'id_module' => 774,
                'name' => 'МДК.3.1 Проектирование и разработка веб-приложений',
            ],
            [
                'id' => 10224,
                'id_module' => 774,
                'name' => 'МДК.3.2 Оптимизация веб-приложений',
            ],
            [
                'id' => 10225,
                'id_module' => 774,
                'name' => 'МДК.3.3 Обеспечение безопасности веб-приложений',
            ],
        ];

        foreach ($subjects as $subject) {
            ModulSubject::updateOrCreate(
                ['id' => $subject['id']],
                [
                    'id_module' => $subject['id_module'],
                    'name' => $subject['name'],
                ]
            );
        }
    }
}
