# HawkerOps - Restaurant Platform Management System

A comprehensive Laravel-based monitoring system for tracking restaurant menu items and platform status across Grab, FoodPanda, and Deliveroo delivery platforms.

## 📋 Table of Contents
- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [Project Structure](#project-structure)
- [Database Schema](#database-schema)
- [Usage](#usage)
- [Future Enhancements](#future-enhancements)

---

## 🎯 Overview

HawkerOps is a real-time monitoring dashboard designed to track 46 restaurant outlets across 3 major food delivery platforms in Singapore. It provides instant visibility into:
- Platform online/offline status
- Menu item availability (active/inactive)
- Historical status logs with Singapore timezone (SGT)
- Daily trends and analytics

**Live Monitoring**: Track 2,450+ menu items across all platforms in real-time.

---

## ✨ Features

### Current Features
- ✅ **Dashboard Overview** - Real-time status of all stores and platforms
- ✅ **Store Management** - Individual store monitoring with platform breakdown
- ✅ **Items Tracking** - Complete menu visibility with availability status
- ✅ **Platform Status** - Track Grab, FoodPanda, Deliveroo uptime
- ✅ **Historical Logs** - Daily status snapshots with real-time timestamp updates (SGT)
- ✅ **Info Guide** - Built-in help popup accessible from all pages
- ✅ **Alerts System** - UI ready for real-time notifications (mock data)
- ✅ **Reports** - Analytics pages for trends, reliability, performance (mock data)
- ✅ **Settings** - Scraper status, configuration, export tools (mock data)

### Planned Features
- 🔜 Real-time browser notifications
- 🔜 Email/Slack alerts
- 🔜 CSV/Excel export functionality
- 🔜 Charts and data visualization
- 🔜 User authentication & roles
- 🔜 Dark mode
- 🔜 Mobile optimization

---

## 🛠 Tech Stack

### Backend
- **Laravel 11** - PHP web framework
- **PHP 8.2+** - Server-side language
- **SQLite** - Database (easy deployment, no external DB required)

### Frontend
- **Blade Templates** - Laravel's templating engine
- **Tailwind CSS** - Utility-first CSS framework (via CDN)
- **Vanilla JavaScript** - No heavy frontend frameworks

### Scrapers
- **Python 3.x** - Web scraping scripts
- **Selenium/BeautifulSoup** - For dynamic content scraping

### Deployment
- **Render.com** - Cloud hosting platform
- **Git/GitHub** - Version control

---

## 📦 Installation

### Prerequisites
```bash
- PHP >= 8.2
- Composer
- Node.js & npm (optional, for Vite)
- Python 3.x (for scrapers)
- SQLite
```

### Setup Steps

1. **Clone the repository**
```bash
git clone https://github.com/your-repo/resto-db-v3.git
cd resto-db-v3.5
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database setup**
```bash
# SQLite database will be created automatically
php artisan migrate
```

5. **Run the application**
```bash
php artisan serve
```

6. **Access the dashboard**
```
http://localhost:8000
```

---

## 📁 Project Structure

```
resto-db-v3.5/
│
├── 📁 app/                                 # Laravel application code
│   ├── 📁 Console/                         # Artisan commands
│   ├── 📁 Exceptions/                      # Exception handlers
│   ├── 📁 Helpers/                         # Helper classes
│   │   └── ShopHelper.php                  # Shop mapping & brand data
│   ├── 📁 Http/                            # HTTP layer
│   │   ├── 📁 Controllers/                 # Route controllers (if used)
│   │   └── 📁 Middleware/                  # HTTP middleware
│   ├── 📁 Models/                          # Eloquent models (if used)
│   └── 📁 Providers/                       # Service providers
│
├── 📁 bootstrap/                           # Laravel bootstrap files
│   ├── app.php                             # Application bootstrap
│   └── cache/                              # Framework cache files
│
├── 📁 config/                              # Configuration files
│   ├── app.php                             # Application config
│   ├── database.php                        # Database connections
│   └── ...                                 # Other config files
│
├── 📁 database/                            # Database files & migrations
│   ├── 📁 migrations/                      # Database migrations
│   │   ├── 2026_01_28_060901_create_store_status_logs_table.php  # Store logs migration
│   │   └── ...                             # Other migrations
│   ├── 📁 seeders/                         # Database seeders
│   └── database.sqlite                     # SQLite database file
│
├── 📁 public/                              # Public assets (web root)
│   ├── index.php                           # Application entry point
│   ├── 📁 css/                             # Compiled CSS (if using Vite)
│   ├── 📁 js/                              # Compiled JS (if using Vite)
│   └── favicon.ico                         # Site favicon
│
├── 📁 resources/                           # Frontend resources
│   ├── 📁 css/                             # Raw CSS files
│   │   └── app.css                         # Main stylesheet
│   ├── 📁 js/                              # Raw JavaScript files
│   │   └── app.js                          # Main JS file
│   └── 📁 views/                           # Blade templates
│       ├── layout.blade.php                # Main layout (sidebar, header)
│       ├── dashboard.blade.php             # Dashboard overview page
│       ├── stores.blade.php                # All stores listing
│       ├── items.blade.php                 # All items listing (extends layout)
│       ├── platforms.blade.php             # Platform status page (extends layout)
│       ├── store-detail.blade.php          # Individual store items view
│       ├── store-logs.blade.php            # Historical status logs per store
│       ├── alerts.blade.php                # Alerts & notifications page
│       ├── offline-items.blade.php         # Offline items overview
│       │
│       ├── 📁 reports/                     # Reports subcategory pages
│       │   ├── daily-trends.blade.php      # Daily uptime & offline trends
│       │   ├── platform-reliability.blade.php  # Platform comparison stats
│       │   ├── item-performance.blade.php  # Item availability analysis
│       │   └── store-comparison.blade.php  # Side-by-side store comparison
│       │
│       └── 📁 settings/                    # Settings subcategory pages
│           ├── scraper-status.blade.php    # Scraper health monitoring
│           ├── configuration.blade.php     # System settings & preferences
│           └── export.blade.php            # Data export tools
│
├── 📁 routes/                              # Application routes
│   ├── web.php                             # Web routes (main routing file)
│   ├── api.php                             # API routes (if needed)
│   └── console.php                         # Console commands
│
├── 📁 storage/                             # Storage files
│   ├── 📁 app/                             # Application storage
│   ├── 📁 framework/                       # Framework cache & sessions
│   └── 📁 logs/                            # Application logs
│
├── 📁 tests/                               # Test files
│   ├── 📁 Feature/                         # Feature tests
│   └── 📁 Unit/                            # Unit tests
│
├── 📁 vendor/                              # Composer dependencies (gitignored)
│
├── 📁 scrapers/                            # Python scraping scripts (if applicable)
│   ├── grab_scraper.py                     # Grab platform scraper
│   ├── foodpanda_scraper.py                # FoodPanda platform scraper
│   └── deliveroo_scraper.py                # Deliveroo platform scraper
│
├── .env                                    # Environment variables (gitignored)
├── .env.example                            # Example environment file
├── .gitignore                              # Git ignore rules
├── artisan                                 # Laravel CLI tool
├── composer.json                           # PHP dependencies
├── composer.lock                           # PHP dependency lock
├── package.json                            # Node dependencies (if using Vite)
├── phpunit.xml                             # PHPUnit configuration
├── vite.config.js                          # Vite configuration (if used)
└── README.md                               # This file
```

---

## 🗄 Database Schema

### Tables

#### `store_status_logs`
**Purpose**: Stores daily status snapshots for each store with real-time updates throughout the day.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INTEGER | Primary key |
| `shop_id` | STRING | Store identifier |
| `shop_name` | STRING | Store name |
| `platforms_online` | INTEGER | Number of platforms online (0-3) |
| `total_platforms` | INTEGER | Total platforms tracked (always 3) |
| `total_offline_items` | INTEGER | Count of offline items across all platforms |
| `platform_data` | TEXT | JSON data containing per-platform details |
| `logged_at` | TIMESTAMP | Log entry timestamp (SGT) |
| `created_at` | TIMESTAMP | Record creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

**Indexes**: `shop_id, logged_at` (composite index for faster queries)

#### `platform_status`
**Purpose**: Current online/offline status for each platform per store.

| Column | Type | Description |
|--------|------|-------------|
| `shop_id` | STRING | Store identifier |
| `platform` | STRING | Platform name (grab/foodpanda/deliveroo) |
| `is_online` | BOOLEAN | Platform status (1=online, 0=offline) |
| `last_checked_at` | TIMESTAMP | Last scraper run timestamp |
| `last_check_status` | STRING | Last check result |

#### `items`
**Purpose**: Menu items with availability status across all platforms.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INTEGER | Primary key |
| `shop_id` | STRING | Store identifier |
| `shop_name` | STRING | Store name |
| `platform` | STRING | Platform name |
| `name` | STRING | Item name |
| `category` | STRING | Item category |
| `price` | DECIMAL | Item price |
| `is_available` | BOOLEAN | Availability status (1=active, 0=inactive) |
| `image_url` | STRING | Item image URL |
| `created_at` | TIMESTAMP | Record creation |
| `updated_at` | TIMESTAMP | Last update |

**Note**: Each item appears 3 times (once per platform). Unique items are counted by grouping.

---

## 🚀 Usage

### Accessing Pages

#### Main Navigation
- **Overview**: `/dashboard` - Dashboard with all stores overview
- **Stores**: `/stores` - List of all 46 stores
- **Items**: `/items` - All menu items with filters
- **Platforms**: `/platforms` - Platform status grid view
- **Alerts**: `/alerts` - Notifications center

#### Store-Specific Pages
- **Store Details**: `/store/{shopId}/items` - View all items for a specific store
- **Store Logs**: `/store/{shopId}/logs` - Historical status timeline

#### Reports (Collapsible Section)
- **Daily Trends**: `/reports/daily-trends` - Daily uptime & offline trends
- **Platform Reliability**: `/reports/platform-reliability` - Platform comparison
- **Item Performance**: `/reports/item-performance` - Item availability analysis
- **Store Comparison**: `/reports/store-comparison` - Compare up to 3 stores

#### Settings (Collapsible Section)
- **Scraper Status**: `/settings/scraper-status` - Monitor scraper health
- **Configuration**: `/settings/configuration` - System settings
- **Export Data**: `/settings/export` - Download reports

### Key Features Explained

#### 1. Run Sync Button
- Refreshes data from database
- Does NOT run scrapers
- Shows latest scraped data

#### 2. Store Logs Timeline
- Creates 1 entry per day per store
- Entry #1 for first day, #2 for second day, etc.
- Today's entry updates in real-time (timestamp always shows current SGT time)
- Historical entries show their logged timestamp

#### 3. Info Popup
- Click "i" button on any page
- Comprehensive guide to app features
- Explains buttons, status indicators, timezone, etc.

#### 4. Collapsible Navigation
- Reports and Settings sections expand/collapse
- Click section header to toggle
- Arrow rotates to indicate state

---

## 🔮 Future Enhancements

### Phase 1 (Quick Wins)
- [ ] Real alert system with database triggers
- [ ] Working CSV/Excel export functionality
- [ ] Chart.js integration for trend visualization
- [ ] Search functionality across all pages

### Phase 2 (High Value)
- [ ] Historical trend analysis from store_status_logs
- [ ] Email notifications for critical alerts
- [ ] Platform uptime tracking over time
- [ ] Notes/comments system for incidents

### Phase 3 (Nice to Have)
- [ ] Dark mode toggle
- [ ] User authentication & roles
- [ ] Mobile app optimization
- [ ] Customizable dashboard widgets

### Infrastructure
- [ ] Automated cron scheduling on Render
- [ ] Webhook alerts to Slack/Teams
- [ ] Public API for integrations
- [ ] Performance optimization & caching

---

## 📝 Developer Notes

### Important Conventions

1. **Timezone**: All timestamps use `Asia/Singapore` (SGT, UTC+8)
   ```php
   \Carbon\Carbon::now('Asia/Singapore')
   ```

2. **Store Logs Logic**:
   - One entry per day per store
   - If entry exists for today → UPDATE (keeps entry fresh)
   - If no entry for today → INSERT (creates new entry)
   - Display always shows current time for today's entry

3. **Blade Templates**:
   - `layout.blade.php` - Used by items.blade.php and platforms.blade.php
   - `dashboard.blade.php` and `stores.blade.php` - Standalone with own sidebar
   - All pages have info popup and collapsible navigation

4. **Navigation Structure**:
   - Main items: Overview, Stores, Items, Platforms, Alerts
   - Collapsible: Reports (4 pages), Settings (3 pages)
   - All items have emoji icons (📊 🏪 📦 🌐 🔔 📈 ⚙️)

### Code Standards

- PHP: Follow PSR-12 coding standard
- Blade: Use `@if`, `@foreach`, `@section` directives
- JavaScript: Use modern ES6+ syntax
- CSS: Tailwind utility classes preferred

---

## 🤝 Contributing

Currently a private project. For contributions or questions, contact the development team.

---

## 📄 License

Proprietary - Internal Use Only

---

## 👥 Credits

**Developed by**: Benson
**Design & UI/UX**: Gabriel

---

## 📞 Support

For technical issues or feature requests, please contact the system administrator.

---

**Last Updated**: January 28, 2026
**Version**: 3.5
**Status**: Active Development
**Built with ❤️ by Benson and Gabriel
