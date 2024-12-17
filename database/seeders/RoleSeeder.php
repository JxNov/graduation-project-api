<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'slug' => 'admin',
            ],
            [
                'name' => 'Teacher',
                'slug' => 'teacher',
            ],
            [
                'name' => 'Student',
                'slug' => 'student',
            ],
            [
                'name' => 'Parent',
                'slug' => 'parent',
            ]
        ];

        $permissions = Permission::all();

        foreach ($roles as $role) {
            $role = Role::create($role);

            if ($role->slug === 'admin') {
                $role->permissions()->attach($permissions->where('value', 'admin.read')->pluck('id')->toArray());
                $role->permissions()->attach($permissions->where('value', 'admin.create')->pluck('id')->toArray());
                $role->permissions()->attach($permissions->where('value', 'admin.update')->pluck('id')->toArray());
                $role->permissions()->attach($permissions->where('value', 'admin.delete')->pluck('id')->toArray());
            }

            if ($role->slug === 'teacher') {
                $role->permissions()->attach($permissions->where('value', 'teacher.read')->pluck('id')->toArray());
            }

            if ($role->slug === 'student') {
                $role->permissions()->attach($permissions->where('value', 'student.read')->pluck('id')->toArray());
            }

            if ($role->slug === 'parent') {
                $role->permissions()->attach($permissions->where('value', 'parent.read')->pluck('id')->toArray());
            }
        }
    }
}
