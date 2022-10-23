<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

class AddFieldCountryCodeInSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $admins = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('name', \App\Models\Role::ROLE_ADMIN);
        })->get();

        /** @var User $admin */
        foreach ($admins as $admin) {
            $userTenantId = $admin->tenant_id;
            $invoiceNoPrefixExists = Setting::where('key', 'country_code')
                ->where('tenant_id', $userTenantId)->exists();
            if (! $invoiceNoPrefixExists) {
                Setting::create([
                    'key'       => 'country_code',
                    'value'     => '+91',
                    'tenant_id' => $userTenantId,
                ]);
            }
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \App\Models\Setting::where('key', 'country_code')->delete();
    }
}
