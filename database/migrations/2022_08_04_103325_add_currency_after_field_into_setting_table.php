<?php

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

class AddCurrencyAfterFieldIntoSettingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', Role::ROLE_ADMIN);
        })->get();

        /** @var User $admin */
        foreach ($admins as $admin) {
            $userTenantId = $admin->tenant_id;

            Setting::create([
                'key' => 'currency_after_amount',
                'value' => '1',
                'tenant_id' => $userTenantId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
