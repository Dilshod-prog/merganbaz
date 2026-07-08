# Merganbaz - Machine Management Admin Panel

Merganbaz - bu stanoklar, operatorlar va tachkalarni boshqarish uchun mo'ljallangan to'liq admin panel tizimi.

## Asosiy Imkoniyatlar

### Super Admin uchun:
- ✅ Stanoklar (Machines) boshqaruvi
- ✅ Operatorlarni qo'shish, tahrirlash, huquqlar berish
- ✅ Tachkalarni yaratish va stanoklar ketma-ketligini belgilash
- ✅ Hisobotlarni ko'rish
- ✅ Barcha jarayonlarni kuzatish

### Operator uchun:
- ✅ O'z stanokida ishlash
- ✅ Tachkalar ustida ish boshlash, tugatish
- ✅ Stanokni to'xtatish (obed, gaz yo'q, texnik nosozlik, boshqa)
- ✅ Ish jarayonini kuzatish

## Texnologiyalar

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Ma'lumotlar bazasi**: MySQL 5.7+ / MariaDB 10.3+
- **Dizayn**: Mobile-First Responsive (barcha ekranlar uchun moslashgan)

## O'rnatish

### ⚡ TEZKOR O'RNATISH (Tavsiya qilinadi)

1. **Loyihani yuklab olish**:
```bash
cd C:/xampp/htdocs
git clone https://github.com/Dilshod-prog/merganbaz.git
```

2. **XAMPP ni ishga tushiring**:
   - Apache ✅ Start
   - MySQL ✅ Start

3. **Avtomatik o'rnatish**:
```
http://localhost/merganbaz/setup.php
```

Bu sahifa avtomatik:
- ✅ Database yaratadi
- ✅ Barcha jadvallarni yaratadi
- ✅ Admin user qo'shadi (admin/admin123)
- ✅ Test stanoklar qo'shadi

4. **Login qiling**:
```
http://localhost/merganbaz
Login: admin
Parol: admin123
```

---

### 📋 QADAMMA-QADAM O'RNATISH (Agar setup.php ishlamasa)

### 1. Loyihani yuklab olish

```bash
git clone https://github.com/Dilshod-prog/merganbaz.git
cd merganbaz
```

### 2. Ma'lumotlar bazasini yaratish

MySQL/MariaDB ga kiring va `database.sql` faylini import qiling:

```bash
mysql -u root -p < database.sql
```

Yoki phpMyAdmin orqali:
1. phpMyAdmin ochish
2. "Import" tugmasini bosing
3. `database.sql` faylini tanlang
4. "Go" bosing

### 3. Database konfiguratsiyasi

`includes/config.php` faylini ochib, ma'lumotlar bazasi ma'lumotlarini kiriting:

```php
define('DB_HOST', 'localhost');      // Server manzili
define('DB_USER', 'root');           // Database foydalanuvchi
define('DB_PASS', '');               // Database paroli
define('DB_NAME', 'merganbaz');      // Database nomi
```

### 4. Web serverga joylashtirish

Loyihani web server katalogiga ko'chiring:

**XAMPP uchun**:
```bash
cp -r merganbaz C:/xampp/htdocs/
```

**Linux/Ubuntu uchun**:
```bash
sudo cp -r merganbaz /var/www/html/
sudo chmod -R 755 /var/www/html/merganbaz
```

### 5. Web serverni ishga tushirish

- **XAMPP**: Apache va MySQL ni ishga tushiring
- **Linux**: `sudo service apache2 start && sudo service mysql start`

### 6. Tizimga kirish

Brauzerda quyidagi manzilni oching:
```
http://localhost/merganbaz/
```

**Standart login ma'lumotlari (Super Admin)**:
- Login: `admin`
- Parol: `admin123`

⚠️ **Muhim**: Birinchi kirishda parolni o'zgartiring!

## Tizim Strukturasi

```
merganbaz/
├── admin/                  # Super Admin sahifalari
│   ├── dashboard.php       # Bosh sahifa
│   ├── machines.php        # Stanoklar boshqaruvi
│   ├── operators.php       # Operatorlar boshqaruvi
│   ├── vehicles.php        # Tachkalar boshqaruvi
│   └── reports.php         # Hisobotlar
├── operator/               # Operator sahifalari
│   └── dashboard.php       # Operator ish paneli
├── ajax/                   # AJAX so'rovlar
│   ├── save_operator.php
│   ├── save_machine.php
│   ├── save_vehicle.php
│   ├── start_work.php
│   ├── complete_work.php
│   └── ...
├── includes/               # Umumiy fayllar
│   ├── config.php          # Ma'lumotlar bazasi sozlamalari
│   └── functions.php       # Umumiy funksiyalar
├── css/                    # Stillar
│   └── style.css           # Asosiy CSS
├── js/                     # JavaScript
│   └── main.js             # Asosiy JS
├── database.sql            # Ma'lumotlar bazasi strukturasi
├── login.php               # Kirish sahifasi
├── logout.php              # Chiqish
└── index.php               # Asosiy sahifa (redirect)
```

## Foydalanish

### Super Admin

1. **Stanoklar qo'shish**:
   - "Stanoklar" bo'limiga o'ting
   - "+ Stanok Qo'shish" tugmasini bosing
   - Stanok nomi va kodini kiriting
   - Saqlang

2. **Operator qo'shish**:
   - "Operatorlar" bo'limiga o'ting
   - "+ Operator Qo'shish" tugmasini bosing
   - Ism, login, parol kiriting
   - Stanok tanlang
   - Huquqlarni belgilang
   - Saqlang

3. **Tachka yaratish**:
   - "Tachkalar" bo'limiga o'ting
   - "+ Tachka Yaratish" tugmasini bosing
   - Tachka nomeri kiriting
   - Stanoklar ketma-ketligini belgilang
   - Saqlang

### Operator

1. **Ishni boshlash**:
   - Tachkalar ro'yxatidan kerakli tachkani toping
   - "▶️ Boshlash" tugmasini bosing

2. **Ishni tugatish**:
   - Aktiv tachkada "✅ Tugatish" tugmasini bosing
   - Kerak bo'lsa izoh qoldiring

3. **Stanokni to'xtatish**:
   - "⏸️ Stanokni To'xtatish" tugmasini bosing
   - Sabab tanlang (obed, gaz yo'q, texnik nosozlik)
   - Izoh qoldiring

## Ma'lumotlar bazasi strukturasi

### Asosiy jadvallar:
- `users` - Foydalanuvchilar (admin va operatorlar)
- `machines` - Stanoklar
- `vehicles` - Tachkalar
- `vehicle_machine_sequence` - Tachkalar uchun stanoklar ketma-ketligi
- `work_logs` - Ish jarayonlari tarixi
- `machine_stops` - Stanok to'xtatishlar
- `operator_permissions` - Operator huquqlari

## Xavfsizlik

- Parollar `password_hash()` funksiyasi bilan shifrlangan
- SQL Injection himoyasi (`prepared statements`)
- XSS himoyasi (`htmlspecialchars()`)
- Session-based autentifikatsiya
- Role-based access control (RBAC)

## Mobil Moslashuv

Tizim "Mobile-First" tamoyili asosida yaratilgan:
- ✅ Smartfonlar (320px+)
- ✅ Planshetlar (768px+)
- ✅ Noutbuklar (1024px+)
- ✅ Desktop (1280px+)

## Muammolarni hal qilish

### Ma'lumotlar bazasiga ulanib bo'lmayapti
- MySQL serverni tekshiring: `sudo service mysql status`
- `includes/config.php` dagi ma'lumotlarni tekshiring

### Sahifa bo'sh ochiladi
- PHP versiyasini tekshiring: `php -v` (7.4+ bo'lishi kerak)
- Apache error logini ko'ring: `/var/log/apache2/error.log`

### Stillar yuklanmayapti
- Fayl yo'llarini tekshiring
- Browser cache ni tozalang (Ctrl + Shift + R)

## Muallif

**Dilshod**
- GitHub: [@Dilshod-prog](https://github.com/Dilshod-prog)

## Litsenziya

Bu loyiha MIT litsenziyasi ostida.

## Yordam va Qo'llab-quvvatlash

Agar savollar yoki muammolar bo'lsa:
1. GitHub Issues orqali xabar bering
2. Pull Request yuboring

---

**Omad tilaymiz! 🚀**
