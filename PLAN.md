# 智慧党建系统 · 实施规划

## 一、总体架构决策

| 决策点 | 方案 | 理由 |
|---|---|---|
| 面板形态 | **多面板**：5 个独立 Filament 面板，**member 为全员个人统一工作台（默认面板）**，所有个人业务收敛在此；其他 4 个面板纯粹承担管理功能 | 个人端与管理端二元化切分：个人业务与各级审批管理天然隔离；一人多身份通过跨面板跳转解决 |
| 身份模型 | `User`（基础账号：工号/姓名/邮箱，登录认证）↔ `Member`（党员档案：详细信息）**一一对应（HasOne，双向）**；党员相关业务通过 `member_id` 关联获取 | 账号与档案解耦：未开通账号的党员也可先建档；党外干部与协同员依托 Trainee 同样可在个人工作台享受服务 |
| 面板准入 | 按身份记录控制：branch/college 凭**在任委员**（`CommitteeMember`），org 凭**在职组织部工作人员**（`OrgStaff`），sysadmin 凭**启用管理员**（`Admin`） | 委员会职务可配置（党支部书记/组织委员/党委书记等），任命方式可配置（选举/指派）；在任状态（离任/任期到期）自动失去面板权限 |
| 权限模型 | 面板准入（身份记录）× 角色授权（`RoleGrant`，含数据范围组织）× Laravel Policy | 授权必须走 Policy，不能只靠隐藏 UI；无角色授权时数据范围回退到在任委员所在组织 |
| 数据范围 | Eloquent 全局作用域 / 专用 Query Scope，按当前面板对应身份收敛 | demo 契约明确"数据范围由后端收敛"，Filament 表格查询统一走 scope |
| 队列 | Horizon（已配置） | 预警扫描、政治生日推送、站内通知均为周期/异步任务 |
| 导入导出 | Filament Import/Export Action（OpenSpout，已随 Filament 安装） | 对应 demo 的 xlsx 导入导出（党员名册、学员名册、学时、台账） |
| 敏感字段 | 身份证号 `encrypted` cast + 展示脱敏中间件/访问器 | demo 硬性规则：身份证加密落库、列表脱敏、仅组织部/分党委可见全号 |
| 前端 | Filament 原生组件（表格/表单/Infolist/Widget），党建红主题 | 不引入 React 门户；驾驶舱用 Filament Widget 实现 |

### 身份与面板准入映射（demo → 本系统）

| demo 角色 | 面板 | 准入依据（新架构） | 数据范围 |
|---|---|---|---|
| `member` 全员个人 | **个人统一工作台（`/member`，默认工作台）** | 所有登录用户（党员、积极分子、非党员干部、普通教工） | 本人个人业务 |
| `branch` 支部管理员 | 党支部管理面板（`/branch`） | 关联党员在任**党支部委员会委员**（`CommitteeMember`，组织 level=3） | 委员会所在支部及下级 |
| `college` 分党委管理员 | 二级党委管理面板（`/college`） | 关联党员在任**党委委员会委员**（`CommitteeMember`，组织 level=2） | 委员会所在院级组织及下属支部 |
| `training_coordinator` 培训协同员 | 个人工作台 / 二级党委协同 | 角色授权（`RoleGrant`） | 个人业务在 member；协同提交在 college 裁剪 |
| `org_dept` 组织部管理员 | 党委组织部管理面板（`/org`） | **在职组织部工作人员**（`OrgStaff`） | 全校党务管理 |
| `super_admin` 超级管理员 | 系统管理员管理面板（`/sysadmin`） | **启用系统管理员**（`Admin`） | 全校 + 系统底层运维 |

委员会职务可配置（字典 `committee_branch_position` / `committee_college_position`）：党支部书记、党支部副书记、组织委员、宣传委员、纪检委员、青年委员；党委书记、党委副书记等。任命方式可配置（字典 `appointment_method`）：选举、指派。在任状态：`status=在任` 且 `term_end` 为空或未到期；离任/到期自动失去面板权限与数据范围。

层级授权链（仅对仍保留角色授权的协同员等场景生效，只能向下授予、不越级）：

```
super_admin ─▶ org_dept ─▶ college ─▶ branch ─▶ member
                              └─────▶ training_coordinator ─▶ member
```

### 多面板核心机制

1. **面板即身份上下文**：member 为默认面板（所有登录用户可进）；其余面板按身份记录准入——`canAccessPanel(Panel $panel)` 校验在任委员/在职工作人员/启用管理员记录。
2. **一人多身份**：用户菜单提供"切换工作台"入口，按本人身份记录列出可进面板，跳转到对应面板 URL（`/member`、`/branch`、`/college`、`/org`、`/sysadmin`）；无需会话级活动身份状态，权限与数据范围随面板天然确定。
3. **资源共享**：跨面板复用的资源（如 `MemberResource`）通过 Filament 5 **可配置资源**（`MemberResource::make('branch')`）或 **Plugin 类**（`Panel::configureUsing()` + `match ($panel->getId())`）按面板差异化注册——不同面板可裁剪列、表单字段、Action 与导航标签。
4. **培训协同员**：不设独立面板，进入二级党委面板后仅看到学时提交相关导航（`trainee:read` 不授予，名册不可见）。
5. **测试**：非默认面板测试需 `Filament::setCurrentPanel('branch')` 指定面板。

---

## 二、功能分解矩阵（demo → 五大模块）

图例：`●` 主要操作方　`○` 可查看/参与　`—` 不可见

### 1. 党员个人（`member`）

| 功能（demo 来源） | 说明 |
|---|---|
| 认证与工作台切换（`auth`） | 登录、改密码、跨面板切换工作台 |
| 个人档案（`members/{id}`） | 本人信息只读，身份证脱敏 |
| 手机号变更申请（`phone-change-request`） | 提交申请 → 支部审核生效，全程留痕 |
| 个人培训档案（`hours/my-profile`） | 网络自学/集中培训分组统计、区间筛选，仅"已生效" |
| 培训报名与签到（`trainings/enroll`、`checkin`） | 报名培训项目、扫码签到 |
| 三会一课扫码签到（`meetings/sign-in`） | 保留能力，台账不强制 |
| 组织关系转接申请（`transfers` apply） | 本人发起，跟踪审批进度 |
| 因私出国（境）申请（`abroad` apply） | 提交申请、延期申请、查看审批轨迹 |
| 政治生日（`birthdays/today`） | 当日提醒（通知推送） |
| 党费记录（`fees`） | 本人交纳记录只读 |
| 工作坊参与（`workshop`） | 问答发帖/回帖/标记解决、消息通知、订阅推送 |
| 学时查看（`hours/summary`） | 本人学时流水 |

### 2. 党支部（`branch`）

| 功能 | 说明 |
|---|---|
| 党员信息库（本支部） | CRUD、xlsx 导入导出、统计；身份证脱敏 |
| 三会一课台账 | 新建（应到自动统计）、考勤录入、附件（照片/PDF）、提交院级审核、退回修改 |
| 党员发展 | 建档（自动生成 25 步）、逐步完成+材料上传、终止、档案导出 |
| 组织关系转接 | 支部代录、初审（`review-branch`） |
| 因私出国（境） | 初审、销假、定期联系记录 |
| 手机号变更审核 | `member:review-phone` |
| 政治生日 | 本支部名单、导出 |
| 培训协同 | 支部级报名审核（`review-branch`）、学时提交（协同端能力） |
| 预警处置 | 本支部预警查看与处置 |
| 角色授予 | 仅可授予党员角色 |

### 3. 二级党委（`college`）

| 功能 | 说明 |
|---|---|
| 组织管理（本院） | 下属支部维护、委员名单、换届时间与到期预警；组织编码只读 |
| 党员信息库（本院） | CRUD、导入导出；**身份证全号可见** |
| 三会一课审核 | 院级审核（归档/退回，原因必填）、本院台账统计与导出 |
| 党员发展 | 本院案件监督、进度统计、档案导出 |
| 培训管理 | 发布院级培训、college 级报名审核、学时提交 |
| 组织关系转接 | 院级审批（`review-college`） |
| 因私出国（境） | 院级审批、延期审批（`review-college`） |
| 党务工作坊 | 活动登记发布（公开活动送审）、跨院报名审核、总结归档（7 日内）、问答参与、单位统计 |
| 预警处置 | 本院范围 |
| 角色授予 | 支部管理员、党员 |
| 院级统计 | 本院口径驾驶舱 |

### 4. 党委组织部（`org_dept`）

| 功能 | 说明 |
|---|---|
| 全校组织管理 | 三级组织树、**组织编码编辑（独占 `org:write-code`）**、撤销/合并预检（有在册党员拦截）、名称沿革、软删除 |
| 全校党员信息库 | 全字段 CRUD、导入导出、多维统计、数据来源标记 |
| 党员发展监督 | 全校案件、阶段/超期统计、标准化电子档案 |
| 三会一课 | 全校台账导出、按支部/类型统计 |
| 培训·管理端 | 学员管理（独立建库，含党外干部）、课程维护、发布校级培训、**学时直接录入即时生效**、审核协同端提交、批量录入、xlsx 导入导出、年度学时标准、全员学时总览与档案 |
| 党务工作坊·管理 | 公开活动审核、资源链接维护（分类/置顶/排序）、问答置顶/隐藏/官方回复、全校统计 |
| 监督预警 | 9 类规则扫描（手动+定时）、分级处置、党建任务督办 |
| 政治生日 | 全校名单、推送台账与回写 |
| 党费 | 全校汇总、交纳率、**党费系统同步接口（只读对接）** |
| 驾驶舱 | 全校口径全部指标 |
| 角色授予 | 分党委管理员、培训协同员、支部管理员、党员 |

### 5. 系统管理员（`super_admin`）

| 功能 | 说明 |
|---|---|
| 全部组织部能力 | super_admin ⊇ org_dept |
| 账号管理 | 新建/修改/删除账号、重置密码、SSO 标识绑定 |
| 角色授权总控 | 授予组织部管理员及以下全部角色、授权流水审计、主角色设置 |
| 数据字典 | `dicts` 维护（学历、干部类型、培训形式等） |
| 系统参数 | `configs`（含工作坊敏感词表） |
| 审计日志 | 登录、增删改、审批、同步全留痕查询 |
| 组织部管理员授予 | 唯一可授予 `org_dept` 的角色 |

---

## 三、数据模型规划

### 3.1 组织与人员

| 模型 | 表 | 要点 |
|---|---|---|
| `Organization` | `organizations` | 三级树（`parent_id`/`level`/`path`）、`code` 唯一、`member_type`(tea/stu/mix) 派生 `nature`、`status`(正常/撤销/合并)、换届字段（`term_start_date`+`term_years` 推算到期）、名称沿革表 `organization_name_histories` |
| `Member` | `members` | **主键 = 工号/学号（字符串）**；23+ 字段对齐 demo；`id_card` **encrypted + 应用层唯一校验**（加密后无法用数据库唯一约束）；`user_id` 可空唯一（关联账号，一一对应）；校验：入党−出生≥18 年、转正−入党≥1 年（不满足须 `special_note`）；`data_source` 标记 |
| `User` | `users` | **主键 = 工号/学号（字符串）**；基础账号：`name`/`email`/`phone`/`sso_subject`/`member_id` 可空唯一；实现 `FilamentUser`；与 `Member` **双向 HasOne**（`users.member_id` ↔ `members.user_id`） |
| `CommitteeMember` | `committee_members` | 委员会成员（支部/院级党委共用，按组织 `level` 区分）：`organization_id`×`member_id`×`position`（职务可配置）×`appointment_method`（选举/指派）×`term_start`/`term_end`（空=本届在任）×`status`（在任/离任）；控制 branch/college 面板准入 |
| `OrgStaff` | `org_staff` | 组织部工作人员：`user_id` 唯一×`name`×`position`×`status`（在职/离职）；控制 org 面板准入 |
| `Admin` | `admins` | 系统管理员：`user_id` 唯一×`name`×`status`（启用/停用）；控制 sysadmin 面板准入 |
| `RoleGrant` | `role_grants` | `user_id`×`role_code`×`org_id`（数据范围）×`is_primary`×`status`；授权流水即本表 + 审计日志；新架构下仅培训协同员等场景使用 |
| `PhoneChangeRequest` | `phone_change_requests` | 申请→支部审核→生效，留痕 |

### 3.2 核心业务

| 模型 | 表 | 要点 |
|---|---|---|
| `DevelopmentCase` / `DevelopmentStep` | `development_cases` / `development_steps` | 五阶段 25 步骤（3/6/6/6/4）预置定义；步骤完成自动推进；材料表 `development_materials`；终止与归档 |
| `Meeting` | `meetings` | 6 类会议、状态机（草稿→已提交→已归档/退回修改）、考勤表 `meeting_attendees`（应到自动统计）、附件表、留痕表 `meeting_logs`；年度计划表 `meeting_plans` |
| `Training` / `TrainingEnrollment` | `trainings` / `training_enrollments` | 校级/院级/支部三级发布；两级审批（branch→college）；动态签到码 |
| `Course` | `courses` | 课程资源 |
| `Trainee` | `trainees` | **独立于党员库**（`member_id` 可空），党外干部可纳入 |
| `TrainingHour` | `training_hours` | 学时流水：`training_form` 决定网络自学/集中培训口径；状态（待审核/已生效/已驳回）；`hour:write` 直接生效、`hour:submit` 待审核 |
| `HourStandard` | `hour_standards` | 年度学时标准 |
| `Transfer` | `transfers` | 转入/转出、三级审批轨迹、办结生成介绍信编号 |
| `AbroadApplication` | `abroad_applications` | 按天数自动判定审批路径（<6 月/≥6 月）；延期表、联系记录表 |
| `FeeRecord` | `fee_records` | 只读对接：`fee:sync` 接收推送（按工号/身份证匹配） |

### 3.3 工作坊与监督

| 模型 | 表 | 要点 |
|---|---|---|
| `WorkshopActivity` / `WorkshopEnrollment` | `workshop_activities` / `workshop_enrollments` | 公开活动先审后发；状态由审核+时间推演（草稿/待审核/已驳回/已发布/报名中/进行中/已结束）；总结归档限结束后 7 日内 |
| `WorkshopLink` | `workshop_links` | 分类、置顶、排序、点击计数 |
| `WorkshopPost` / `WorkshopReply` | `workshop_posts` / `workshop_replies` | 标签必填单选、敏感词过滤（读 `configs`）、最佳答案、置顶/隐藏 |
| `Notification` | `notifications` | 站内消息（Laravel database channel），未读角标 |
| `Alert` | `alerts` | 9 类规则、三级（提示/警告/严重）、处置/忽略；扫描任务入 Horizon |
| `SupervisionTask` | `supervision_tasks` | 党建任务督办 |
| `BirthdayPush` | `birthday_pushes` | 政治生日推送台账与回写 |

### 3.4 系统管理

| 模型 | 表 | 要点 |
|---|---|---|
| `Dict` / `Config` | `dicts` / `configs` | 数据字典、系统参数（敏感词等） |
| `AuditLog` | `audit_logs` | 登录/增删改/审批/同步留痕 |

---

## 四、权限与数据范围实现

1. **权限注册表**：`app/Support/Permissions.php` 定义 96 项权限码（对齐 demo `permissions.py`），按角色预置映射。
2. **Policy 层**：每个资源一个 Policy，`before()` 检查 `super_admin`；权限点检查当前面板对应角色的授权。
3. **面板准入（新架构）**：`canAccessPanel(Panel $panel)` 按身份记录校验——member 默认放行；branch/college 校验关联党员在任委员记录（组织 level 3/2）；org 校验在职 `OrgStaff`；sysadmin 校验启用 `Admin`。数据范围：有角色授权按授权 `org_id` 收敛；无授权时回退到在任委员所在组织及全部下级（`scopedOrgIds()`）。
4. **数据范围 Scope**：`Member::scopeVisibleTo($query, array $orgIds)`、`Meeting::scopeVisibleTo()` 等，统一按 `scopedOrgIds()` 收敛（全校/本院含下级/本支部/本人）。Filament 资源的 `getTableQuery()` 与 Widget 统一走此 scope。
5. **导航收敛**：Filament `navigationItems ->visible(fn)` 按权限显隐；但服务端 Policy 是唯一防线。
6. **培训协同员特例**：`trainee:read` 不授予协同端，仅开放按工号精确查询（`lookup`），二级党委面板内按权限裁剪页面。

---

## 五、Filament 结构规划（多面板）

```
app/
├── Filament/
│   ├── MemberPanelProvider.php          # 党员个人面板 /member：个人档案、申请、签到、问答
│   ├── BranchPanelProvider.php          # 党支部面板 /branch：本支部党员库、三会一课、发展、初审
│   ├── CollegePanelProvider.php         # 二级党委面板 /college：本院组织/党员、审核、工作坊发布
│   ├── OrgPanelProvider.php             # 党委组织部面板 /org：全校管理、培训管理端、预警、驾驶舱
│   ├── SysAdminPanelProvider.php        # 系统管理员面板 /sysadmin：账号授权、字典参数、审计
│   ├── Pages/Dashboard.php              # 各面板独立驾驶舱（Widget 集按面板配置）
│   ├── Widgets/                         # 驾驶舱统计 Widget（党员构成/组织分布/发展态势/学时/党费/预警）
│   ├── Resources/                       # 共享资源层（跨面板复用，按面板差异化注册）
│   │   ├── OrganizationResource/        # 组织树、委员 RelationManager、沿革 RelationManager
│   │   ├── MemberResource/              # 23 字段表单、导入导出、手机号变更审核、统计 Widget
│   │   ├── DevelopmentCaseResource/     # 25 步骤 Infolist 时间线、材料上传、档案导出
│   │   ├── MeetingResource/             # 台账、考勤 RelationManager、附件、审核流 Action
│   │   ├── TrainingResource/            # 培训发布、报名审批、签到码
│   │   ├── TraineeResource/             # 学员管理（含党外干部）
│   │   ├── TrainingHourResource/        # 三端学时（管理端/协同端/个人档案页）
│   │   ├── TransferResource/            # 转接审批流
│   │   ├── AbroadApplicationResource/   # 出国境审批流、延期、联系记录
│   │   ├── FeeRecordResource/           # 党费只读 + 同步端点
│   │   ├── WorkshopActivityResource/    # 活动发布/审核/归档
│   │   ├── WorkshopLinkResource/        # 链接管理
│   │   ├── WorkshopPostResource/        # 问答管理
│   │   ├── AlertResource/               # 预警处置
│   │   ├── SupervisionTaskResource/     # 任务督办
│   │   ├── BirthdayResource/            # 政治生日名单与推送台账
│   │   ├── UserResource/                # 账号与角色授权（系统管理员）
│   │   ├── DictResource/ ConfigResource/ # 字典与参数
│   │   └── AuditLogResource/            # 审计日志（只读）
│   └── Plugins/                         # 按面板注册资源组合（Plugin 类）
│       ├── MemberPanelPlugin.php        # 党员面板：个人档案、申请、问答（只读+本人业务）
│       ├── BranchPanelPlugin.php        # 支部面板：党员库(本支部)、台账、发展、初审
│       ├── CollegePanelPlugin.php       # 分党委面板：本院管理、审核、工作坊
│       ├── OrgPanelPlugin.php           # 组织部面板：全校管理、培训端、预警、党费
│       └── SysAdminPanelPlugin.php      # 系统面板：账号授权、字典参数、审计
└── Support/Permissions.php              # 96 项权限码注册表
```

**跨面板资源共享模式**（Filament 5 可配置资源 / Plugin）：

```php
// Plugin 内按面板注册同一资源，差异化配置
public function register(Panel $panel): void
{
    $panel->resources([
        MemberResource::make('branch')      // 支部面板：身份证脱敏、限本支部
            ->navigationLabel('党员信息库'),
        MemberResource::make('org')         // 组织部面板：全字段、全校、含导入导出
            ->navigationLabel('党员信息库'),
    ]);
}

// PanelProvider 中按面板 ID 装配
Panel::configureUsing(function (Panel $panel): void {
    match ($panel->getId()) {
        'branch' => $panel->plugin(BranchPanelPlugin::make()),
        'college' => $panel->plugin(CollegePanelPlugin::make()),
        'org' => $panel->plugin(OrgPanelPlugin::make()),
        'sysadmin' => $panel->plugin(SysAdminPanelPlugin::make()),
        'member' => $panel->plugin(MemberPanelPlugin::make()),
        default => null,
    };
});
```

### 面板与功能矩阵

| 面板 | 路径 | 准入依据 | 主要资源 | 独有能力 |
|---|---|---|---|---|
| 党员个人（默认） | `/member` | 所有登录用户 | 个人档案、我的申请、我的学时、工作坊问答 | 扫码签到、手机号变更申请 |
| 党支部 | `/branch` | 在任党支部委员 | 党员库(本支部)、三会一课、发展案件、转接/出国境 | 台账填报、考勤录入、25 步推进 |
| 二级党委 | `/college` | 在任党委委员 | 本院组织/党员、审核队列、工作坊活动 | 院级审核、活动发布、跨院报名 |
| 党委组织部 | `/org` | 在职组织部工作人员 | 全校组织/党员、培训管理端、预警、党费、驾驶舱 | 组织编码编辑、学时直录、活动审核、链接/问答管理 |
| 系统管理员 | `/sysadmin` | 启用系统管理员 | 账号授权、字典、参数、审计日志 | 角色授权总控、敏感词配置 |

### 队列任务（Horizon）

| Job | 调度 | 说明 |
|---|---|---|
| `ScanAlertsJob` | 每日 | 9 类预警规则扫描（换届到期、组织生活、发展超期、材料缺失、思想汇报、条件不符、预备期到期、学时未达标、任务督办） |
| `PushBirthdayJob` | 每日 08:00 | 当日政治生日推送 + 台账 |
| `MeetingReminderJob` | 每周 | 月度/长期未填报三会一课提醒 |
| 通知类 Job | 事件驱动 | 工作坊审核/报名/回复等站内消息 |

---

## 六、分阶段实施计划

### 阶段 1：地基（组织 + 人员 + 权限 + 面板骨架）✅ 已完成
- 5 个面板 Provider 创建（`/member`、`/branch`、`/college`、`/org`、`/sysadmin`）、党建红主题；member 为默认面板
- `User` 实现 `FilamentUser`：`canAccessPanel($panel)` 按身份记录校验（在任委员/在职工作人员/启用管理员）
- 迁移：`organizations`（含沿革）、`members`、`role_grants`、`dicts`、`configs`、`audit_logs`、`committee_members`、`org_staff`、`admins`；`users`/`members` 字符串主键（工号/学号）
- 权限注册表 + 数据范围 Scope（`scopedOrgIds()` 含委员会回退）
- `OrganizationResource`（树、编码权限、撤销预检）、`MemberResource`（校验规则、加密脱敏）
- 账号管理与角色授权（`UserResource` + 授权三重校验）
- **验收**：五类身份登录进入各自面板；member 默认面板；离任/到期/离职/停用自动失去面板权限；身份证加密脱敏

### 阶段 2：党支部核心业务
- 三会一课全流程（台账→考勤→附件→提交→院级审核→归档/退回）
- 党员发展五阶段 25 步骤（建档、推进、材料、终止、档案导出）
- 手机号变更审核流
- **验收**：支部建台账、分党委审核归档；发展案件 25 步推进与超期提示

### 阶段 3：培训与人员流动 ✅ 已完成
- 学员管理（独立建库、导入导出、协同端 lookup 限制）
- 培训发布、两级报名审批、签到码
- 学时三端（管理端直录生效 / 协同端提交待审 / 个人档案）+ 学时标准 + 导入导出
- 组织关系转接（三级审批、办结编号）
- 因私出国（境）（按天数分流审批、延期、销假、联系记录）
- **验收**：协同端看不到名册；学时口径（网络自学/集中培训）正确；转接/出国境流程闭环（`tests/Feature/Phase3ResourcesTest.php` 7 用例覆盖）

### 阶段 4：工作坊与监督 ✅ 已完成
- 党务工作坊（活动先审后发、跨院报名、7 日归档、链接、问答+敏感词过滤、站内消息）
- 监督预警（9 类规则 + Horizon 扫描 Job + 处置与忽略幂等）、党建任务督办
- 政治生日（名单导出、推送 Job、台账、站内祝福）
- 党费只读对接（`fee/sync` 端点 + 令牌鉴权 + sha256 身份证与工号匹配 + 汇总）
- **验收**：公开活动先审后发；预警扫描生成与处置；生日推送留痕（`tests/Feature/Phase4ResourcesTest.php` 12 用例覆盖）

### 阶段 5：驾驶舱与收尾 ✅ 已完成
- 驾驶舱 Widget 集（`BranchOverviewWidget`、`CollegeOverviewWidget`、`OrgOverviewWidget`、`MemberOverviewWidget`，严格按角色 `scopedOrgIds()` 收敛口径）
- 系统管理员面板（`/sysadmin`：`AuditLogResource` 只读查询、`DictResource` 数据字典管理、`ConfigResource` 系统参数管理、`UserResource` 账号与授权总控）
- 全量 Feature 测试（覆盖各阶段核心业务、Policy、数据范围、状态机流转与审计联动，`tests/Feature/Phase5CockpitAndAdminTest.php` 10 用例覆盖，全套测试 61 用例、256 断言）
- Pint 格式化与回归验证全部通过，系统具备完整投产标准

### 架构演进与优化：个人默认工作台（Member Portal）全业务统筹 ✅ 已完成
- **定位革新**：`/member` 作为默认工作台，彻底升级为“个人全员统一工作台”（Personal Portal），覆盖正式党员、预备党员、入党积极分子/申请人、非党员中层干部（学员库 Trainee）、普通教职工等所有个人业务；其他 4 个面板纯粹承担管理功能（Management Portal）。
- **个人业务资源补齐**：
  - `FeeRecordResource`：个人党费交纳流水只读查询；
  - `PhoneChangeRequestResource`：个人自主提报联系电话变更申请；
  - `DevelopmentCaseResource`：入党申请人/积极分子/预备党员查看本人五阶段 25 步骤全纪实与材料待办；
  - `WorkshopActivityResource`：全员浏览全校已发布公开工作坊活动广场；
  - `TrainingHourResource` / `TrainingProfileWidget`：全面兼容 Trainee 干部培训学员档案，支持党外中层干部查询个人培训学时；
  - `MemberOverviewWidget`：自适应个人身份画像（党员画像、党外干部画像、积极分子发展画像、普通教工画像）。
- **质量保障**：新增 `tests/Feature/MemberPersonalPortalTest.php`（7 个测试用例，27 个断言），全量测试套件达到 **68 个测试用例，283 个断言，100% 通过**。
