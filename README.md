<div align="center">

<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/8f/SENA_Colombia_logo.svg/320px-SENA_Colombia_logo.svg.png" width="100" alt="Logo SENA"/>

# 🎓 Sistema de Juicios Evaluativos SENA

**Plataforma web de gestión académica para instructores del Servicio Nacional de Aprendizaje — SENA**

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?style=flat&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Nginx](https://img.shields.io/badge/Nginx-Production-009639?style=flat&logo=nginx&logoColor=white)](https://nginx.org)
[![License](https://img.shields.io/badge/Licencia-MIT-green.svg)](LICENSE)

</div>

---

## 📋 Descripción General

El **Sistema de Juicios Evaluativos SENA** es una plataforma web institucional diseñada para que los instructores del SENA gestionen de manera integral el proceso académico de sus fichas de formación.

El sistema permite importar, visualizar y analizar los juicios evaluativos de los aprendices, identificar riesgos de deserción y emitir alertas formales a Bienestar al Aprendiz / Coordinación Académica.

### 🌐 Acceso en Producción

> **URL:** [https://juicios-evaluativos.frcode.online](https://juicios-evaluativos.frcode.online)

---

## ✨ Módulos y Funcionalidades

### 🏠 Dashboard Principal
- Indicadores clave (KPIs) en tiempo real: total de aprendices, juicios calificados, en riesgo y tasa de aprobación.
- Gráficas de distribución por estado académico.
- Listado de aprendices en situación crítica.
- Actualización automática vía AJAX cada 30 segundos.

### 👥 Listado Maestro de Aprendices
- Visualización paginada con filtros por **ficha, estado y búsqueda libre**.
- Ordenamiento dinámico: **alfabético (A-Z / Z-A)**, **por número de documento**, **por estado** y **más recientes**.
- Exportación del listado a **Excel (.xlsx)** respetando los filtros y el orden activo.
- Acceso directo al expediente individual de cada aprendiz.
- Descarga del **expediente académico en PDF** por aprendiz.

### 📁 Gestión de Fichas
- CRUD completo de fichas de formación.
- Asociación con programas de formación y competencias.
- Modal de confirmación estilizado para eliminación de fichas.

### 📤 Importación Masiva (Sofia Plus)
- Carga el **«Reporte de Juicios de Evaluación»** exportado de Sofia Plus (`.xls`, `.xlsx` o `.csv`).
- Las columnas se ubican **por el texto de su encabezado**, no por posición: funciona con exportaciones de 10 columnas y con las que traen una columna extra (la fecha y el funcionario cambian de lugar entre exportaciones).
- Se leen del archivo: ficha, programa (código, versión, modalidad), tipo y número de documento (CC/TI…), estado del aprendiz, competencia, resultado de aprendizaje, juicio, **fecha y hora reales del juicio** y funcionario que lo registró.
- Solo el texto exacto `APROBADO` cuenta como aprobado; `POR EVALUAR` y `NO APROBADO` quedan pendientes. Cualquier otro valor se avisa.
- Si seleccionas una ficha y el archivo es de otra, la importación se **rechaza** (no se importa en silencio a la ficha equivocada).
- Cada fila se procesa en un *savepoint*: una fila con error se omite y se reporta, sin perder las demás.
- Una aprobación hecha a mano en la matriz **no se borra** si el Excel todavía dice `POR EVALUAR` (configurable en `config/sena.php`).
- Reporte detallado de juicios importados, aprendices, filas omitidas y advertencias; historial completo en *Historial de importaciones*.

### 📊 Matriz Interactiva de Calificación
- Calificación de juicios evaluativos en tiempo real por aprendiz y competencia.
- Actualización individual **vía AJAX** (sin recargar la página).
- **Guardado en lote** de múltiples calificaciones con un solo clic.
- Filtros por ficha y competencia.

### 🚦 Semáforo Predictivo de Deserción
- Análisis automático del **score de riesgo** de cada aprendiz basado en juicios pendientes.
- Clasificación visual: 🔴 **Crítico** · 🟡 **Moderado** · 🟢 **En seguimiento**
- Selección múltiple de aprendices para emisión de alertas grupales.

### 🔍 Detector de Cuellos de Botella Académicos
- **Ranking de competencias** con mayor concentración de juicios pendientes en la ficha.
- Aislamiento del **"Grupo de Refuerzo Pedagógico"** por competencia específica.
- Contacto directo por **WhatsApp** para citación a refuerzo.
- Enlace directo a la **Matriz de Calificación** preconfigurada con la competencia crítica.

### 📣 Sistema de Remisiones y Alertas a Bienestar
- Emisión de **alertas masivas oficiales** a Bienestar al Aprendiz / Coordinación.
- Registro en base de datos con **número de radicado único** (`REM-YYYY-XXXX`).
- **Envío de correo electrónico institucional** (`BIENESTAR_EMAIL`) con plantilla HTML oficial; si no está configurado o falla, el sistema lo informa en lugar de aparentar éxito.
- Generación de **Oficio Institucional de Remisión en PDF** con membrete SENA, listo para radicar.
- **Bandeja de Remisiones**: historial completo, KPIs de casos, filtros y gestión de estados.
- Cambio de estado de atención: Pendiente · En Acompañamiento · Atendido · Cerrado.

### 🔐 Sistema de Autenticación
- Login seguro con diseño **split-screen glassmorphism** y foto institucional del campus SENA.
- Citas rotativas institucionales con animación.
- Protección de **todas las rutas** con middleware `auth`.
- Perfil de usuario y botón de **Cerrar Sesión** integrado en el sidebar.
- Gestión de sesiones persistentes ("Mantener sesión iniciada").

---

## 🛠 Stack Tecnológico

| Capa | Tecnología | Versión |
|------|-----------|---------|
| **Backend** | PHP | 8.3 |
| **Framework** | Laravel | 13 |
| **Base de Datos** | PostgreSQL | 16 |
| **Frontend** | HTML5 + CSS3 + JavaScript | — |
| **Gráficas** | Chart.js | CDN |
| **Iconos** | Font Awesome | 6.7 |
| **Tipografía** | Google Fonts — Inter | — |
| **PDF** | barryvdh/laravel-dompdf | 3.1 |
| **Excel** | Maatwebsite/Excel (PhpSpreadsheet) | 3.1 |
| **Build Tool** | Vite | 8.0 |
| **Servidor Web** | Nginx | — |

---

## 🗄 Estructura de la Base de Datos

```
aprendiz            → Datos del aprendiz y su ficha
ficha               → Fichas de formación con jornada
programa            → Programas de formación SENA
competencia         → Competencias por programa
resultados          → Resultados de aprendizaje por competencia
juicios_evaluativos → Calificaciones (APROBADO / PENDIENTE / etc.)
importaciones       → Historial de cargas masivas
remisiones          → Casos reportados a Bienestar
users               → Usuarios administradores del sistema
```

---

## 🚀 Instalación Local

### Requisitos Previos
- PHP 8.3 con extensiones: `pgsql`, `gd`, `zip`, `xml`, `mbstring`, `curl`
- Composer
- Node.js 20+
- PostgreSQL 15+

### Pasos

```bash
# 1. Clonar el repositorio
git clone https://github.com/Fabian08-Developer/Juicios-Evaluativos.git
cd Juicios-Evaluativos

# 2. Instalar dependencias PHP
composer install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Configurar la base de datos en .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=juicios_evaluativos
# DB_USERNAME=postgres
# DB_PASSWORD=tu_contraseña

# 5. Ejecutar migraciones y crear el administrador
#    (opcional: define ADMIN_EMAIL / ADMIN_PASSWORD / BIENESTAR_EMAIL en .env antes)
php artisan migrate --force
php artisan db:seed --class=AdminSeeder   # si no defines ADMIN_PASSWORD, muestra una clave aleatoria UNA vez

# 6. Instalar y compilar assets
npm install
npm run dev

# 7. Iniciar servidor de desarrollo
php artisan serve
```

Accede en: `http://127.0.0.1:8000/login`

---

## 🌐 Despliegue en VPS (Ubuntu 24.04)

### Requisitos del Servidor

```bash
sudo apt install -y php8.3 php8.3-fpm php8.3-pgsql php8.3-gd \
    php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
    nginx postgresql git composer nodejs npm
```

### Configuración de Nginx

```nginx
server {
    listen 80;
    server_name juicios-evaluativos.tudominio.com;
    root /home/deploy/proyectos/juicios-evaluativos/public;

    index index.php;
    charset utf-8;
    client_max_body_size 25M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Comandos de Despliegue

```bash
cd /home/deploy/proyectos/juicios-evaluativos

git pull origin main
composer install --optimize-autoloader --no-dev
npm install && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

> El `AdminSeeder` solo se ejecuta en la **primera** instalación. Es idempotente (no pisa la contraseña de un usuario que ya existe), pero ya no forma parte de cada despliegue.

### Producción: ajustes obligatorios en `.env`

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio
MAIL_MAILER=smtp            # con MAIL_HOST / MAIL_USERNAME / MAIL_PASSWORD reales
BIENESTAR_EMAIL=bienestar@tu-centro.edu.co
```

### SSL Gratuito con Certbot (HTTPS)

```bash
sudo certbot --nginx -d juicios-evaluativos.tudominio.com
```

---

## 🔑 Primer acceso y contraseñas

El sistema **ya no trae una contraseña por defecto**. `AdminSeeder` crea el administrador (`ADMIN_EMAIL`, por defecto `admin@sena.edu.co`) con la clave de `ADMIN_PASSWORD`, o con una aleatoria que se muestra una sola vez.

Para cambiar una contraseña (por ejemplo, la antigua `Sena2026*` si tu instalación es anterior a este cambio):

```bash
php artisan sena:cambiar-clave admin@sena.edu.co
```

> ⚠️ Si tu instalación se creó con la contraseña publicada anteriormente en este README, **cámbiala ahora**.

El login bloquea temporalmente tras varios intentos fallidos (`config/sena.php`).

### Comandos útiles

| Comando | Para qué sirve |
|---|---|
| `php artisan sena:cambiar-clave {email}` | Cambia una contraseña sin dejarla en el historial del shell |
| `php artisan sena:reporte-general` | Resumen estadístico en consola |
| `php artisan sena:detectar-duplicados` | Revisa inconsistencias de datos |
| `php artisan sena:limpiar-funcionarios [--aplicar]` | Elimina funcionarios inválidos creados por el importador anterior (simulación por defecto) |

---

## 📁 Estructura del Proyecto

```
├── app/
│   ├── Http/Controllers/
│   │   ├── Auth/LoginController.php
│   │   ├── AprendizController.php
│   │   ├── DashboardController.php
│   │   ├── FichaController.php
│   │   ├── ImportacionController.php
│   │   └── InnovacionAcademicaController.php
│   ├── Models/
│   │   ├── Aprendiz.php
│   │   ├── Ficha.php
│   │   ├── Remision.php
│   │   └── ...
│   ├── Exports/AprendicesExport.php
│   ├── Imports/ReporteSofiaImport.php
│   ├── Services/            (ImportadorJuiciosService, RiesgoDesercionService)
│   ├── Support/ReporteSofiaPlus.php   (interpreta el reporte por encabezados)
│   └── Mail/AlertaBienestarMail.php
├── config/sena.php          (correo de Bienestar, umbrales de riesgo, login, importación)
├── database/
│   ├── migrations/
│   └── seeders/AdminSeeder.php
├── public/images/login-bg.jpg
├── resources/views/
│   ├── auth/login.blade.php
│   ├── layouts/app.blade.php
│   ├── dashboard.blade.php
│   ├── aprendices/
│   ├── fichas/
│   ├── acciones/
│   ├── remisiones/
│   └── emails/
└── routes/web.php
```

---

## 🧪 Pruebas

```bash
composer test                      # SQLite en memoria (rápido; omite 1 prueba específica de PostgreSQL)

# Contra PostgreSQL (el motor de producción), incluida la prueba del savepoint por fila:
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432 DB_DATABASE=juicios_test \
DB_USERNAME=postgres DB_PASSWORD=secreto php artisan test
```

Los tests generan reportes `.xls` sintéticos con la misma estructura que Sofia Plus (sin datos personales reales). **No subas reportes reales al repositorio**: contienen datos personales de aprendices.

> La zona horaria por defecto es `America/Bogota` (`APP_TIMEZONE`). Los registros creados antes de este cambio se guardaron en UTC.

---

## 👤 Autor

**Fabian Ramos** — Instructor / Desarrollador SENA
📧 leiderfabianramoscano99@gmail.com
🔗 [github.com/Fabian08-Developer](https://github.com/Fabian08-Developer)

---

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Consulta el archivo [LICENSE](LICENSE) para más detalles.

---

<div align="center">
  <sub>Desarrollado con ❤️ para el <strong>Servicio Nacional de Aprendizaje — SENA</strong> · Colombia 🇨🇴</sub>
</div>
