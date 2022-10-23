<?php

use App\Models\SuperAdminSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHomePageSupportLinkToSuperAdminSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('super_admin_settings', function (Blueprint $table) {
            SuperAdminSetting::create([
                'key'   => 'home_page_support_link',
                'value' => null,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('super_admin_settings', function (Blueprint $table) {
            //
        });
    }
}
