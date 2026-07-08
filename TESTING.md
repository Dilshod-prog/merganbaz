# Merganbaz - Testing Checklist

## Tizimni Sinovdan O'tkazish

### 1. O'rnatish Testi

- [ ] `database.sql` faylini import qilish
- [ ] `includes/config.php` faylida ma'lumotlar bazasi sozlamalarini to'g'ri kiritish
- [ ] Web serverda loyihani ochish
- [ ] Login sahifasi ochilishi

### 2. Autentifikatsiya Testi

#### Super Admin
- [ ] Standart login bilan kirish (admin / admin123)
- [ ] Admin dashboard sahifasi ochilishi
- [ ] Statistika to'g'ri ko'rsatilishi
- [ ] Chiqish (logout) ishlashi

#### Operator
- [ ] Operator hisobi yaratish
- [ ] Operator login bilan kirish
- [ ] Operator dashboard ochilishi
- [ ] Chiqish ishlashi

### 3. Super Admin - Stanoklar Boshqaruvi

- [ ] Yangi stanok qo'shish
  - [ ] Stanok nomi: "Stanok Test 1"
  - [ ] Stanok kodi: "TEST001"
  - [ ] Tavsif kiritish
  - [ ] Saqlash
- [ ] Stanok ro'yxatida ko'rinishi
- [ ] Stanokni tahrirlash
- [ ] Stanokni o'chirish/yoqish
- [ ] Takroriy kod kiritishda xatolik chiqishi

### 4. Super Admin - Operatorlar Boshqaruvi

- [ ] Yangi operator qo'shish
  - [ ] To'liq ism: "Test Operator"
  - [ ] Login: "testop"
  - [ ] Parol: "test123"
  - [ ] Telefon: "+998901234567"
  - [ ] Stanok tanlash
  - [ ] Huquqlar belgilash
  - [ ] Saqlash
- [ ] Operator ro'yxatida ko'rinishi
- [ ] Operatorni tahrirlash
  - [ ] Parolsiz tahrirlash ishlashi
  - [ ] Parol bilan tahrirlash ishlashi
- [ ] Huquqlarni o'zgartirish
- [ ] Operatorni o'chirish/yoqish
- [ ] Takroriy login kiritishda xatolik chiqishi

### 5. Super Admin - Tachkalar Boshqaruvi

- [ ] Yangi tachka yaratish
  - [ ] Tachka nomeri: "01A234BC"
  - [ ] Tavsif kiriting
  - [ ] Birinchi stanokni tanlash
  - [ ] "+ Stanok Qo'shish" bilan ikkinchi stanok qo'shish
  - [ ] Uchinchi stanok qo'shish
  - [ ] Saqlash
- [ ] Tachka ro'yxatida ko'rinishi
- [ ] Tachka ma'lumotlarini ko'rish (Ko'rish tugmasi)
  - [ ] Asosiy ma'lumotlar ko'rsatilishi
  - [ ] Stanoklar ketma-ketligi ko'rsatilishi
  - [ ] Ishlar tarixi (hozircha bo'sh)
- [ ] Ketma-ketlikni tahrirlash
  - [ ] Yangi stanok qo'shish
  - [ ] Stanokni o'chirish
  - [ ] Saqlash
- [ ] Takroriy tachka nomeri kiritishda xatolik chiqishi

### 6. Operator - Ish Jarayoni

#### Tachkada Ishni Boshlash
- [ ] Operator hisobi bilan kirish
- [ ] O'z stanokini ko'rish
- [ ] Tachkalar ro'yxatini ko'rish
- [ ] "▶️ Boshlash" tugmasi bosilganda:
  - [ ] Tasdiqlash oynasi chiqishi
  - [ ] Ish boshlanishi
  - [ ] Tachka "Jarayonda" statusiga o'tishi
  - [ ] "✅ Tugatish" va "⏸️ To'xtatish" tugmalari paydo bo'lishi

#### Ishni Tugatish
- [ ] "✅ Tugatish" tugmasini bosish
- [ ] Tasdiqlash oynasi
- [ ] Izoh kiritish imkoniyati
- [ ] Ish tugashi
- [ ] Agar keyingi stanok mavjud bo'lsa:
  - [ ] Tachka "Kutilmoqda" statusiga qaytishi
  - [ ] Keyingi stanok operatori ro'yxatida ko'rinishi
- [ ] Agar barcha stanoklar tugagan bo'lsa:
  - [ ] Tachka "Tugallandi" statusiga o'tishi

#### Ishni To'xtatish
- [ ] "⏸️ To'xtatish" tugmasini bosish
- [ ] Sabab kiritish
- [ ] Ish to'xtatilishi
- [ ] Work logs da qayd etilishi

### 7. Operator - Stanokni To'xtatish

- [ ] "⏸️ Stanokni To'xtatish" tugmasini bosish
- [ ] Modal ochilishi
- [ ] To'xtatish sababini tanlash:
  - [ ] Tushlik (Obed)
  - [ ] Gaz yo'q
  - [ ] Texnik nosozlik
  - [ ] Boshqa (qo'shimcha matn talab qilinishi)
- [ ] Izoh kiritish
- [ ] Saqlash
- [ ] Aktiv to'xtatishlar ro'yxatida ko'rinishi
- [ ] "▶️ Davom Ettirish" tugmasi ishlashi

### 8. Super Admin - Hisobotlar

- [ ] Hisobotlar sahifasini ochish
- [ ] Davr tanlash (Dan/Gacha)
- [ ] Qidirish tugmasi ishlashi
- [ ] Tugallangan tachkalar soni ko'rsatilishi
- [ ] Operatorlar statistikasi:
  - [ ] Operator ismi
  - [ ] Ishlangan tachkalar soni
  - [ ] Tugallangan ishlar soni
- [ ] To'xtatishlar statistikasi:
  - [ ] Stanok nomi
  - [ ] To'xtatish sababi
  - [ ] To'xtatishlar soni
  - [ ] O'rtacha davomiylik
- [ ] Tugallangan tachkalar ro'yxati
  - [ ] Tachka nomeri
  - [ ] Boshlangan vaqt
  - [ ] Tugallangan vaqt
  - [ ] Jami vaqt

### 9. Responsive Dizayn Testi

#### Mobil (< 768px)
- [ ] Login sahifasi to'g'ri ko'rinishi
- [ ] Dashboard mobilda to'g'ri ko'rinishi
- [ ] Navigatsiya to'g'ri ishlashi
- [ ] Jadvallar scroll qilishi (table-responsive)
- [ ] Modal oynalar to'g'ri ochilishi
- [ ] Tugmalar bosish uchun yetarlicha katta bo'lishi

#### Planshet (768px - 1024px)
- [ ] 2 ustunli grid ishlashi
- [ ] Statistika kartochkalar to'g'ri joylashishi
- [ ] Form elementlari yaxshi ko'rinishi

#### Desktop (> 1024px)
- [ ] To'liq interfeys ko'rinishi
- [ ] Barcha elementlar to'g'ri joylashishi

### 10. Xavfsizlik Testi

- [ ] Login qilmagan foydalanuvchi admin sahifalariga kira olmasligi
- [ ] Operator super admin sahifalariga kira olmasligi
- [ ] Super admin operator sahifalariga kira olmasligi
- [ ] SQL Injection himoyasi (parolda ' yoki " belgisi)
- [ ] XSS himoyasi (script teglar htmlspecialchars bilan tozalanishi)
- [ ] Parollar shifrlangan holatda saqlanishi (password_hash)

### 11. Xato Xabarlari

- [ ] Bo'sh forma yuborishda xato ko'rsatilishi
- [ ] Noto'g'ri login/parolda xato xabari
- [ ] Takroriy ma'lumot kiritishda xato
- [ ] Server xatoliklarida tushunarli xabar

### 12. Brauzer Mos Kelishi

- [ ] Google Chrome
- [ ] Mozilla Firefox
- [ ] Safari
- [ ] Microsoft Edge
- [ ] Mobil brauzerlar

## Umumiy Natija

- [ ] Barcha asosiy funksiyalar ishlaydi
- [ ] Interfeys tushunarli va foydalanish oson
- [ ] Mobil qurilmalarda to'g'ri ishlaydi
- [ ] Xavfsizlik choralari mavjud
- [ ] README fayli to'liq va tushunarli

## Topilgan Muammolar

_(Bu bo'limga test davomida topilgan muammolarni yozing)_

1. 
2. 
3. 

## Qo'shimcha Test Stsenariylari

### Stsenariy 1: To'liq Tachka Jarayoni
1. Super admin tachka yaratadi (3 ta stanok bilan)
2. Birinchi stanok operatori ishni boshlaydi
3. Operator ishni tugallaydi
4. Ikkinchi stanok operatori ishni boshlaydi va tugallaydi
5. Uchinchi stanok operatori ishni boshlaydi va tugallaydi
6. Tachka "Tugallandi" statusiga o'tishini tekshirish
7. Hisobotda ko'rinishini tekshirish

### Stsenariy 2: Stanok To'xtatish
1. Operator ishni boshlaydi
2. Stanokni to'xtatadi (Tushlik)
3. To'xtatish aktiv to'xtatishlar ro'yxatida paydo bo'ladi
4. 30 daqiqadan keyin davom ettiradi
5. Hisobotda to'xtatish ko'rinadi

### Stsenariy 3: Operator Huquqlarini Boshqarish
1. Operatorga faqat "Boshlash" huquqini berish
2. "Tugatish" tugmasi ko'rinmasligi kerak
3. Huquqlarni o'zgartirish
4. Tugmalar paydo bo'lishi

---

**Test sanasi:** _____________

**Test bajaruvchi:** _____________

**Natija:** ⬜ Muvaffaqiyatli  ⬜ Muammolar bor
