<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $rolesTable = $tableNames['roles'];
        $modelHasRolesTable = $tableNames['model_has_roles'];
        $roleHasPermissionsTable = $tableNames['role_has_permissions'];

        $legacyRoles = DB::table($rolesTable)
            ->where('name', 'lic-medewerker')
            ->get();

        foreach ($legacyRoles as $legacyRole) {
            $canonicalRole = DB::table($rolesTable)
                ->where('name', 'LICEmployee')
                ->where('guard_name', $legacyRole->guard_name)
                ->first();

            if ($canonicalRole === null) {
                DB::table($rolesTable)
                    ->where('id', $legacyRole->id)
                    ->update(['name' => 'LICEmployee']);

                continue;
            }

            $modelRoleRows = DB::table($modelHasRolesTable)
                ->where('role_id', $legacyRole->id)
                ->get()
                ->map(fn (object $row) => array_merge((array) $row, ['role_id' => $canonicalRole->id]))
                ->all();

            if ($modelRoleRows !== []) {
                DB::table($modelHasRolesTable)->insertOrIgnore($modelRoleRows);
                DB::table($modelHasRolesTable)->where('role_id', $legacyRole->id)->delete();
            }

            $rolePermissionRows = DB::table($roleHasPermissionsTable)
                ->where('role_id', $legacyRole->id)
                ->get()
                ->map(fn (object $row) => array_merge((array) $row, ['role_id' => $canonicalRole->id]))
                ->all();

            if ($rolePermissionRows !== []) {
                DB::table($roleHasPermissionsTable)->insertOrIgnore($rolePermissionRows);
                DB::table($roleHasPermissionsTable)->where('role_id', $legacyRole->id)->delete();
            }

            DB::table($rolesTable)->where('id', $legacyRole->id)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $rolesTable = $tableNames['roles'];

        DB::table($rolesTable)
            ->where('name', 'LICEmployee')
            ->update(['name' => 'lic-medewerker']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
