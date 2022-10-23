<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ReduceLengthOfIndexColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('media', function ($table) {
            $table->string('model_type', 161)->change();
            $table->longText('manipulations')->change();
            $table->longText('custom_properties')->change();
            $table->longText('generated_conversions')->change();
            $table->longText('responsive_images')->change();
        });
        Schema::table('model_has_permissions', function ($table) {
            $table->string('model_type', 161)->change();
        });
        Schema::table('model_has_roles', function ($table) {
            $table->string('model_type', 161)->change();
        });
        Schema::table('password_resets', function ($table) {
            $table->string('email', 161)->change();
        });
        Schema::table('tenants', function ($table) {
            $table->longText('data')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('media', function ($table) {
            $table->string('model_type', 191)->change();
            $table->json('manipulations')->change();
            $table->json('custom_properties')->change();
            $table->json('generated_conversions')->change();
            $table->json('responsive_images')->change();
        });
        Schema::table('model_has_permissions', function ($table) {
            $table->string('model_type', 191)->change();
        });
        Schema::table('model_has_roles', function ($table) {
            $table->string('model_type', 191)->change();
        });
        Schema::table('password_resets', function ($table) {
            $table->string('email', 191)->change();
        });
        Schema::table('tenants', function ($table) {
            $table->json('data')->change();
        });
    }
}
