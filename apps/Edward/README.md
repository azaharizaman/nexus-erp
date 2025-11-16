# Edward CLI Demo - Terminal-based ERP Interface

**Edward CLI Demo** is a pure command-line demonstration application for **Nexus ERP**, showcasing the power of headless ERP systems through a terminal interface—a homage to the classic JD Edwards ERP systems that ran entirely in green-screen terminals.

---

## 🎯 What is Edward CLI Demo?

Edward CLI Demo proves that modern ERP systems don't need flashy web interfaces to be powerful. By consuming the `nexus/erp` package and atomic packages, Edward demonstrates:

✅ **Pure Terminal Interface** - No web routes, no API endpoints, no views  
✅ **Headless Architecture** - All business logic from atomic packages (nexus-tenancy, nexus-inventory, etc.)  
✅ **Interactive Menus** - Using Laravel Prompts for elegant terminal UX  
✅ **Full ERP Capabilities** - Tenant management, users, inventory, settings, audit logs  
✅ **CLI-First Approach** - Perfect for automation, scripting, and remote management  
✅ **Action Orchestration** - Demonstrates Laravel Actions pattern for unified invocation

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.3+
- Composer
- PostgreSQL or MySQL
- Redis (optional)

### Installation

```bash
# Clone the repository
cd /path/to/nexus-erp/apps/edward

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Edit .env with your database credentials
# Then run migrations
php artisan migrate

# Launch Edward!
php artisan edward:menu
```

---

## 🖥️ Using Edward CLI Demo

### Main Menu

```bash
php artisan edward:menu
```

This launches the interactive terminal interface with comprehensive ERP capabilities:

```
╔═══════════════════════════════════════════════════════════════════════╗
║                                                                       ║
║   ███████╗██████╗ ██╗    ██╗ █████╗ ██████╗ ██████╗                 ║
║   ██╔════╝██╔══██╗██║    ██║██╔══██╗██╔══██╗██╔══██╗                ║
║   █████╗  ██║  ██║██║ █╗ ██║███████║██████╔╝██║  ██║                ║
║   ██╔══╝  ██║  ██║██║███╗██║██╔══██║██╔══██╗██║  ██║                ║
║   ███████╗██████╔╝╚███╔███╔╝██║  ██║██║  ██║██████╔╝                ║
║   ╚══════╝╚═════╝  ╚══╝╚══╝ ╚═╝  ╚═╝╚═╝  ╚═╝╚═════╝                 ║
║                                                                       ║
║          Terminal-based ERP powered by Nexus ERP                     ║
║          A homage to classic JD Edwards systems                      ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

═══ EDWARD MAIN MENU ═══

  ❯ 🏢 Tenant Management (8 operations)
    👤 User Management (9 operations)
    📦 Inventory Management (9 operations)
    ⚙️  Settings & Configuration (9 operations)
    📊 Reports & Analytics (9 operations)
    🔍 Search & Query (9 operations)
    📝 Audit Logs (9 operations)
    🚪 Exit Edward
```

### Menu System Features

Edward CLI Demo includes **7 comprehensive sub-menus** with **60+ operations**:

1. **Tenant Management** - List, create, update, suspend, activate, archive, delete, impersonate
2. **User Management** - List, create, update, deactivate, activate, reset password, assign roles, permissions, delete
3. **Inventory Management** - List items, create, update, adjust stock, transfer, check levels, history, low stock alerts, delete
4. **Settings & Configuration** - List, view, update, delete settings, export/import, audit settings, clear cache, validate, reset to defaults
5. **Reports & Analytics** - Dashboard, tenant stats, user activity, inventory reports, settings usage, audit reports, system health, custom reports, export reports
6. **Search & Query** - Global search, tenants, users, inventory, settings, audit logs, advanced filters, saved searches, export results
7. **Audit Logs** - List activities, filter by entity/event/date, view details, export, stats, purge old logs, system events, compliance reports

### Available Commands

```bash
# Launch main menu (primary interface)
php artisan edward:menu

# Direct commands (orchestrated through Actions)
php artisan erp:tenant:list
php artisan erp:tenant:create
php artisan erp:user:list
php artisan erp:inventory:list
# ... and more
```

---

## 🏗️ Architecture

Edward CLI Demo is a **minimal Laravel application** that demonstrates the **Action Orchestration Pattern**:

```
apps/edward/
├── app/
│   └── Console/
│       └── Commands/
│           └── EdwardMenuCommand.php  # Main terminal interface (448 lines)
├── config/                             # Minimal Laravel config
├── database/
│   └── migrations/                     # Database schema (shared with main app)
├── composer.json                       # Requires nexus/erp + atomic packages
└── artisan                             # CLI entry point
```

### What's NOT in Edward CLI Demo (Stripped in Phase 8.8)
- ❌ No web routes (removed `routes/api.php`)
- ❌ No HTTP controllers (removed `app/Http/Controllers/`)
- ❌ No middleware (removed `app/Http/Middleware/`)
- ❌ No API resources (removed `app/Http/Resources/`)
- ❌ No Blade views or frontend assets (removed `resources/css/`, `resources/js/`)
- ❌ No public assets except `.htaccess`

### What Edward CLI Demo Demonstrates
✅ **Action Orchestration** - Single action classes invoked as CLI commands (via `lorisleiva/laravel-actions`)  
✅ **Atomic Package Integration** - Consumes nexus-tenancy, nexus-inventory, nexus-audit-log, nexus-settings  
✅ **Pure Terminal Interface** - Interactive menus using Laravel Prompts  
✅ **Headless Architecture** - Business logic in atomic packages, presentation in Edward  
✅ **CLI-First Development** - No web dependencies, perfect for automation  
✅ **Modern Laravel CLI** - Demonstrates best practices for command-line applications

### Action Orchestration Flow

```
Terminal User Input
    ↓
EdwardMenuCommand (Laravel Prompts)
    ↓
Artisan Command (e.g., erp:tenant:list)
    ↓
Action Class (e.g., ListTenantsAction)
    ↓
Atomic Package Logic (nexus-tenancy)
    ↓
Database/Storage
```

**Key Insight:** The same `ListTenantsAction` can be invoked as:
- CLI command: `php artisan erp:tenant:list`
- API endpoint: `GET /api/tenants`
- Queued job: `ListTenantsAction::dispatch()`
- Event listener: `ListTenantsAction::handle()`

Edward demonstrates the **CLI invocation path** of this unified pattern.

---

## 🎓 Why "Edward CLI Demo"?

**Edward** is a tribute to **JD Edwards ERP** (now Oracle JD Edwards EnterpriseOne), one of the pioneering ERP systems that:

- Ran entirely in **terminal/green-screen interfaces**
- Proved ERP didn't need GUIs to be powerful
- Dominated the market in the 1980s-1990s
- Set standards for modular ERP architecture

By naming our CLI demo "Edward," we honor that legacy while proving that modern headless ERP systems can deliver the same power with contemporary technology—now with the flexibility of Laravel Actions for multi-channel invocation.

---

## 🔮 Implementation Status

Edward CLI Demo menu system is **fully implemented** with 60+ operations across 7 sub-menus:

### ✅ Completed (Phase 8.8)
- [x] **Terminal menu system** - EdwardMenuCommand with 7 interactive sub-menus
- [x] **Main menu interface** - ASCII art banner, navigation, exit handling
- [x] **Tenant management menu** - 8 operations (placeholder implementations)
- [x] **User management menu** - 9 operations (placeholder implementations)
- [x] **Inventory management menu** - 9 operations (placeholder implementations)
- [x] **Settings & configuration menu** - 9 operations (placeholder implementations)
- [x] **Reports & analytics menu** - 9 operations (placeholder implementations)
- [x] **Search & query menu** - 9 operations (placeholder implementations)
- [x] **Audit logs menu** - 9 operations (placeholder implementations)
- [x] **Web components stripped** - Removed routes, controllers, middleware, resources
- [x] **Documentation updated** - README reflects CLI-only architecture

### 🔄 Next Steps (Post Phase 8.8)
- [ ] **Connect to real Actions** - Replace placeholders with actual Action invocations
- [ ] **Full tenant operations** - Create, list, suspend, activate via Actions
- [ ] **User RBAC operations** - Full user lifecycle, roles, permissions
- [ ] **Inventory features** - Real stock movements, transfers, reports
- [ ] **Settings CRUD** - Complete settings management via Actions
- [ ] **Activity log viewing** - Browse and filter audit logs
- [ ] **Search implementation** - Global search powered by Laravel Scout
- [ ] **Batch operations** - Import/export via CSV
- [ ] **Demo data seeders** - Sample tenants, users, inventory for testing

---

## 📦 Package Dependencies

Edward CLI Demo requires **atomic packages** via the nexus/erp orchestrator:

```json
{
  "require": {
    "nexus/erp": "dev-main",
    "nexus/tenancy": "dev-main",
    "nexus/inventory": "dev-main",
    "nexus/audit-log": "dev-main",
    "nexus/settings": "dev-main",
    "nexus/backoffice": "dev-main",
    "lorisleiva/laravel-actions": "^2.0"
  }
}
```

All business logic comes from **atomic packages**. The `nexus/erp` orchestrator provides **Action registration** and invocation. Edward is purely a **CLI presentation layer** demonstrating terminal-based interaction.

---

## 🤝 Contributing

Edward is a demonstration app. To contribute:

1. **For ERP features** - Contribute to the `nexus/erp` package at `/src/`
2. **For CLI interface** - Enhance Edward's terminal commands in `/apps/edward/app/Console/Commands/`
3. **For new modules** - Add submenu commands following the `EdwardMenuCommand` pattern

---

## 📄 License

Edward is part of the Nexus ERP project and shares the same license (MIT).

---

## 🌟 Key Takeaway

**Edward proves that headless ERP systems can power ANY interface** - from web SPAs to mobile apps to pure terminal interfaces. The future of ERP is API-first, and Edward showcases exactly that vision.

```bash
# The power of Nexus ERP, right in your terminal
php artisan edward:menu
```
