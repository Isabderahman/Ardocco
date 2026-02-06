<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CasablancaSettatSeeder extends Seeder
{
    /**
     * Seed Région Casablanca-Settat avec toutes ses provinces et communes
     */
    public function run(): void
    {
        if (DB::table('regions')->where('code', 'CS')->exists()) {
            $this->command?->warn('ℹ️ Région Casablanca-Settat déjà seedée (code=CS), skip.');
            return;
        }

        DB::beginTransaction();

        try {
            // 1. CRÉER LA RÉGION CASABLANCA-SETTAT
            $regionId = Str::uuid()->toString();

            DB::table('regions')->insert([
                'id' => $regionId,
                'name_fr' => 'Casablanca-Settat',
                'name_ar' => 'الدار البيضاء-سطات',
                'code' => 'CS',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. PROVINCES ET LEURS COMMUNES
            $provinces = [
                // CASABLANCA
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'Casablanca',
                    'name_ar' => 'الدار البيضاء',
                    'code' => 'CAS',
                    'communes' => [
                        // Arrondissements de Casablanca
                        ['name_fr' => 'Aïn Chock', 'name_ar' => 'عين الشق', 'type' => 'urbaine', 'code_postal' => '20470'],
                        ['name_fr' => 'Aïn Sebaâ - Hay Mohammadi', 'name_ar' => 'عين السبع الحي المحمدي', 'type' => 'urbaine', 'code_postal' => '20600'],
                        ['name_fr' => 'Anfa', 'name_ar' => 'أنفا', 'type' => 'urbaine', 'code_postal' => '20050'],
                        ['name_fr' => 'Ben M\'sick', 'name_ar' => 'بن مسيك', 'type' => 'urbaine', 'code_postal' => '20400'],
                        ['name_fr' => 'Casablanca-Anfa', 'name_ar' => 'الدار البيضاء أنفا', 'type' => 'urbaine', 'code_postal' => '20000'],
                        ['name_fr' => 'Hay Hassani', 'name_ar' => 'الحي الحسني', 'type' => 'urbaine', 'code_postal' => '20200'],
                        ['name_fr' => 'Al Fida - Mers Sultan', 'name_ar' => 'الفداء مرس السلطان', 'type' => 'urbaine', 'code_postal' => '20100'],
                        ['name_fr' => 'Mechouar de Casablanca', 'name_ar' => 'مشور الدار البيضاء', 'type' => 'urbaine', 'code_postal' => '20250'],
                        ['name_fr' => 'Moulay Rachid', 'name_ar' => 'مولاي رشيد', 'type' => 'urbaine', 'code_postal' => '20450'],
                        ['name_fr' => 'Sidi Bernoussi', 'name_ar' => 'سيدي برنوصي', 'type' => 'urbaine', 'code_postal' => '20600'],
                        ['name_fr' => 'Sidi Moumen', 'name_ar' => 'سيدي مومن', 'type' => 'urbaine', 'code_postal' => '20650'],
                        ['name_fr' => 'Sidi Othmane', 'name_ar' => 'سيدي عثمان', 'type' => 'urbaine', 'code_postal' => '20700'],
                        ['name_fr' => 'Dar Bouazza', 'name_ar' => 'دار بوعزة', 'type' => 'urbaine', 'code_postal' => '27182'],
                        ['name_fr' => 'Bouskoura', 'name_ar' => 'بوسكورة', 'type' => 'urbaine', 'code_postal' => '27182'],
                        ['name_fr' => 'Médiouna', 'name_ar' => 'مديونة', 'type' => 'rurale', 'code_postal' => '27253'],
                        ['name_fr' => 'Oulad Saleh', 'name_ar' => 'أولاد صالح', 'type' => 'rurale', 'code_postal' => '27302'],
                        ['name_fr' => 'Tit Mellil', 'name_ar' => 'تيط مليل', 'type' => 'urbaine', 'code_postal' => '27203'],
                    ]
                ],

                // MOHAMMEDIA
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'Mohammedia',
                    'name_ar' => 'المحمدية',
                    'code' => 'MOH',
                    'communes' => [
                        ['name_fr' => 'Mohammedia', 'name_ar' => 'المحمدية', 'type' => 'urbaine', 'code_postal' => '28000'],
                        ['name_fr' => 'Aïn Harrouda', 'name_ar' => 'عين حرودة', 'type' => 'urbaine', 'code_postal' => '28230'],
                        ['name_fr' => 'Benslimane', 'name_ar' => 'بنسليمان', 'type' => 'urbaine', 'code_postal' => '13000'],
                        ['name_fr' => 'Bouznika', 'name_ar' => 'بوزنيقة', 'type' => 'urbaine', 'code_postal' => '13100'],
                        ['name_fr' => 'Ech Challalate', 'name_ar' => 'الشلالات', 'type' => 'rurale', 'code_postal' => '13052'],
                        ['name_fr' => 'Mansouria', 'name_ar' => 'المنصورية', 'type' => 'rurale', 'code_postal' => '13102'],
                        ['name_fr' => 'Sidi Moussa Ben Ali', 'name_ar' => 'سيدي موسى بن علي', 'type' => 'rurale', 'code_postal' => '13152'],
                        ['name_fr' => 'Sidi Moussa El Majdoub', 'name_ar' => 'سيدي موسى المجذوب', 'type' => 'rurale', 'code_postal' => '13202'],
                    ]
                ],

                // EL JADIDA
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'El Jadida',
                    'name_ar' => 'الجديدة',
                    'code' => 'JDI',
                    'communes' => [
                        ['name_fr' => 'El Jadida', 'name_ar' => 'الجديدة', 'type' => 'urbaine', 'code_postal' => '24000'],
                        ['name_fr' => 'Azemmour', 'name_ar' => 'أزمور', 'type' => 'urbaine', 'code_postal' => '24100'],
                        ['name_fr' => 'Sidi Bennour', 'name_ar' => 'سيدي بنور', 'type' => 'urbaine', 'code_postal' => '24200'],
                        ['name_fr' => 'Moulay Abdellah', 'name_ar' => 'مولاي عبد الله', 'type' => 'rurale', 'code_postal' => '24252'],
                        ['name_fr' => 'Bir Jdid', 'name_ar' => 'بئر الجديد', 'type' => 'rurale', 'code_postal' => '24302'],
                        ['name_fr' => 'Haouzia', 'name_ar' => 'الهوزية', 'type' => 'rurale', 'code_postal' => '24052'],
                        ['name_fr' => 'Oulad Frej', 'name_ar' => 'أولاد فرج', 'type' => 'rurale', 'code_postal' => '24102'],
                        ['name_fr' => 'Sidi Smail', 'name_ar' => 'سيدي اسماعيل', 'type' => 'rurale', 'code_postal' => '24152'],
                        ['name_fr' => 'Zemamra', 'name_ar' => 'زمامرة', 'type' => 'urbaine', 'code_postal' => '24350'],
                        ['name_fr' => 'Khemis Zemamra', 'name_ar' => 'خميس زمامرة', 'type' => 'rurale', 'code_postal' => '24352'],
                    ]
                ],

                // NOUACEUR
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'Nouaceur',
                    'name_ar' => 'النواصر',
                    'code' => 'NOU',
                    'communes' => [
                        ['name_fr' => 'Bouskoura', 'name_ar' => 'بوسكورة', 'type' => 'urbaine', 'code_postal' => '27182'],
                        ['name_fr' => 'Dar Bouazza', 'name_ar' => 'دار بوعزة', 'type' => 'urbaine', 'code_postal' => '27182'],
                        ['name_fr' => 'Nouaceur', 'name_ar' => 'النواصر', 'type' => 'rurale', 'code_postal' => '27002'],
                        ['name_fr' => 'Oulad Azzouz', 'name_ar' => 'أولاد عزوز', 'type' => 'rurale', 'code_postal' => '27052'],
                        ['name_fr' => 'Oulad Saleh', 'name_ar' => 'أولاد صالح', 'type' => 'rurale', 'code_postal' => '27102'],
                    ]
                ],

                // SETTAT
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'Settat',
                    'name_ar' => 'سطات',
                    'code' => 'SET',
                    'communes' => [
                        ['name_fr' => 'Settat', 'name_ar' => 'سطات', 'type' => 'urbaine', 'code_postal' => '26000'],
                        ['name_fr' => 'Ben Ahmed', 'name_ar' => 'بن أحمد', 'type' => 'urbaine', 'code_postal' => '26300'],
                        ['name_fr' => 'El Borouj', 'name_ar' => 'البروج', 'type' => 'urbaine', 'code_postal' => '25250'],
                        ['name_fr' => 'Berrechid', 'name_ar' => 'برشيد', 'type' => 'urbaine', 'code_postal' => '26100'],
                        ['name_fr' => 'Oulad M\'rah', 'name_ar' => 'أولاد امراح', 'type' => 'rurale', 'code_postal' => '26052'],
                        ['name_fr' => 'Oulad Said', 'name_ar' => 'أولاد سعيد', 'type' => 'rurale', 'code_postal' => '26102'],
                        ['name_fr' => 'Riah', 'name_ar' => 'رياح', 'type' => 'rurale', 'code_postal' => '26152'],
                        ['name_fr' => 'Sidi El Aïdi', 'name_ar' => 'سيدي العايدي', 'type' => 'rurale', 'code_postal' => '26202'],
                        ['name_fr' => 'Soualem', 'name_ar' => 'السوالم', 'type' => 'rurale', 'code_postal' => '26252'],
                        ['name_fr' => 'Sidi Rahhal Chatai', 'name_ar' => 'سيدي رحال الشاطئ', 'type' => 'rurale', 'code_postal' => '26302'],
                    ]
                ],

                // BERRECHID
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'Berrechid',
                    'name_ar' => 'برشيد',
                    'code' => 'BER',
                    'communes' => [
                        ['name_fr' => 'Berrechid', 'name_ar' => 'برشيد', 'type' => 'urbaine', 'code_postal' => '26100'],
                        ['name_fr' => 'Deroua', 'name_ar' => 'الدروة', 'type' => 'urbaine', 'code_postal' => '26150'],
                        ['name_fr' => 'Oulad Abbou', 'name_ar' => 'أولاد عبو', 'type' => 'rurale', 'code_postal' => '26102'],
                        ['name_fr' => 'Oulad Ziane', 'name_ar' => 'أولاد زيان', 'type' => 'rurale', 'code_postal' => '26152'],
                        ['name_fr' => 'Kasbat Ben Mchich', 'name_ar' => 'قصبة بن مشيش', 'type' => 'rurale', 'code_postal' => '26202'],
                        ['name_fr' => 'Lambarkiyine', 'name_ar' => 'لمباركيين', 'type' => 'rurale', 'code_postal' => '26252'],
                    ]
                ],

                // SIDI BENNOUR
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'Sidi Bennour',
                    'name_ar' => 'سيدي بنور',
                    'code' => 'SBN',
                    'communes' => [
                        ['name_fr' => 'Sidi Bennour', 'name_ar' => 'سيدي بنور', 'type' => 'urbaine', 'code_postal' => '24200'],
                        ['name_fr' => 'Jemaat Shaim', 'name_ar' => 'جماعة الشعيم', 'type' => 'urbaine', 'code_postal' => '24250'],
                        ['name_fr' => 'Laghnadra', 'name_ar' => 'الغنادرة', 'type' => 'rurale', 'code_postal' => '24202'],
                        ['name_fr' => 'Ouled Frej', 'name_ar' => 'أولاد فرج', 'type' => 'rurale', 'code_postal' => '24252'],
                        ['name_fr' => 'Sidi Smail', 'name_ar' => 'سيدي اسماعيل', 'type' => 'rurale', 'code_postal' => '24302'],
                        ['name_fr' => 'Zaouiat Sidi Ben Hamdoun', 'name_ar' => 'زاوية سيدي بن حمدون', 'type' => 'rurale', 'code_postal' => '24352'],
                    ]
                ],

                // MEDIOUNA
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'Médiouna',
                    'name_ar' => 'مديونة',
                    'code' => 'MED',
                    'communes' => [
                        ['name_fr' => 'Médiouna', 'name_ar' => 'مديونة', 'type' => 'urbaine', 'code_postal' => '27253'],
                        ['name_fr' => 'Lahraouiyine', 'name_ar' => 'الهراويين', 'type' => 'rurale', 'code_postal' => '27302'],
                        ['name_fr' => 'Ouled Hriz Sahel', 'name_ar' => 'أولاد حريز الساحل', 'type' => 'rurale', 'code_postal' => '27352'],
                    ]
                ],
            ];

            // 3. INSÉRER LES PROVINCES ET COMMUNES
            foreach ($provinces as $provinceData) {
                // Insérer la province
                DB::table('provinces')->insert([
                    'id' => $provinceData['id'],
                    'region_id' => $regionId,
                    'name_fr' => $provinceData['name_fr'],
                    'name_ar' => $provinceData['name_ar'],
                    'code' => $provinceData['code'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Insérer les communes de cette province
                foreach ($provinceData['communes'] as $commune) {
                    DB::table('communes')->insert([
                        'id' => Str::uuid()->toString(),
                        'province_id' => $provinceData['id'],
                        'name_fr' => $commune['name_fr'],
                        'name_ar' => $commune['name_ar'],
                        'type' => $commune['type'],
                        'code_postal' => $commune['code_postal'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            $this->command->info('✅ Région Casablanca-Settat créée avec succès!');
            $this->command->info('   - 1 Région');
            $this->command->info('   - ' . count($provinces) . ' Provinces/Préfectures');

            $totalCommunes = array_sum(array_map(fn($p) => count($p['communes']), $provinces));
            $this->command->info('   - ' . $totalCommunes . ' Communes');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Erreur lors du seeding: ' . $e->getMessage());
            throw $e;
        }
    }
}
