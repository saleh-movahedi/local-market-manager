# Local Market Manager

سیستم مدیریت فروشگاه‌های هایپرمارکت (فروشگاه‌های Franchise) با Laravel 13، Filament 3 و Queue-based sync.

---

## 🎯 هدف پروژه

مدیریت محصولات، قیمت‌ها، موجودی و تسویه‌حساب برای چندین فروشگاه‌های هایپرمارکت (مانند فروشگاه‌های ۶۹۹۷ و ۵۱۶۲۸ در اوکالا) با قابلیت:

- **Sync محصولات** از API هایپرمارکت (API Gateway اوکالا)
- **مقایسه قیمت و موجودی** بین فروشگاه‌های مختلف
- **مدیریت مرچنت‌ها** (فروشگاه‌های شما) و تخصیص محصولات
- **تسویه‌حساب هفتگی** خودکار با کمیسیون و لجر حسابداری
- **پنل ادمین** با Filament v3

---

## 🏗 معماری

```
┌─────────────────────────────────────────────────────────────┐
│                    Local Market Manager                     │
├─────────────────────────────────────────────────────────────┤
│  Laravel 13 + Filament 3 (Admin Panel)                     │
├─────────────────────────────────────────────────────────────┤
│  Models                                                     │
│  ├── Store          → فروشگاه‌های هایپرمارکت                │
│  ├── Product        → محصولات اصلی (store-agnostic)         │
│  ├── StoreProduct   → قیمت/موجودی هر محصول در هر فروشگاه    │
│  ├── Category       → دسته‌بندی‌های درختی (سطح ۱، ۲)        │
│  ├── Merchant       → مرچنت‌های شما                         │
│  ├── MerchantProduct→ محصولات مرچنت (قیمت/موجودی سفارشی)    │
│  ├── Order          → سفارش‌ها                              │
│  ├── OrderItem      → آیتم‌های سفارش                        │
│  ├── LedgerEntry    → حسابداری (فروش، کمیسیون، مرجوعی...)  │
│  └── Settlement     → تسویه‌حساب هفتگی                     │
├─────────────────────────────────────────────────────────────┤
│  Jobs (Queue/Horizon)                                       │
│  ├── SyncCategoryProducts     → SYNC یک دسته برای یک فروشگاه│
│  ├── SyncAllStoresProducts    → SYNC همه دسته‌ها/فروشگاه‌ها  │
│  └── CalculateSettlements     → محاسبه تسویه‌حساب هفتگی    │
├─────────────────────────────────────────────────────────────┤
│  API Controllers                                            │
│  ├── StoreController          → CRUD فروشگاه + SYNC         │
│  ├── ProductController        → محصولات + show در فروشگاه   │
│  ├── CategoryController       → دسته‌بندی + SYNC             │
│  ├── MerchantController       → مرچنت + Ledger/Settlements   │
│  ├── MerchantProductController→ محصولات مرچنت               │
│  ├── OrderController          → سفارش‌ها + وضعیت            │
│  ├── SettlementController     → تسویه‌حساب + پرداخت         │
│  └── ComparisonController     → مقایسه دو فروشگاه          │
└─────────────────────────────────────────────────────────────┘
```

---

## 📦 دیتابیس (SQLite / قابل تغییر به PostgreSQL/MySQL)

### جداول اصلی

| جدول | توضیح |
|------|-------|
| `stores` | فروشگاه‌های هایپرمارکت (code: 6997, 51628) |
| `products` | محصولات اصلی (name, image_url, slug, attributes) |
| `store_products` | **Pivot + Financial Data**: price, ok_price, discount, quantity, supply_status |
| `categories` | دسته‌بندی درختی (level 1, 2, parent_ids) |
| `merchants` | مرچنت‌های شما (code, settings, margin_percent) |
| `merchant_products` | محصولات مرچنت با قیمت/موجودی سفارشی |
| `orders` | سفارش‌ها (status, totals, shipping) |
| `order_items` | آیتم‌های سفارش (unit prices, line_total) |
| `ledger_entries` | حسابداری دوطرفه (sale, commission, refund, payout, fee) |
| `settlements` | تسویه‌حساب هفتگی (draft → confirmed → paid) |

---

## 🔄 فرآیند SYNC (Queue-based)

```
1. StoreController::syncAll() → Dispatch SyncAllStoresProducts Job
        │
        ▼
2. SyncAllStoresProducts Job → For each active Store + Level-2 Category
        │
        ▼
3. SyncCategoryProducts Job (per store+category) → Queue 'sync'
        │
        ├── GET https://apigateway.okala.com/api/unicorn/v2/products/store/{code}?pC_Id={cat}&slug={slug}
        ├── UpdateOrCreate Product (master data)
        ├── UpdateOrCreate StoreProduct (financial data per store)
        └── Update Category.last_synced_at
```

### صف‌ها (Queues)
- `sync` → جاب‌های SYNC محصولات
- `financial` → تسویه‌حساب
- `scheduler` → کرون‌جاب‌ها
- `default` → بقیه

---

## 📊 مقایسه فروشگاه‌ها (Comparison API)

```
GET /api/comparison?store1=51628&store2=6997
```

خروجی:
```json
{
  "store1": {"id": 2, "code": "51628", "name": "فروشگاه ۵۱۶۲۸"},
  "store2": {"id": 1, "code": "6997", "name": "فروشگاه ۶۹۹۷"},
  "summary": {
    "only_in_store1": 0,
    "only_in_store2": 0,
    "price_differences": 4,
    "status_differences": 2,
    "total_compared": 4
  },
  "price_differences": [...],
  "status_differences": [...]
}
```

---

## 💰 تسویه‌حساب (Settlement)

هر هفته دوشنبه ساعت ۰۴:۰۰:
1. محاسبه فروش، کمیسیون، مرجوعی برای هر مرچنت
2. ساخت/بروزرسانی `Settlement` (draft → confirmed → paid)
3. ثبت `LedgerEntry` برای کمیسیون و قابل پرداخت

---

## 🖼 بهینه‌سازی عکس (Bandwidth)

| نوع | اندازه | کیفیت | کاربرد |
|-----|--------|-------|--------|
| Thumbnail | 142x142 | 90% | جداول Filament (ImageColumn) |
| Medium | 231x231 | 100% | جزئیات محصول |
| Original | 0:0 (full) | 100% | دانلود کامل |

**Accessors در Product Model:**
- `thumbnail_url` → 142x142/quality:90
- `medium_image_url` → 231x231/quality:100

---

## 🚀 راه‌اندازی

### پیش‌نیازها
- PHP 8.2+
- Composer
- SQLite / PostgreSQL / MySQL
- Redis (برای Horizon/Queue)

### نصب
```bash
git clone https://github.com/saleh-movahedi/local-market-manager
cd local-market-manager
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed  # اگر seeder داشته باشید
```

### اجرای سرور
```bash
# Development
php artisan serve

# Queue Worker
php artisan queue:work --queue=sync,financial,default

# Horizon (تولید/مانیتورینگ صف‌ها)
php artisan horizon

# Scheduler (کرون‌جاب‌ها)
php artisan schedule:work
```

---

## 🌐 پنل ادمین (Filament v3)

```
URL: http://localhost:8000/filament
ایمیل: admin@local-market.com
پسورد: admin123456
```

**Resources موجود:**
- Stores (با دکمه SYNC در صفحه Edit)
- Products
- Store Products (قیمت/موجودی در هر فروشگاه)
- Categories (درختی Level 1/2)
- Merchants + Merchant Products
- Orders + Order Items
- Settlements + Ledger Entries

---

## 📁 ساختار پروژه (مهم)

```
app/
├── Http/Controllers/Api/       # API Controllers
├── Filament/Resources/         # Filament Resources (Admin Panel)
├── Jobs/                       # Queue Jobs (Sync, Settlement)
├── Models/                     # Eloquent Models
├── Providers/
│   └── AppServiceProvider.php  # Scheduler Registration
└── Console/Kernel.php          # (در Laravel 11 در AppServiceProvider)

database/
├── migrations/                 # 11 Migration اصلی
└── seeders/

routes/
├── api.php                     # تمام API Routes
└── web.php
```

---

## ⚙️ Environment Variables (`.env` مهم)

```env
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=local_market
# DB_USERNAME=postgres
# DB_PASSWORD=secret

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

QUEUE_CONNECTION=redis
# QUEUE_CONNECTION=database
```

---

## 🔐 Security Notes

- API هایپرمارکت عمومی است (بدون احراز هویت) اما rate-limit دارد
- پنل ادمین با Sanctum/Filament امن است
- توکن‌های شخصی در `.env` نگهداری می‌شوند

---

## 📝 Development Notes

### اضافه کردن فروشگاه جدید
```bash
php artisan tinker --execute="
App\Models\Store::create([
    'name' => 'فروشگاه جدید',
    'code' => 'NEW_CODE',
    'url' => 'https://www.okala.com/store/NEW_CODE',
    'is_active' => true
]);
"
```

### SYNC دستی یک فروشگاه
```bash
php artisan tinker --execute="
App\Jobs\SyncAllStoresProducts::dispatch();
"
```

### اجرای worker برای پردازش SYNC
```bash
php artisan queue:work --queue=sync,financial,default --timeout=300
```

---

## 📄 License

MIT License - آزاد برای استفاده تجاری و غیرتجاری.

---

## 🤝 Contributing

PRها و Issueها خوش‌آمدند. برای تغییرات بزرگ ابتدا Issue باز کنید.

---

**Maintainer:** saleh-movahedi  
**Repository:** https://github.com/saleh-movahedi/local-market-manager