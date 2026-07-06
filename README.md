# ✦ Aurora CMS

Modern, villámgyors tartalomkezelő rendszer — PHP 8.3 + SQLite, nulla függőség, nincs build lépés.

## Indítás

**Laragonnal (ajánlott):** indítsd el a Laragont (Apache), majd nyisd meg: `http://cms-f.test`

**PHP beépített szerverrel:**
```
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -S localhost:8123 index.php
```

Az adatbázis (`storage/cms.sqlite`) az első betöltéskor automatikusan létrejön mintatartalommal.

## Admin felület

- URL: `/admin`
- Alapértelmezett belépés: **admin@cms.local** / **admin123**
- ⚠️ Az első belépés után változtasd meg a jelszót a Felhasználók menüben!

## Funkciók

| Terület | Leírás |
|---|---|
| **Posztok** | Rich text szerkesztő (Quill), kategória, kiemelt kép, vázlat/publikált, kivonat, egyedi slug |
| **Oldalak** | Statikus oldalak, menübe sorolás, sorrend |
| **Kategóriák** | Szín, leírás, inline szerkesztés |
| **Menük** | Fejléc- és láblécmenü, drag & drop sorrendezés, gyorsválasztó oldalakból/kategóriákból, külső linkek, új lapon nyitás |
| **Sablonok** | Dizájnsablonok (színek, betűtípusok, lekerekítés) élő előnézettel, 5 gyári sablon, aktiválás/másolás, **JSON export/import** |
| **Médiatár** | Drag & drop feltöltés, automatikus WebP bélyegkép, URL másolás, képválasztó modal |
| **Felhasználók** | Admin / szerkesztő szerepkörök, jelszókezelés |
| **Beállítások** | Oldalnév, szlogen, meta leírás, lapozás, lábléc |
| **Frontend** | Reszponzív magazin-téma, sötét mód, kereső, kategória-archívum, kapcsolódó cikkek, olvasási idő |
| **SEO** | `sitemap.xml`, `rss.xml`, Open Graph + Twitter meta tagek, canonical URL-ek |

## Technika

- **Stack:** PHP 8.3, SQLite (WAL mód), vanilla JS, saját CSS design system
- **Biztonság:** CSRF-védelem minden űrlapon, jelszó-hashelés (bcrypt), prepared statement-ek, MIME-ellenőrzés feltöltésnél, XSS-escape
- **Sebesség:** nincs keretrendszer, oldalanként néhány SQL-lekérdezés, statikus asset cache, WebP bélyegképek
- **Struktúra:**
  - `index.php` — front controller + útvonalak
  - `app/controllers/` — frontend és admin logika
  - `app/views/` — PHP sablonok (front + admin)
  - `assets/` — CSS/JS
  - `storage/` — SQLite adatbázis
  - `uploads/` — feltöltött média (év/hónap mappákban)
