# 智慧党建系统 · 核心实现要点与工程规范

## 一、Filament 5 深度实践要点

### 1. 多面板架构组织规范
- 面板 Provider 位于 [app/Providers/Filament/](app/Providers/Filament/)，每个面板均遵循独立配置：
  - `MemberPanelProvider`：路径 `/`，作为**默认面板与全员个人统一工作台**；
  - `BranchPanelProvider`：路径 `/branch`；
  - `CollegePanelProvider`：路径 `/college`；
  - `OrgPanelProvider`：路径 `/org`；
  - `SysadminPanelProvider`：路径 `/sysadmin`。
- **可复用资源的命名空间与隔离**：
  跨面板引用的同类模型（如各端专属的活动、申请、预警资源），放在对应面板的命名空间下（如 [app/Filament/Branch/Resources/Alerts/](app/Filament/Branch/Resources/Alerts/) 与 [app/Filament/Org/Resources/Alerts/](app/Filament/Org/Resources/Alerts/)），严格根据该端业务权限重写 `getEloquentQuery()`、`form()` 和 `table()`，实现天然的权限隔离。

### 2. 交互式 Action 与流程状态机
- **弹窗表单（Modal Schema）收集审核意见**：
  在审批通过/驳回操作中，通过 Action 自带的 `schema()` 声明即席表单（如填写驳回原因、处置说明），避免跳转额外页面，保持操作流畅：
  ```php
  Action::make('reject')
      ->label('驳回')
      ->color('danger')
      ->schema([
          Textarea::make('reason')->label('驳回原因')->required(),
      ])
      ->action(function (array $data, WorkshopActivity $record): void {
          $record->forceFill([
              'status' => '已驳回',
              'reject_reason' => $data['reason'],
              'reviewed_by' => (string) Auth::id(),
              'reviewed_at' => now(),
          ])->save();
      });
  ```
- **权限安全防线（Server-side Authorization）**：
  绝不可仅依赖 Action 的 `visible()` 来控制权限；敏感操作必须配合 Laravel Policy 或在操作回调内部二次校验操作人身份。

---

## 二、异步任务与调度机制（Horizon + Scheduler）

### 1. 预警扫描引擎实现（[app/Jobs/ScanAlertsJob.php](app/Jobs/ScanAlertsJob.php)）
- **幂等写入与防复活原则**：
  - 扫描生成的每条预警均基于业务维度生成全局唯一的 `dedup_key`（例如：`term:{org_id}:{end_date}`、`thought:{case_id}`、`hours:{member_id}:{year}`）；
  - 若 `dedup_key` 已存在且状态为“**未处置**”，则仅刷新其预警级别、内容与 `last_scanned_at`；
  - 若已存在且状态为“**已处置**”或“**已忽略**”，则**严禁复活或重新生成**，确保基层处理结果的严肃性；
  - 不存在时才执行 `Alert::create()`。
- **配置化阈值注入**：
  换届预警提前天数（默认 180）、组织生活提醒日（默认 25）、发展步骤超期天数（默认 30）、预备期提醒天数（默认 30）均读取系统参数（`configs`），未设置时通过代码兜底默认值。

### 2. 调度配置（[routes/console.php](routes/console.php)）
在 Laravel 13 中通过 `Illuminate\Support\Facades\Schedule` 注册周期任务：
```php
// 监督预警扫描：每日 06:00
Schedule::job(new ScanAlertsJob)->dailyAt('06:00')->name('scan-alerts');

// 政治生日推送：每日 08:00
Schedule::job(new PushBirthdayJob)->dailyAt('08:00')->name('push-birthday');

// 三会一课填报提醒：每周一 09:00
Schedule::job(new MeetingReminderJob)->weeklyOn(1, '09:00')->name('meeting-reminder');
```

---

## 三、外部系统对接技术契约

### 1. 党费数据同步接口 (`POST /fee/sync`)
- **控制器实现**：[app/Http/Controllers/FeeSyncController.php](app/Http/Controllers/FeeSyncController.php)
- **安全鉴权**：
  - 请求头必须携带 `X-Fee-Token`；
  - 服务端读取 `configs.fee_sync_token` 进行 `Hash::check()` 比对。
- **党员匹配机制（两级比对）**：
  1. **首选匹配**：通过 `staff_no`（工号/学号）进行数据库精确等值匹配；
  2. **备选匹配**：若工号为空或未匹配，使用传入的身份证号计算 `hash('sha256', $idCard)`，与系统党员库中解密后的身份证哈希比对。
- **批量容错机制**：
  - 单条数据校验或匹配失败不中断整批任务，采用 Try-Catch 累加 `failed` 与 `errors: [{index, message}]`，最终返回 `{created, updated, failed, errors}` 的明细响应。

---

## 四、自动化测试设计与最佳实践

### 1. 测试体系设计规范
系统采用分层 Feature 测试策略，所有测试均使用 `RefreshDatabase` 确保数据库隔离，并在基类或测试初始化时统一执行 `$this->seed(DatabaseSeeder::class)`，确保基础组织树、字典和测试账号完备。

### 2. 多面板环境模拟踩坑总结
在编写针对非默认面板（如 branch、college、org、sysadmin）的测试时，必须显式调用：
```php
Filament::setCurrentPanel(Filament::getPanel('branch'));
$this->be($branchUser);

// 此时访问该面板路由才能正确获得面板上下文
$this->get('/branch/alerts')->assertSuccessful();
```

### 3. 当前测试用例清单与覆盖范围

| 测试套件文件 | 覆盖业务阶段 | 用例数量 |
|---|---|---|
| [tests/Feature/Phase1CoreSecurityTest.php](tests/Feature/Phase1CoreSecurityTest.php) | 阶段 1：身份解耦、面板准入、组织层级树与撤销预检、身份证加密与脱敏 | 15 个 |
| [tests/Feature/Phase2BranchTest.php](tests/Feature/Phase2BranchTest.php) | 阶段 2：三会一课状态机、考勤核算、发展党员25步推进与材料、手机号变更审核 | 17 个 |
| [tests/Feature/Phase3ResourcesTest.php](tests/Feature/Phase3ResourcesTest.php) | 阶段 3：学员库独立建库、学时三端流转、组织关系转接三级审批、出国境天数分流 | 7 个 |
| [tests/Feature/Phase4ResourcesTest.php](tests/Feature/Phase4ResourcesTest.php) | 阶段 4：工作坊先审后发与归档窗口、敏感词过滤、预警9类扫描幂等、生日推送、党费对接 | 12 个 |
| [tests/Feature/Phase5CockpitAndAdminTest.php](tests/Feature/Phase5CockpitAndAdminTest.php) | 阶段 5：4 端专属驾驶舱 Widget、系统管理员访问控制、审计日志只读、字典参数维护 | 10 个 |
| [tests/Feature/MemberPersonalPortalTest.php](tests/Feature/MemberPersonalPortalTest.php) | 个人工作台：全员统一工作台、党费明细、手机号变更申请、入党发展纪实、非党员干部学时 | 7 个 |
| **全量总计** | **6 个核心测试套件，共 68 个测试用例，283 个断言，100% 通过** | |

---

## 五、环境配置与部署运维指南

### 1. 基础环境检查
```bash
php -v          # 需 PHP 8.4+
composer -V     # 需 Composer 2.x
```

### 2. 项目部署初始化
```bash
# 1. 安装项目依赖
composer install --no-dev --optimize-autoloader

# 2. 生成应用密钥与环境配置
cp .env.example .env
php artisan key:generate

# 3. 运行数据库迁移与初始种子播种
php artisan migrate --force
php artisan db:seed --force

# 4. 建立存储软链接
php artisan storage:link

# 5. 编译前端资源
npm run build
```

### 3. 队列守护与定时任务守护（Production）
- **Crontab 定时调度配置**：
  ```cron
  * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
  ```
- **Supervisor 守护 Horizon 队列**：
  ```ini
  [program:party-horizon]
  process_name=%(program_name)s
  command=php /path-to-project/artisan horizon
  autostart=true
  autorestart=true
  user=www-data
  redirect_stderr=true
  stdout_logfile=/path-to-project/storage/logs/horizon.log
  stopwaitsecs=3600
  ```
