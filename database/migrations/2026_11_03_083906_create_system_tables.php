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
        Schema::create('dicts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->comment('字典类型：education/identity/member_category 等');
            $table->string('key', 50)->comment('键');
            $table->string('label', 100)->comment('中文标签');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['type', 'key']);
        });

        Schema::create('configs', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('参数键，如 workshop_sensitive_words');
            $table->text('value')->nullable();
            $table->string('name', 100)->comment('参数名称');
            $table->string('remark', 200)->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 30)->nullable()->comment('操作人账号（users.id）');
            $table->string('action', 50)->comment('login/create/update/delete/approve/sync 等');
            $table->string('subject_type', 100)->nullable()->comment('模型类');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable()->comment('变更快照');
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('configs');
        Schema::dropIfExists('dicts');
    }
};
