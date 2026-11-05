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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('组织编码，仅组织部可编辑');
            $table->string('short_name', 100)->comment('简称');
            $table->string('full_name', 200)->comment('全称');
            $table->foreignId('parent_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->unsignedTinyInteger('level')->comment('层级：1=学校党委 2=院级党组织 3=党支部');
            $table->string('path', 255)->default('/')->comment('物化路径 /1/12/123/');
            $table->string('secretary', 64)->nullable()->comment('党组织书记');
            $table->string('contact_person', 64)->nullable()->comment('党组织联系人');
            $table->string('contact_phone', 32)->nullable()->comment('联系电话');
            $table->date('founded_date')->nullable()->comment('成立日期');
            $table->date('term_start_date')->nullable()->comment('本届换届起点');
            $table->unsignedTinyInteger('term_years')->nullable()->comment('届期（年）');
            $table->date('term_end_date')->nullable()->comment('换届到期日（推算）');
            $table->string('nature', 20)->nullable()->comment('组织性质：教工/学生/混合党支部');
            $table->string('category', 50)->nullable()->comment('类别');
            $table->string('member_type', 10)->nullable()->comment('tea/stu/mix，仅支部层');
            $table->string('status', 20)->default('正常')->comment('正常/撤销/合并');
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('remark')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'status']);
            $table->index('level');
        });

        Schema::create('organization_committees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('position', 50)->comment('职务：书记/副书记/委员');
            $table->string('phone', 30)->nullable();
            $table->date('term_start')->nullable();
            $table->date('term_end')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('organization_name_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('old_name', 200);
            $table->string('new_name', 200);
            $table->date('changed_on')->comment('变更日期');
            $table->string('changed_by', 30)->nullable()->comment('操作人账号（users.id）');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_name_histories');
        Schema::dropIfExists('organization_committees');
        Schema::dropIfExists('organizations');
    }
};
