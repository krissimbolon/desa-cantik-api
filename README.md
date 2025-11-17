# Desa Cantik API
**Sistem Informasi Desa Cinta Statistik Toraja Utara**  
Backend API - Laravel 12 | PHP 8.2 | MySQL 8.0

[![Laravel](https://img.shields.io/badge/Laravel-12.37-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat&logo=docker&logoColor=white)](https://www.docker.com)

---
## Daftar Isi

- [Tentang Proyek](#tentang-proyek)
- [Tim Pengembang](#tim-pengembang)
- [Tech Stack](#tech-stack)
- [Fitur Utama](#fitur-utama)
- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Struktur Database](#struktur-database)
- [Test Credentials](#test-credentials)
- [Development Workflow](#development-workflow)
- [API Documentation](#api-documentation)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [Documentation](#documentation)
- [Project Structure](#project-structure)
- [Security](#security)
- [License](#license)
- [Contact & Support](#contact--support)
- [Acknowledgments](#acknowledgments)
- [Project Stats](#project-stats)

---

## Tentang Proyek

**Sistem Informasi Desa Cinta Statistik (Cantik)** merupakan sistem informasi berbasis web untuk pengelolaan dan publikasi data statistik desa binaan BPS Kabupaten Toraja Utara, saat ini meliputi Desa Nonongan Selatan dan Desa Rindingbatu.

### Tujuan Sistem
- **Pengelolaan Data Statistik**: Mengelola indikator statistik desa secara terstruktur
- **Publikasi**: Upload dan kelola dokumen publikasi
- **Peta Tematik**: Visualisasi data geospasial dengan GeoJSON
- **Multi-User Access**: Role-based access (Pegawai BPS, Perangkat Desa, Masyarakat Umum)
- **Keamanan Data**: Menggunakan JWT authentication dan authorization

### Lingkup Proyek
- **Organisasi:** BPS Kabupaten Toraja Utara
- **Desa Binaan:** Nonongan Selatan, Rindingbatu

---

## Tim Pengembang

**Tim 4 Kelas 3SI1**

| Nama                                  | NIM       | Role                               |
| ------------------------------------- | --------- | ---------------------------------- |
| **Teguh Christianto Simbolon**        | 222313403 | Project Manager, Backend Developer |
| **Alif Zakiansyah As Syauqi**         | 222312958 | Lead Backend Developer             |
| **Ahmad Adib Husaini Al Munawwar**    | 222312948 | Backend Developer                  |
| **Amir Syaifudin**                    | 222312968 | Lead Frontend Developer            |
| **Anggita Cristin Meylani**           | 222312982 | Frontend Developer                 |
| **Nyimas Virna Salsa Lestari Risqia** | 222313307 | Frontend Developer                 |

**Institusi:** Politeknik Statistika STIS Program Studi D-IV Komputasi Statistik

---

## Tech Stack

### Backend (API)
- **Framework:** Laravel 12.37.0
- **Language:** PHP 8.2.29
- **Database:** MySQL 8.0
- **Web Server:** Nginx Alpine
- **Authentication:** JWT (tymon/jwt-auth)
- **API Documentation:** L5-Swagger / OpenAPI 3.0
- **Testing:** PHPUnit, Pest

### Infrastructure
- **Containerization:** Docker & Docker Compose
- **Orchestration:** Docker Compose v2
- **Version Control:** Git & GitLab
- **CI/CD:** GitLab CI/CD (Planned)

### Development Tools
- **PHP Memory:** 1GB (untuk handling file besar)
- **Upload Limit:** 200MB (publikasi PDF)
- **Database Packet Size:** 200MB
- **Session Driver:** Database
- **Cache Driver:** File (development) / Redis (production)

---

## Fitur Utama

### 1. Manajemen Pengguna & Autentikasi
- Login dengan JWT Token
- Role-based Access Control (RBAC)
  - Pegawai BPS: Full access
  - Perangkat Desa: Manage data desa sendiri
  - Masyarakat Umum: Read-only data publik
- Manajemen profil
- Reset password

### 2. Manajemen Data Desa
- CRUD daftar desa
- Profil desa (deskripsi, visi-misi, logo)
- Aktivasi/deaktivasi modul per desa
- Tampilkan/sembunyikan desa dari portal publik

### 3. Statistik Desa
- Kelola indikator statistik (kategori, unit, deskripsi)
- Input data statistik time-series (per tahun)
- Impor CSV bulk data
- Ekspor data ke CSV/Excel
- Visualisasi data (chart, graph)

### 4. Publikasi Laporan
- Upload file PDF (max 200MB)
- Metadata publikasi (judul, deskripsi, kategori)
- Download publikasi
- Soft delete

### 5. Peta Tematik
- Upload GeoJSON batas desa
- Manajemen berbagai layer tematik
- Konfigurasi tampilan peta (warna, opacity)
- Link indikator ke peta tematik

---

## Prerequisites

Pastikan sudah terinstall:

- **Docker** v20.10 atau lebih baru
- **Docker Compose** v2.0 atau lebih baru
- **Git** v2.30 atau lebih baru
- **WSL2** (untuk pengguna Windows)

### Verifikasi Instalasi

```bash
docker --version
# Docker version 20.10.x atau lebih baru

docker-compose --version
# Docker Compose version v2.x.x atau lebih baru

git --version
# git version 2.30.x atau lebih baru
```

---

## Quick Start

### 1. Clone Repository

```bash
git clone https://git.stis.ac.id/rpl-lancarnyaman/desa-cantik-api.git
cd desa-cantik-api
```

### 2. Setup Environment

```bash
# Copy environment file
cp .env.example .env

# Edit .env jika perlu (opsional)
nano .env
```

### 3. Start Docker Containers

```bash
# Build & start semua services
docker-compose up -d --build

# Tunggu ~30 detik sampai semua container ready
```

### 4. Install Dependencies

```bash
# Masuk ke container app
docker-compose exec app bash

# Install Composer packages
composer install

# Generate application key
php artisan key:generate

# Exit container
exit
```

### 5. Run Migrations & Seeders

```bash
# Run database migrations
docker-compose exec app php artisan migrate

# Seed data dummy
docker-compose exec app php artisan db:seed
```

### 6. Verify Installation

```bash
# Test API endpoint
curl -I http://localhost:8000

# Expected: HTTP/1.1 200 OK
```

### 7. Access Application

Buka browser:
```
http://localhost:8000
```

**Sukses!** Jika muncul Laravel welcome page, instalasi berhasil!

---

## Struktur Database

### Tabel Aplikasi (11 tabel)

| Tabel                   | Deskripsi                                           | Relasi                                    |
| ----------------------- | --------------------------------------------------- | ----------------------------------------- |
| **user_roles**          | Role user (Pegawai BPS, Perangkat Desa, Masyarakat) | → users                                   |
| **desa**                | Master data desa                                    | → users, desa_profiles, desa_modules, dll |
| **users**               | Akun pengguna sistem                                | ← roles, ← desa                           |
| **desa_profiles**       | Profil lengkap desa (1:1)                           | ← desa                                    |
| **desa_modules**        | Aktivasi modul per desa                             | ← desa                                    |
| **indicators**          | Master indikator statistik                          | → desa_indicator_data                     |
| **desa_indicator_data** | Data statistik time-series                          | ← desa, ← indicators                      |
| **publications**        | File publikasi PDF                                  | ← desa                                    |
| **geospatial_data**     | GeoJSON boundary desa                               | ← desa                                    |
| **thematic_maps**       | Layer peta tematik                                  | ← desa                                    |
| **thematic_indicators** | Junction table (maps ↔ indicators)                  | ← thematic_maps, ← indicators             |

### Tabel Laravel System (8 tabel)

- `cache`, `cache_locks` - Cache storage
- `sessions` - Session database driver
- `jobs`, `job_batches`, `failed_jobs` - Queue system
- `password_reset_tokens` - Password reset
- `migrations` - Migration tracker

**Total:** 19 tabel

### Entity Relationship Diagram (ERD)

Lihat dokumentasi lengkap di:
- **Laporan Progres Milestone 2 Tim 4 Kelas 3SI1:** Halaman 48-49

---

## Test Credentials

### Pegawai BPS (Full Access)
```
Email: admin@bps.go.id
Password: password123
Role: Admin BPS
Access: Semua fitur & semua desa
```

### Perangkat Desa - Nonongan Selatan
```
Email: nonongan@desacantik.id
Password: password123
Role: Perangkat Desa
Access: Data Desa Nonongan Selatan saja
```

### Perangkat Desa - Rindingbatu
```
Email: rindingbatu@desacantik.id
Password: password123
Role: Perangkat Desa
Access: Data Desa Rindingbatu saja
```

**PENTING:** Ganti password default sebelum production deployment!

---

## Development Workflow

### Git Branch Strategy (GitFlow)

```
main (production)
 ↑
 └─ develop (integration)
      ↑
      ├─ feature/authentication
      ├─ feature/desa-management
      ├─ feature/statistics
      ├─ feature/publications
      ├─ feature/maps
      └─ bugfix/issue-xxx
```

### Workflow Steps

1. **Create Feature Branch**
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/your-feature-name
   ```

2. **Development**
   ```bash
   # Make changes
   git add .
   git commit -m "feat: add your feature"
   ```

3. **Push & Create Merge Request**
   ```bash
   git push origin feature/your-feature-name
   ```

4. **Code Review & Merge**
   - Create Merge Request di GitLab
   - Request review dari team
   - Fix review comments
   - Merge ke `develop`

5. **Delete Feature Branch**
   ```bash
   git branch -d feature/your-feature-name
   git push origin --delete feature/your-feature-name
   ```

### Commit Message Convention

Gunakan [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add user authentication API
fix: resolve database connection timeout
docs: update README installation steps
style: format code with PSR-12
refactor: restructure controller methods
test: add unit tests for User model
chore: update composer dependencies
```

---

## API Documentation

### Swagger UI (OpenAPI 3.0)

- Base URL: `${APP_URL}/api` (default: `http://localhost:8000/api`)
- UI: `http://localhost:8000/api/documentation`
- Spec output: `storage/api-docs/openapi.json` & `openapi.yaml` (auto-regenerated in `local` via `L5_SWAGGER_GENERATE_ALWAYS=true`)

Generate/rebuild manually:
```bash
php artisan l5-swagger:generate
```

Notes:
- Authentication uses Sanctum bearer tokens. Use the "Authorize" button in Swagger UI and paste `Bearer <token>` from `/api/v1/auth/login`.
- Annotation sources live in `app/Docs` and the controllers under `app/Http/Controllers`.

### Postman Collection

Download collection:
```
/docs/postman/Desa-Cantik-API.postman_collection.json
```

---

##  Troubleshooting

### Container tidak start

```bash
# Check logs
docker-compose logs app
docker-compose logs mysql
docker-compose logs nginx

# Restart containers
docker-compose restart

# Rebuild jika perlu
docker-compose down
docker-compose up -d --build
```

### Error: "Connection refused"

```bash
# Pastikan container running
docker-compose ps

# Expected: 3 containers dengan status "Up"
```

### Error: "Table not found"

```bash
# Run migrations
docker-compose exec app php artisan migrate

# Atau fresh migrate
docker-compose exec app php artisan migrate:fresh --seed
```

### Error: "Permission denied" (Storage)

```bash
# Fix permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Port 8000 sudah digunakan

```bash
# Edit docker-compose.yml
# Ganti port nginx:
#   ports:
#     - "8080:80"  # Ganti 8000 ke 8080

# Restart
docker-compose down
docker-compose up -d
```

### Debugging Commands

```bash
# Check container logs
docker-compose logs -f app

# Execute commands in container
docker-compose exec app php artisan tinker

# Check database
docker-compose exec mysql mysql -u desa_cantik_user -p

# Restart specific service
docker-compose restart app
```

---

## Contributing

### Setup Development Environment

```bash
# 1. Clone & setup
git clone https://git.stis.ac.id/rpl-lancarnyaman/desa-cantik-api.git
cd desa-cantik-api
cp .env.example .env

# 2. Start Docker
docker-compose up -d

# 3. Install dependencies
docker-compose exec app composer install

# 4. Run migrations
docker-compose exec app php artisan migrate:fresh --seed

# 5. Create your feature branch
git checkout -b feature/your-feature
```

### Code Style

Proyek ini menggunakan **PSR-12** coding standard:

```bash
# Format code
docker-compose exec app ./vendor/bin/pint

# Check style
docker-compose exec app ./vendor/bin/phpcs
```

### Running Tests

```bash
# Run all tests
docker-compose exec app php artisan test

# Run specific test
docker-compose exec app php artisan test --filter=UserTest

# With coverage
docker-compose exec app php artisan test --coverage
```

### Pull Request Checklist

- [ ] Code follows PSR-12 standard
- [ ] All tests passing
- [ ] No merge conflicts with develop
- [ ] Migration files included (jika ada perubahan DB)
- [ ] API documentation updated (jika ada endpoint baru)
- [ ] Commit messages follow convention
- [ ] Branch name descriptive (feature/*, bugfix/*)

---

## Documentation

### Project Documents

- [Laporan Milestone 2](docs/Laporan-Progres-Milestone-2_3SI1_Tim-4.pdf)
- **Database ERD**, lihat di Laporan Milestone 2 (halaman 48-49)
- **API Specification**, dalam pengembangan

### Additional Resources

- [Laravel 12 Documentation](https://laravel.com/docs/12.x)
- [Docker Documentation](https://docs.docker.com)
- [GitLab CI/CD Guide](https://docs.gitlab.com/ee/ci/)
- [Sanctum Documentation](https://laravel.com/docs/12.x/sanctum)

---

## Project Structure

```
desa-cantik-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/    # API Controllers
│   │   ├── Middleware/     # Custom middleware
│   │   └── Resources/      # API Resources
│   ├── Models/             # Eloquent Models
│   ├── Services/           # Business Logic
│   └── Policies/           # Authorization
├── database/
│   ├── migrations/         # Database migrations (15 files)
│   ├── seeders/            # Database seeders (4 files)
│   └── factories/          # Model factories
├── docker/
│   ├── nginx/              # Nginx configuration
│   ├── php/                # PHP-FPM configuration
│   └── mysql/              # MySQL configuration
├── routes/
│   ├── api.php             # API routes
│   └── web.php             # Web routes
├── tests/
│   ├── Feature/            # Feature tests
│   └── Unit/               # Unit tests
├── docs/                   # Project documentation
├── storage/                # Storage & logs
├── docker-compose.yml      # Docker orchestration
├── .env.example            # Environment template
└── README.md               # This file
```

---

## Security

### Pelaporan Vulnerability

Jika menemukan security issue, **JANGAN** buat public issue. Hubungi:

- **Project Manager:** Teguh Christianto Simbolon (222313403@stis.ac.id)
- **Lead Backend Developer:** Alif Zakiansyah As Syauqi (222312958@stis.ac.id)

### Security Features
- JWT Authentication dengan refresh token
- Password hashing (bcrypt)
- SQL Injection prevention (Eloquent ORM)
- XSS protection (Laravel Sanitizer)
- CSRF protection
- Rate limiting
- CORS configuration  

---

## License

Proyek ini dikembangkan untuk keperluan akademik dengan lisensi sebagai berikut.

**Copyright © 2025 Tim 4 - Kelas 3SI1**  
**Politeknik Statistika STIS**

Untuk keperluan pendidikan dan penelitian. Tidak untuk penggunaan komersial tanpa izin.

---

## Contact & Support

### Support

- **Technical Issues:** Buat GitLab Issue
- **Questions:** Hubungi Project Manager atau Lead Backend Developer
- **Documentation:** Periksa di folder `/docs`

### Links

- **GitLab Repository:** https://git.stis.ac.id/rpl-lancarnyaman/desa-cantik-api
- **Project Board:** https://git.stis.ac.id/rpl-lancarnyaman/desa-cantik-api/-/boards
- **Milestones:** https://git.stis.ac.id/rpl-lancarnyaman/desa-cantik-api/-/milestones

---

## Acknowledgments

Terima kasih kepada:
- **Tim Desa Cantik BPS Kabupaten Toraja Utara** atas segala dukungan dan sumber daya yang diberikan.
- **Dosen Pembimbing** atas segala bimbingan dan petunjuk yang diberikan.
- **Politeknik Statistika STIS**
- **Open Source Community**

---

## Project Stats

![GitHub last commit](https://img.shields.io/badge/last%20commit-November%202025-brightgreen)
![GitHub contributors](https://img.shields.io/badge/contributors-6-blue)
![PHP Version](https://img.shields.io/badge/PHP-8.2-777BB4)
![Laravel Version](https://img.shields.io/badge/Laravel-12.37-FF2D20)
![Database](https://img.shields.io/badge/MySQL-8.0-4479A1)

---

<div align="center">

### Dibangun dengan lancar dan nyaman oleh Tim 4 Kelas 3SI1

**Politeknik Statistika STIS • Jakarta • 2025**

[Documentation](docs/) • [Report Bug](https://git.stis.ac.id/rpl-lancarnyaman/desa-cantik-api/-/issues) • [Request Feature](https://git.stis.ac.id/rpl-lancarnyaman/desa-cantik-api/-/issues)

</div>
