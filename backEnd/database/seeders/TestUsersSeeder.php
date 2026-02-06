<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Seed des utilisateurs de test (admin/agents/vendeur/promoteur).
     *
     * Comptes par défaut (password: "password") :
     * - admin@ardocco.test
     * - agent1@ardocco.test / agent2@ardocco.test
     * - vendeur1@ardocco.test / vendeur2@ardocco.test
     * - promoteur1@ardocco.test / promoteur2@ardocco.test
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@ardocco.test',
                'password' => 'password',
                'role' => 'admin',
                'first_name' => 'Admin',
                'last_name' => 'Ardocco',
                'phone' => '+212600000001',
                'company_name' => null,
                'is_verified' => true,
                'is_active' => true,
            ],
            [
                'email' => 'agent1@ardocco.test',
                'password' => 'password',
                'role' => 'agent',
                'first_name' => 'Agent',
                'last_name' => 'One',
                'phone' => '+212600000101',
                'company_name' => 'Ardocco Agency',
                'is_verified' => true,
                'is_active' => true,
            ],
            [
                'email' => 'agent2@ardocco.test',
                'password' => 'password',
                'role' => 'agent',
                'first_name' => 'Agent',
                'last_name' => 'Two',
                'phone' => '+212600000102',
                'company_name' => 'Ardocco Agency',
                'is_verified' => true,
                'is_active' => true,
            ],
            [
                'email' => 'vendeur1@ardocco.test',
                'password' => 'password',
                'role' => 'vendeur',
                'first_name' => 'Vendeur',
                'last_name' => 'One',
                'phone' => '+212600000201',
                'company_name' => null,
                'is_verified' => true,
                'is_active' => true,
            ],
            [
                'email' => 'vendeur2@ardocco.test',
                'password' => 'password',
                'role' => 'vendeur',
                'first_name' => 'Vendeur',
                'last_name' => 'Two',
                'phone' => '+212600000202',
                'company_name' => null,
                'is_verified' => false,
                'is_active' => true,
            ],
            [
                'email' => 'promoteur1@ardocco.test',
                'password' => 'password',
                'role' => 'promoteur',
                'first_name' => 'Promoteur',
                'last_name' => 'One',
                'phone' => '+212600000301',
                'company_name' => 'Promoteur One SARL',
                'is_verified' => true,
                'is_active' => true,
            ],
            [
                'email' => 'promoteur2@ardocco.test',
                'password' => 'password',
                'role' => 'promoteur',
                'first_name' => 'Promoteur',
                'last_name' => 'Two',
                'phone' => '+212600000302',
                'company_name' => 'Promoteur Two SA',
                'is_verified' => true,
                'is_active' => false,
            ],
        ];

        foreach ($users as $userData) {
            $email = $userData['email'];
            $plainPassword = $userData['password'];

            unset($userData['password']);
            $emailVerifiedAt = $userData['is_verified'] ? now() : null;

            $existing = User::query()->where('email', $email)->first();

            if (!$existing) {
                $created = User::create([
                    ...$userData,
                    'email' => $email,
                    'password' => Hash::make($plainPassword),
                ]);
                $created->forceFill(['email_verified_at' => $emailVerifiedAt])->save();
                continue;
            }

            $existing->fill($userData);
            $existing->save();
            $existing->forceFill(['email_verified_at' => $emailVerifiedAt])->save();
        }

        $this->command?->info('✅ Utilisateurs de test créés/actualisés.');
        $this->command?->info('   Password par défaut: "password"');
    }
}
