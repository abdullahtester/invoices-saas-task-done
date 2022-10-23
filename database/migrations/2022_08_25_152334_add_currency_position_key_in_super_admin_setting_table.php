<?php

use App\Models\SuperAdminSetting;
use Illuminate\Database\Migrations\Migration;

class AddCurrencyPositionKeyInSuperAdminSettingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
           SuperAdminSetting::create([
              'key'   => 'currency_after_amount',
              'value' => SuperAdminSetting::CURRENCY_AFTER_AMOUNT,
           ]);
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
