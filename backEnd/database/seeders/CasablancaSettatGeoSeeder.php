<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CasablancaSettatGeoSeeder extends Seeder
{
    /**
     * Seed Région Casablanca-Settat avec toutes ses provinces, communes et coordonnées GPS
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
                'latitude' => 33.5731,
                'longitude' => -7.5898,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. PROVINCES ET LEURS COMMUNES AVEC COORDONNÉES GPS RÉELLES
            $provinces = [
                // ==================== CASABLANCA ====================
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'Casablanca',
                    'name_ar' => 'الدار البيضاء',
                    'code' => 'CAS',
                    'latitude' => 33.5731,
                    'longitude' => -7.5898,
                    'communes' => [
                        // Arrondissements de Casablanca
                        ['name_fr' => 'Aïn Chock', 'name_ar' => 'عين الشق', 'type' => 'urbaine', 'code_postal' => '20470', 'lat' => 33.5366, 'lng' => -7.6289],
                        ['name_fr' => 'Aïn Sebaâ - Hay Mohammadi', 'name_ar' => 'عين السبع الحي المحمدي', 'type' => 'urbaine', 'code_postal' => '20600', 'lat' => 33.6147, 'lng' => -7.5314],
                        ['name_fr' => 'Anfa', 'name_ar' => 'أنفا', 'type' => 'urbaine', 'code_postal' => '20050', 'lat' => 33.5892, 'lng' => -7.6548],
                        ['name_fr' => 'Ben M\'sick', 'name_ar' => 'بن مسيك', 'type' => 'urbaine', 'code_postal' => '20400', 'lat' => 33.5539, 'lng' => -7.5714],
                        ['name_fr' => 'Casablanca-Anfa', 'name_ar' => 'الدار البيضاء أنفا', 'type' => 'urbaine', 'code_postal' => '20000', 'lat' => 33.5731, 'lng' => -7.5898],
                        ['name_fr' => 'Hay Hassani', 'name_ar' => 'الحي الحسني', 'type' => 'urbaine', 'code_postal' => '20200', 'lat' => 33.5286, 'lng' => -7.6598],
                        ['name_fr' => 'Al Fida - Mers Sultan', 'name_ar' => 'الفداء مرس السلطان', 'type' => 'urbaine', 'code_postal' => '20100', 'lat' => 33.5928, 'lng' => -7.6158],
                        ['name_fr' => 'Mechouar de Casablanca', 'name_ar' => 'مشور الدار البيضاء', 'type' => 'urbaine', 'code_postal' => '20250', 'lat' => 33.5947, 'lng' => -7.6214],
                        ['name_fr' => 'Moulay Rachid', 'name_ar' => 'مولاي رشيد', 'type' => 'urbaine', 'code_postal' => '20450', 'lat' => 33.5389, 'lng' => -7.5539],
                        ['name_fr' => 'Sidi Bernoussi', 'name_ar' => 'سيدي برنوصي', 'type' => 'urbaine', 'code_postal' => '20600', 'lat' => 33.6142, 'lng' => -7.5267],
                        ['name_fr' => 'Sidi Moumen', 'name_ar' => 'سيدي مومن', 'type' => 'urbaine', 'code_postal' => '20650', 'lat' => 33.5856, 'lng' => -7.5289],
                        ['name_fr' => 'Sidi Othmane', 'name_ar' => 'سيدي عثمان', 'type' => 'urbaine', 'code_postal' => '20700', 'lat' => 33.5592, 'lng' => -7.5386],
                        ['name_fr' => 'Dar Bouazza', 'name_ar' => 'دار بوعزة', 'type' => 'urbaine', 'code_postal' => '27182', 'lat' => 33.5267, 'lng' => -7.7856],
                        ['name_fr' => 'Bouskoura', 'name_ar' => 'بوسكورة', 'type' => 'urbaine', 'code_postal' => '27182', 'lat' => 33.4528, 'lng' => -7.6508],
                        ['name_fr' => 'Médiouna', 'name_ar' => 'مديونة', 'type' => 'rurale', 'code_postal' => '27253', 'lat' => 33.4539, 'lng' => -7.5019],
                        ['name_fr' => 'Oulad Saleh', 'name_ar' => 'أولاد صالح', 'type' => 'rurale', 'code_postal' => '27302', 'lat' => 33.4886, 'lng' => -7.4528],
                        ['name_fr' => 'Tit Mellil', 'name_ar' => 'تيط مليل', 'type' => 'urbaine', 'code_postal' => '27203', 'lat' => 33.5678, 'lng' => -7.4753],
                        ['name_fr' => 'Lissasfa', 'name_ar' => 'الليساسفة', 'type' => 'rurale', 'code_postal' => '20250', 'lat' => 33.5156, 'lng' => -7.6789],
                        ['name_fr' => 'Oulad Azzouz', 'name_ar' => 'أولاد عزوز', 'type' => 'rurale', 'code_postal' => '27052', 'lat' => 33.4267, 'lng' => -7.6142],
                        ['name_fr' => 'Sidi Hajjaj', 'name_ar' => 'سيدي حجاج', 'type' => 'rurale', 'code_postal' => '27182', 'lat' => 33.4839, 'lng' => -7.7458],
                    ]
                ],

                // ==================== MOHAMMEDIA ====================
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'Mohammedia',
                    'name_ar' => 'المحمدية',
                    'code' => 'MOH',
                    'latitude' => 33.6864,
                    'longitude' => -7.3833,
                    'communes' => [
                        ['name_fr' => 'Mohammedia', 'name_ar' => 'المحمدية', 'type' => 'urbaine', 'code_postal' => '28000', 'lat' => 33.6864, 'lng' => -7.3833],
                        ['name_fr' => 'Aïn Harrouda', 'name_ar' => 'عين حرودة', 'type' => 'urbaine', 'code_postal' => '28230', 'lat' => 33.6358, 'lng' => -7.4556],
                        ['name_fr' => 'Benslimane', 'name_ar' => 'بنسليمان', 'type' => 'urbaine', 'code_postal' => '13000', 'lat' => 33.6189, 'lng' => -7.1258],
                        ['name_fr' => 'Bouznika', 'name_ar' => 'بوزنيقة', 'type' => 'urbaine', 'code_postal' => '13100', 'lat' => 33.7878, 'lng' => -7.1603],
                        ['name_fr' => 'Ech Challalate', 'name_ar' => 'الشلالات', 'type' => 'rurale', 'code_postal' => '13052', 'lat' => 33.5842, 'lng' => -7.0856],
                        ['name_fr' => 'Mansouria', 'name_ar' => 'المنصورية', 'type' => 'rurale', 'code_postal' => '13102', 'lat' => 33.6778, 'lng' => -7.0589],
                        ['name_fr' => 'Sidi Moussa Ben Ali', 'name_ar' => 'سيدي موسى بن علي', 'type' => 'rurale', 'code_postal' => '13152', 'lat' => 33.5492, 'lng' => -7.2156],
                        ['name_fr' => 'Sidi Moussa El Majdoub', 'name_ar' => 'سيدي موسى المجذوب', 'type' => 'rurale', 'code_postal' => '13202', 'lat' => 33.6539, 'lng' => -7.1692],
                        ['name_fr' => 'Sidi Taibi', 'name_ar' => 'سيدي الطيبي', 'type' => 'rurale', 'code_postal' => '13252', 'lat' => 33.7156, 'lng' => -7.2389],
                        ['name_fr' => 'Oulad Hriz', 'name_ar' => 'أولاد حريز', 'type' => 'rurale', 'code_postal' => '13302', 'lat' => 33.6892, 'lng' => -7.2856],
                        ['name_fr' => 'Beni Yakhlef', 'name_ar' => 'بني يخلف', 'type' => 'rurale', 'code_postal' => '13352', 'lat' => 33.5678, 'lng' => -7.1489],
                        ['name_fr' => 'Fejja', 'name_ar' => 'الفجة', 'type' => 'rurale', 'code_postal' => '13402', 'lat' => 33.6428, 'lng' => -7.0892],
                    ]
                ],

                // ==================== EL JADIDA ====================
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'El Jadida',
                    'name_ar' => 'الجديدة',
                    'code' => 'JDI',
                    'latitude' => 33.2316,
                    'longitude' => -8.5007,
                    'communes' => [
                        ['name_fr' => 'El Jadida', 'name_ar' => 'الجديدة', 'type' => 'urbaine', 'code_postal' => '24000', 'lat' => 33.2316, 'lng' => -8.5007],
                        ['name_fr' => 'Azemmour', 'name_ar' => 'أزمور', 'type' => 'urbaine', 'code_postal' => '24100', 'lat' => 33.2847, 'lng' => -8.3417],
                        ['name_fr' => 'Sidi Bennour', 'name_ar' => 'سيدي بنور', 'type' => 'urbaine', 'code_postal' => '24200', 'lat' => 32.6486, 'lng' => -8.4264],
                        ['name_fr' => 'Moulay Abdellah', 'name_ar' => 'مولاي عبد الله', 'type' => 'rurale', 'code_postal' => '24252', 'lat' => 33.1756, 'lng' => -8.6856],
                        ['name_fr' => 'Bir Jdid', 'name_ar' => 'بئر الجديد', 'type' => 'rurale', 'code_postal' => '24302', 'lat' => 33.3156, 'lng' => -8.4289],
                        ['name_fr' => 'Haouzia', 'name_ar' => 'الهوزية', 'type' => 'rurale', 'code_postal' => '24052', 'lat' => 33.2678, 'lng' => -8.5892],
                        ['name_fr' => 'Oulad Frej', 'name_ar' => 'أولاد فرج', 'type' => 'rurale', 'code_postal' => '24102', 'lat' => 33.1892, 'lng' => -8.3756],
                        ['name_fr' => 'Sidi Smail', 'name_ar' => 'سيدي اسماعيل', 'type' => 'rurale', 'code_postal' => '24152', 'lat' => 33.2539, 'lng' => -8.2892],
                        ['name_fr' => 'Zemamra', 'name_ar' => 'زمامرة', 'type' => 'urbaine', 'code_postal' => '24350', 'lat' => 32.6142, 'lng' => -8.6858],
                        ['name_fr' => 'Khemis Zemamra', 'name_ar' => 'خميس زمامرة', 'type' => 'rurale', 'code_postal' => '24352', 'lat' => 32.6389, 'lng' => -8.6456],
                        ['name_fr' => 'Sidi Abed', 'name_ar' => 'سيدي عابد', 'type' => 'rurale', 'code_postal' => '24202', 'lat' => 33.1456, 'lng' => -8.4589],
                        ['name_fr' => 'Oulad Rahmoune', 'name_ar' => 'أولاد رحمون', 'type' => 'rurale', 'code_postal' => '24402', 'lat' => 33.0892, 'lng' => -8.5256],
                    ]
                ],

                // ==================== NOUACEUR ====================
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'Nouaceur',
                    'name_ar' => 'النواصر',
                    'code' => 'NOU',
                    'latitude' => 33.3667,
                    'longitude' => -7.5833,
                    'communes' => [
                        ['name_fr' => 'Bouskoura', 'name_ar' => 'بوسكورة', 'type' => 'urbaine', 'code_postal' => '27182', 'lat' => 33.4528, 'lng' => -7.6508],
                        ['name_fr' => 'Dar Bouazza', 'name_ar' => 'دار بوعزة', 'type' => 'urbaine', 'code_postal' => '27182', 'lat' => 33.5267, 'lng' => -7.7856],
                        ['name_fr' => 'Nouaceur', 'name_ar' => 'النواصر', 'type' => 'rurale', 'code_postal' => '27002', 'lat' => 33.3667, 'lng' => -7.5833],
                        ['name_fr' => 'Oulad Azzouz', 'name_ar' => 'أولاد عزوز', 'type' => 'rurale', 'code_postal' => '27052', 'lat' => 33.4267, 'lng' => -7.6142],
                        ['name_fr' => 'Oulad Saleh', 'name_ar' => 'أولاد صالح', 'type' => 'rurale', 'code_postal' => '27102', 'lat' => 33.4886, 'lng' => -7.4528],
                        ['name_fr' => 'Oulad H\'Riz Sahel', 'name_ar' => 'أولاد حريز الساحل', 'type' => 'rurale', 'code_postal' => '27152', 'lat' => 33.3256, 'lng' => -7.6589],
                        ['name_fr' => 'Sidi Hajjaj', 'name_ar' => 'سيدي حجاج', 'type' => 'rurale', 'code_postal' => '27202', 'lat' => 33.4839, 'lng' => -7.7458],
                    ]
                ],

                // ==================== SETTAT ====================
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'Settat',
                    'name_ar' => 'سطات',
                    'code' => 'SET',
                    'latitude' => 33.0008,
                    'longitude' => -7.6164,
                    'communes' => [
                        ['name_fr' => 'Settat', 'name_ar' => 'سطات', 'type' => 'urbaine', 'code_postal' => '26000', 'lat' => 33.0008, 'lng' => -7.6164],
                        ['name_fr' => 'Ben Ahmed', 'name_ar' => 'بن أحمد', 'type' => 'urbaine', 'code_postal' => '26300', 'lat' => 33.1042, 'lng' => -7.3897],
                        ['name_fr' => 'El Borouj', 'name_ar' => 'البروج', 'type' => 'urbaine', 'code_postal' => '25250', 'lat' => 32.5278, 'lng' => -6.7267],
                        ['name_fr' => 'Berrechid', 'name_ar' => 'برشيد', 'type' => 'urbaine', 'code_postal' => '26100', 'lat' => 33.2650, 'lng' => -7.5869],
                        ['name_fr' => 'Oulad M\'rah', 'name_ar' => 'أولاد امراح', 'type' => 'rurale', 'code_postal' => '26052', 'lat' => 33.0892, 'lng' => -7.5256],
                        ['name_fr' => 'Oulad Said', 'name_ar' => 'أولاد سعيد', 'type' => 'rurale', 'code_postal' => '26102', 'lat' => 32.9456, 'lng' => -7.6892],
                        ['name_fr' => 'Riah', 'name_ar' => 'رياح', 'type' => 'rurale', 'code_postal' => '26152', 'lat' => 32.8756, 'lng' => -7.5389],
                        ['name_fr' => 'Sidi El Aïdi', 'name_ar' => 'سيدي العايدي', 'type' => 'rurale', 'code_postal' => '26202', 'lat' => 33.1256, 'lng' => -7.7156],
                        ['name_fr' => 'Soualem', 'name_ar' => 'السوالم', 'type' => 'rurale', 'code_postal' => '26252', 'lat' => 32.9678, 'lng' => -7.4892],
                        ['name_fr' => 'Sidi Rahhal Chatai', 'name_ar' => 'سيدي رحال الشاطئ', 'type' => 'rurale', 'code_postal' => '26302', 'lat' => 33.1892, 'lng' => -7.4556],
                        ['name_fr' => 'Guisser', 'name_ar' => 'كيسر', 'type' => 'rurale', 'code_postal' => '26352', 'lat' => 32.8256, 'lng' => -7.6589],
                        ['name_fr' => 'Boughaz', 'name_ar' => 'بوغاز', 'type' => 'rurale', 'code_postal' => '26402', 'lat' => 33.0456, 'lng' => -7.7892],
                    ]
                ],

                // ==================== BERRECHID ====================
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'Berrechid',
                    'name_ar' => 'برشيد',
                    'code' => 'BER',
                    'latitude' => 33.2650,
                    'longitude' => -7.5869,
                    'communes' => [
                        ['name_fr' => 'Berrechid', 'name_ar' => 'برشيد', 'type' => 'urbaine', 'code_postal' => '26100', 'lat' => 33.2650, 'lng' => -7.5869],
                        ['name_fr' => 'Deroua', 'name_ar' => 'الدروة', 'type' => 'urbaine', 'code_postal' => '26150', 'lat' => 33.3142, 'lng' => -7.5189],
                        ['name_fr' => 'Oulad Abbou', 'name_ar' => 'أولاد عبو', 'type' => 'rurale', 'code_postal' => '26102', 'lat' => 33.3456, 'lng' => -7.6256],
                        ['name_fr' => 'Oulad Ziane', 'name_ar' => 'أولاد زيان', 'type' => 'rurale', 'code_postal' => '26152', 'lat' => 33.2892, 'lng' => -7.4589],
                        ['name_fr' => 'Kasbat Ben Mchich', 'name_ar' => 'قصبة بن مشيش', 'type' => 'rurale', 'code_postal' => '26202', 'lat' => 33.1756, 'lng' => -7.5892],
                        ['name_fr' => 'Lambarkiyine', 'name_ar' => 'لمباركيين', 'type' => 'rurale', 'code_postal' => '26252', 'lat' => 33.2256, 'lng' => -7.6756],
                        ['name_fr' => 'Sahel Oulad H\'riz', 'name_ar' => 'ساحل أولاد حريز', 'type' => 'rurale', 'code_postal' => '26302', 'lat' => 33.3892, 'lng' => -7.5456],
                        ['name_fr' => 'Deroua Gare', 'name_ar' => 'الدروة المحطة', 'type' => 'rurale', 'code_postal' => '26352', 'lat' => 33.3256, 'lng' => -7.4892],
                    ]
                ],

                // ==================== SIDI BENNOUR ====================
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'Sidi Bennour',
                    'name_ar' => 'سيدي بنور',
                    'code' => 'SBN',
                    'latitude' => 32.6486,
                    'longitude' => -8.4264,
                    'communes' => [
                        ['name_fr' => 'Sidi Bennour', 'name_ar' => 'سيدي بنور', 'type' => 'urbaine', 'code_postal' => '24200', 'lat' => 32.6486, 'lng' => -8.4264],
                        ['name_fr' => 'Jemaat Shaim', 'name_ar' => 'جماعة الشعيم', 'type' => 'urbaine', 'code_postal' => '24250', 'lat' => 32.7456, 'lng' => -8.3892],
                        ['name_fr' => 'Laghnadra', 'name_ar' => 'الغنادرة', 'type' => 'rurale', 'code_postal' => '24202', 'lat' => 32.6892, 'lng' => -8.5156],
                        ['name_fr' => 'Ouled Frej', 'name_ar' => 'أولاد فرج', 'type' => 'rurale', 'code_postal' => '24252', 'lat' => 32.5756, 'lng' => -8.3689],
                        ['name_fr' => 'Sidi Smail', 'name_ar' => 'سيدي اسماعيل', 'type' => 'rurale', 'code_postal' => '24302', 'lat' => 32.7892, 'lng' => -8.4756],
                        ['name_fr' => 'Zaouiat Sidi Ben Hamdoun', 'name_ar' => 'زاوية سيدي بن حمدون', 'type' => 'rurale', 'code_postal' => '24352', 'lat' => 32.6156, 'lng' => -8.5589],
                        ['name_fr' => 'Oulad Rahmoune', 'name_ar' => 'أولاد رحمون', 'type' => 'rurale', 'code_postal' => '24402', 'lat' => 32.5456, 'lng' => -8.4892],
                        ['name_fr' => 'Bouhmame', 'name_ar' => 'بوحمامة', 'type' => 'rurale', 'code_postal' => '24452', 'lat' => 32.7256, 'lng' => -8.5256],
                    ]
                ],

                // ==================== MEDIOUNA ====================
                [
                    'id' => Str::uuid()->toString(),
                    'name_fr' => 'Médiouna',
                    'name_ar' => 'مديونة',
                    'code' => 'MED',
                    'latitude' => 33.4539,
                    'longitude' => -7.5019,
                    'communes' => [
                        ['name_fr' => 'Médiouna', 'name_ar' => 'مديونة', 'type' => 'urbaine', 'code_postal' => '27253', 'lat' => 33.4539, 'lng' => -7.5019],
                        ['name_fr' => 'Lahraouiyine', 'name_ar' => 'الهراويين', 'type' => 'rurale', 'code_postal' => '27302', 'lat' => 33.4892, 'lng' => -7.5456],
                        ['name_fr' => 'Ouled Hriz Sahel', 'name_ar' => 'أولاد حريز الساحل', 'type' => 'rurale', 'code_postal' => '27352', 'lat' => 33.4156, 'lng' => -7.5892],
                        ['name_fr' => 'Mejjatia Oulad Taleb', 'name_ar' => 'المجاطية أولاد الطالب', 'type' => 'rurale', 'code_postal' => '27402', 'lat' => 33.5156, 'lng' => -7.4589],
                        ['name_fr' => 'Sidi Moumen Jdid', 'name_ar' => 'سيدي مومن الجديد', 'type' => 'rurale', 'code_postal' => '27452', 'lat' => 33.4756, 'lng' => -7.5256],
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
                    'latitude' => $provinceData['latitude'],
                    'longitude' => $provinceData['longitude'],
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
                        'latitude' => $commune['lat'],
                        'longitude' => $commune['lng'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            // Statistiques
            $totalCommunes = array_sum(array_map(fn($p) => count($p['communes']), $provinces));
            $communesUrbaines = 0;
            $communesRurales = 0;

            foreach ($provinces as $province) {
                foreach ($province['communes'] as $commune) {
                    if ($commune['type'] === 'urbaine') {
                        $communesUrbaines++;
                    } else {
                        $communesRurales++;
                    }
                }
            }

            $this->command->info('');
            $this->command->info('✅ Région Casablanca-Settat créée avec succès!');
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->command->info('📍 1 Région (avec coordonnées GPS)');
            $this->command->info('📍 ' . count($provinces) . ' Provinces/Préfectures (avec coordonnées GPS)');
            $this->command->info('📍 ' . $totalCommunes . ' Communes au total');
            $this->command->info('   ├─ ' . $communesUrbaines . ' communes urbaines');
            $this->command->info('   └─ ' . $communesRurales . ' communes rurales');
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->command->info('');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Erreur lors du seeding: ' . $e->getMessage());
            $this->command->error('   Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
}
