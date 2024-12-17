<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = Module::all();

        $permissions = [];

        foreach ($modules as $module) {
            $permissions[] = [
                'value' => $module->name . '.create',
                'slug' => $module->name . 'create',
            ];

            $permissions[] = [
                'value' => $module->name . '.read',
                'slug' => $module->name . 'read',
            ];

            $permissions[] = [
                'value' => $module->name . '.update',
                'slug' => $module->name . 'update',
            ];

            $permissions[] = [
                'value' => $module->name . '.delete',
                'slug' => $module->name . 'delete',
            ];
        }

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
