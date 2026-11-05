<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 50)->change()->comment('姓名');
            $table->string('phone', 30)->nullable()->after('password');
            $table->string('sso_subject', 100)->nullable()->unique()->after('phone')->comment('统一身份认证标识');
            $table->string('member_id', 30)->nullable()->unique()->after('sso_subject')->comment('关联党员档案（members.id，一一对应）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['member_id']);
            $table->dropColumn(['member_id', 'phone', 'sso_subject']);
        });
    }
};
