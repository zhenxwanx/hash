# 🔐 PHP Hash Encoder

Script PHP sederhana untuk melakukan **encode / hashing string** menggunakan berbagai **algoritma hash populer**.  
Cocok untuk testing, pembelajaran kriptografi dasar, maupun utility ringan.

---

## ✨ Fitur
- Encode string ke hash
- Mendukung banyak algoritma hash PHP
- Ringan & mudah dipakai
- Tanpa dependency tambahan

---

## 🧠 Algoritma Hash yang Didukung
Beberapa algoritma umum yang tersedia:
- MD5
- SHA1
- SHA256
- SHA512
- (dan algoritma lain yang tersedia di `hash_algos()`)

---

## 📦 Requirements
- PHP **7.0+**
- PHP Hash extension (default aktif)

Cek algoritma yang tersedia:
```php
print_r(hash_algos());
