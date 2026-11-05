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
        Schema::create('members', function (Blueprint $table) {
            $table->string('id')->primary()->comment('工号/学号');
            $table->string('user_id', 30)->nullable()->unique()->comment('关联账号（users.id），一一对应，可空表示暂未开通账号');
            $table->string('name', 50)->comment('姓名');
            $table->string('gender', 10)->nullable();
            $table->string('ethnicity', 30)->nullable()->comment('民族');
            $table->date('birth_date')->nullable();
            $table->string('id_card', 128)->unique()->comment('身份证号，加密存储');
            $table->string('staff_no', 30)->unique()->comment('工号/学号');
            $table->foreignId('org_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('parent_org_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('relation_in_school', 30)->nullable()->comment('在校关系：教职工/学生');
            $table->date('join_party_date')->nullable()->comment('入党时间');
            $table->date('full_member_date')->nullable()->comment('转正时间');
            $table->string('identity', 30)->nullable()->comment('身份：正式党员/预备党员/入党积极分子等');
            $table->string('education', 30)->nullable()->comment('学历');
            $table->string('degree', 30)->nullable()->comment('学位');
            $table->string('native_place', 100)->nullable()->comment('籍贯');
            $table->string('source_place', 100)->nullable()->comment('来源地');
            $table->string('cultivation_level', 30)->nullable()->comment('培养层次');
            $table->string('grade', 20)->nullable()->comment('年级');
            $table->date('arrive_school_date')->nullable()->comment('来校日期');
            $table->string('professional_title', 50)->nullable()->comment('职称');
            $table->string('professional_title_level', 30)->nullable()->comment('职称等级');
            $table->string('phone', 30)->nullable();
            $table->string('work_unit', 100)->nullable()->comment('工作单位');
            $table->string('status', 20)->default('正常')->comment('正常/停止党籍/开除党籍/死亡/转出等');
            $table->string('member_category', 30)->nullable()->comment('党员类别');
            $table->text('special_note')->nullable()->comment('特殊备注（校验不满足时必填）');
            $table->string('data_source', 30)->default('初始导入')->comment('数据来源：初始导入/外部同步/手工录入');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['org_id', 'status']);
            $table->index('name');
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
