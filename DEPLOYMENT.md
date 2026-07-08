# Merganbaz - Deployment va GitHub ga Joylashtirish

## Loyiha Holati

✅ **Loyiha to'liq tayyor va Git ga commit qilingan!**

Barcha fayllar `/projects/sandbox/merganbaz` papkasida tayyor va commit qilingan.

## GitHub ga Joylashtirish

### Variant 1: GitHub veb interfeysi orqali (Eng oson)

1. [https://github.com/Dilshod-prog/merganbaz](https://github.com/Dilshod-prog/merganbaz) ni oching
2. "uploading an existing file" yoki "Add file" tugmasini bosing
3. Barcha loyiha fayllarini drag & drop qiling
4. Commit message yozing
5. "Commit changes" bosing

### Variant 2: Git CLI orqali (Mahalliy kompyuterdan)

Agar loyiha fayllarini mahalliy kompyuteringizga yuklab olgan bo'lsangiz:

```bash
cd merganbaz
git init
git add -A
git commit -m "Initial commit: Complete Merganbaz Admin Panel"
git branch -M main
git remote add origin https://github.com/Dilshod-prog/merganbaz.git
git push -u origin main
```

### Variant 3: GitHub Personal Access Token bilan

Agar GitHub authentication kerak bo'lsa:

1. GitHub Settings > Developer settings > Personal access tokens
2. "Generate new token" (classic)
3. Scopes: `repo` ni tanlang
4. Token yarating va nusxalang

Keyin push qilishda:
```bash
git remote set-url origin https://YOUR_TOKEN@github.com/Dilshod-prog/merganbaz.git
git push -u origin main
```

## Loyihada Nima Bor?

### 📁 Fayl Strukturasi

```
merganbaz/
├── admin/                      # Super Admin sahifalari
│   ├── dashboard.php          # Bosh sahifa
│   ├── machines.php           # Stanoklar boshqaruvi
│   ├── operators.php          # Operatorlar boshqaruvi
│   ├── vehicles.php           # Tachkalar boshqaruvi
│   └── reports.php            # Hisobotlar
│
├── operator/                   # Operator sahifalari
│   └── dashboard.php          # Operator ish paneli
│
├── ajax/                       # AJAX Backend
│   ├── save_operator.php      # Operator CRUD
│   ├── save_machine.php       # Stanok CRUD
│   ├── save_vehicle.php       # Tachka CRUD
│   ├── start_work.php         # Ishni boshlash
│   ├── complete_work.php      # Ishni tugatish
│   ├── stop_work.php          # Ishni to'xtatish
│   ├── stop_machine.php       # Stanokni to'xtatish
│   ├── resume_machine.php     # Stanokni davom ettirish
│   └── get_vehicle_*.php      # Tachka ma'lumotlari
│
├── includes/                   # Backend mantiq
│   ├── config.php             # Database konfiguratsiya
│   └── functions.php          # Yordamchi funksiyalar
│
├── css/                        # Stillar
│   └── style.css              # Asosiy CSS (Mobile-First)
│
├── js/                         # JavaScript
│   └── main.js                # Asosiy JS
│
├── database.sql               # MySQL Database Schema
├── login.php                  # Autentifikatsiya
├── logout.php                 # Chiqish
├── index.php                  # Kirish nuqtasi
├── README.md                  # To'liq dokumentatsiya
├── TESTING.md                 # Test checklist
├── DEPLOYMENT.md              # Bu fayl
└── .gitignore                 # Git ignore qoidalar
```

### ✨ Asosiy Xususiyatlar

#### Super Admin uchun:
- ✅ Stanoklar yaratish va boshqarish
- ✅ Operatorlar qo'shish, huquqlar berish
- ✅ Tachkalar yaratish va ketma-ketlik belgilash
- ✅ Real-time statistika
- ✅ To'liq hisobotlar (davr bo'yicha)
- ✅ Barcha jarayonlarni monitoring

#### Operator uchun:
- ✅ O'z stanokida ishlash
- ✅ Tachkalarda ish boshlash/tugatish
- ✅ Stanokni to'xtatish (sabablari bilan)
- ✅ Ish statistikasini ko'rish
- ✅ Real-time yangilanishlar

### 🗄️ Database Strukturasi

7 ta jadval:
1. **users** - Foydalanuvchilar (admin va operatorlar)
2. **machines** - Stanoklar
3. **vehicles** - Tachkalar
4. **vehicle_machine_sequence** - Stanoklar ketma-ketligi
5. **work_logs** - Ishlar tarixi
6. **machine_stops** - Stanok to'xtatishlar
7. **operator_permissions** - Operator huquqlari

### 🎨 Dizayn Xususiyatlari

- **Mobile-First**: Birinchi navbatda mobil uchun
- **Responsive**: Barcha ekranlar uchun (320px - 1920px+)
- **Modern UI**: Gradient kartochkalar, shadow effektlar
- **User-Friendly**: Tushunarli interfeys
- **Fast Loading**: Optimizatsiya qilingan CSS/JS

### 🔐 Xavfsizlik

- ✅ Password hashing (bcrypt)
- ✅ SQL Injection himoyasi (prepared statements)
- ✅ XSS himoyasi (htmlspecialchars)
- ✅ Session-based auth
- ✅ Role-based access control
- ✅ Input validation

## Keyingi Qadamlar

1. **Database o'rnatish**:
   ```bash
   mysql -u root -p < database.sql
   ```

2. **Config sozlash**:
   `includes/config.php` faylida database ma'lumotlarini kiriting

3. **Test qilish**:
   `TESTING.md` faylidagi checklist bo'yicha test qiling

4. **Ishga tushirish**:
   - XAMPP/LAMP da Apache va MySQL ni ishga tushiring
   - `http://localhost/merganbaz/` ni oching
   - Login: `admin` / Parol: `admin123`

## Qo'shimcha Ma'lumotlar

### Server Talablari
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Apache 2.4+ / Nginx
- mod_rewrite (agar kerak bo'lsa)

### Brauzer Qo'llab-quvvatlash
- Chrome/Edge (84+)
- Firefox (78+)
- Safari (13+)
- Mobil brauzerlar

### Optimizatsiya Tavsiyalari
- Gzip compression yoqing
- Browser caching sozlang
- PHP OPcache yoqing
- Database indexes qo'shing (agar kerak bo'lsa)

## Muammo bo'lsa?

README.md va TESTING.md fayllarini o'qing yoki GitHub Issues da savol bering.

---

**Barakalla! Loyiha tayyor! 🎉**

Endi uni GitHub ga yuklang va ishlatishni boshlang!
