#!/bin/bash

# Warna untuk terminal output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== Menyiapkan Environment SIMPUL Backend ===${NC}\n"

# 1. Jalankan PostgreSQL Service
echo -e "${YELLOW}[1/3] Memeriksa & Menjalankan PostgreSQL...${NC}"
sudo systemctl start postgresql

if systemctl is-active --quiet postgresql; then
    echo -e "${GREEN}✓ PostgreSQL aktif dan berjalan!${NC}\n"
else
    echo -e "${RED}✗ Gagal menjalankan PostgreSQL! Periksa systemctl.${NC}"
    exit 1
fi

# 2. Cek Koneksi Database via Laravel
echo -e "${YELLOW}[2/3] Memeriksa koneksi ke Database simpul_db...${NC}"
php artisan db:show >/dev/null 2>&1

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Koneksi ke database SIMPUL SUKSES!${NC}\n"
else
    echo -e "${RED}⚠ Gagal koneksi ke database. Pastikan ekstensi php-pgsql aktif & file .env sesuai.${NC}\n"
fi

# 3. Jalankan Laravel Artisan Serve
echo -e "${YELLOW}[3/3] Menjalankan Server Laravel...${NC}"
echo -e "${GREEN}➜ Backend Siap! Akses di: http://127.0.0.1:8000${NC}"
echo -e "Tekan ${RED}Ctrl + C${NC} untuk menghentikan server.\n"
echo "--------------------------------------------------------"

php artisan serve
