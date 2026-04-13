<?php

namespace Database\Seeders;

use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as RoleModel;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Role::cases() as $role) {
            RoleModel::findOrCreate($role->value);
        }
    }
}
