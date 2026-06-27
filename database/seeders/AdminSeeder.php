<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@alloservice.ma'],
            [
                'name'      => 'Administrateur',
                'password'  => Hash::make('password'),
                'role'      => 'admin',
                'phone'     => '0600000000',
                'avatar'    => 'https://ui-avatars.com/api/?name=Admin&background=7c3aed&color=fff',
                'is_active' => true,
            ]
        );
    }
}
