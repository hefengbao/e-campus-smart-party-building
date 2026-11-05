# 智慧党建系统技术与业务文档

本文档库为智慧党建系统（SmartPartyBuilding）的技术架构设计、业务功能规划、实现要点及运维指南，供系统后续开发、迭代及运维参考。

---

## 一、文档导航

| 文档 | 核心内容 | 适用对象 |
|---|---|---|
| [docs/architecture.md](docs/architecture.md) | 总体技术架构、五面板隔离机制、双轨身份模型、数据范围（DataScope）收敛算法与鉴权体系 | 架构师、全栈开发人员 |
| [docs/modules.md](docs/modules.md) | 基层党务十大业务模块规划、业务状态机、流程闭环规范及驾驶舱指标口径 | 业务分析师、前后端开发人员 |
| [docs/implementation-guide.md](docs/implementation-guide.md) | Filament 5 实操模式、队列与调度机制、安全与敏感数据规范、自动化测试基线及运维部署指南 | 后端开发、测试人员、运维工程师 |

---

## 二、项目核心技术栈

- **服务端运行环境**：PHP 8.4+
- **核心框架**：Laravel 13.x
- **管理后台架构**：Filament 5.x（多面板原生驱动，涵盖 Form、Table、Action、Infolist、Widget）
- **关系型数据库**：SQLite（本地/测试环境） / MySQL 8.0+（生产环境）
- **异步队列与进程管理**：Laravel Horizon + Redis
- **代码规范工具**：Laravel Pint（PSR-12 / Laravel 预置规则）
- **测试框架**：PHPUnit 11.x（Feature 测试驱动与数据隔离）

---

## 三、系统预置用户与测试凭证

系统种子数据预置了五类角色的典型测试账号，统一初始密码均为 `password`，邮箱域名为 `@swufe.edu.cn`：

| 账号邮箱 | 预置身份 | 默认/专属面板 | 核心数据范围 |
|---|---|---|---|
| `super@swufe.edu.cn` | 系统超级管理员 | [/sysadmin](docs/architecture.md#5-系统管理面板-sysadmin) | 全校组织、全业务及系统配置 |
| `org@swufe.edu.cn` | 党委组织部在职管理员 | [/org](docs/architecture.md#4-党委组织部面板-org) | 全校组织、全校党员及组织部终审 |
| `college@swufe.edu.cn` | 经济学院党委在任委员（书记） | [/college](docs/architecture.md#3-二级党委面板-college) | 经济学院及下辖各党支部 |
| `branch@swufe.edu.cn` | 经济学院第1支部在任委员（书记） | [/branch](docs/architecture.md#2-党支部面板-branch) | 本党支部 |
| `member@swufe.edu.cn` | 普通正式党员 | [/](docs/architecture.md#1-个人统一工作台--默认全员入口) | 个人档案、个人业务及通用问答 |
| `training@swufe.edu.cn` | 培训协同员（未关联党员档案） | [/](docs/architecture.md#1-个人统一工作台--默认全员入口) | 个人档案、学时查看、协同业务（名册严格脱敏） |

---

## 四、工程规范与持续集成

1. **代码风格约束**：任何代码变动提交前，必须运行 Pint 格式化：
   ```bash
   vendor/bin/pint --format agent
   ```
2. **全量回归基线**：必须确保测试套件全量通过，当前测试基线为 68 个测试用例、283 个断言：
   ```bash
   php artisan test --compact
   ```
3. **架构规划跟踪**：整体业务演进与里程碑记录参见 [PLAN.md](PLAN.md)。
