<?php

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default(Role::User->value)->after('email')->index();
        });

        $this->backfillRolesFromPermissionTables();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    private function backfillRolesFromPermissionTables(): void
    {
        $tableNames = config('permission.table_names');
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $modelMorphKey = config('permission.column_names.model_morph_key', 'model_id');

        if (! Schema::hasTable($rolesTable) || ! Schema::hasTable($modelHasRolesTable)) {
            return;
        }

        $rolePriority = [
            Role::Admin->value => 3,
            Role::LicEmployee->value => 2,
            Role::User->value => 1,
        ];

        DB::table('users')
            ->select('users.id')
            ->orderBy('users.id')
            ->chunkById(100, function ($users) use ($modelHasRolesTable, $modelMorphKey, $rolePriority, $rolesTable): void {
                foreach ($users as $user) {
                    $role = DB::table($modelHasRolesTable)
                        ->join($rolesTable, $rolesTable.'.id', '=', $modelHasRolesTable.'.role_id')
                        ->where($modelHasRolesTable.'.'.$modelMorphKey, $user->id)
                        ->where($modelHasRolesTable.'.model_type', 'like', '%User')
                        ->pluck($rolesTable.'.name')
                        ->sortByDesc(fn (string $role): int => $rolePriority[$role] ?? 0)
                        ->first();

                    $selectedRole = is_string($role) && isset($rolePriority[$role])
                        ? $role
                        : Role::User->value;

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['role' => $selectedRole]);
                }
            });
    }
};
