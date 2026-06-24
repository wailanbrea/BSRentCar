# 18 — DEPLOYMENT GUIDE · RentCar E-Commerce

> Guía de despliegue para producción. Ver variables en `19_ENVIRONMENT_VARIABLES.md`
> y seguridad en `11_SECURITY.md`.

---

## 1. Requisitos del servidor
- Linux (Ubuntu LTS recomendado).
- PHP 8.3+ con extensiones: `bcmath, ctype, curl, dom, fileinfo, json, mbstring, openssl, pdo_mysql, tokenizer, xml, gd/imagick`.
- MySQL 8.x.
- Nginx + PHP-FPM.
- Composer 2.x, Node.js LTS + npm.
- Redis (colas/cache en prod, recomendado).
- (Si se usa Browsershot) Node + Puppeteer/Chromium headless.
- Certbot (Let's Encrypt) para HTTPS.
- Supervisor o systemd para el queue worker.

## 2. Instalación
```bash
git clone <repo> rentcar
cd rentcar
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# editar .env (DB, Stripe, PayPal, Mail, APP_URL, etc.)
php artisan migrate --force
php artisan db:seed --force        # roles/permisos/admin/settings base
npm install
npm run build
php artisan storage:link
php artisan config:cache route:cache view:cache
```

## 3. Variables `.env`
Configurar todas las de `19_ENVIRONMENT_VARIABLES.md` (DB, Stripe, PayPal, Mail,
`FILESYSTEM_DISK`, `QUEUE_CONNECTION`). `APP_ENV=production`, `APP_DEBUG=false`.

## 4. Migraciones y seeders
```bash
php artisan migrate --force
php artisan db:seed --force
```
Seeders mínimos: roles/permisos (Spatie), usuario admin inicial, settings base
(moneda, impuesto, depósito por defecto, política de cancelación).

## 5. Storage link y discos privados
```bash
php artisan storage:link
```
- Documentos, contratos e inspecciones en disco **privado** (no enlazado a `public`).
- Servir esos archivos solo con URLs firmadas temporales + autorización.

## 6. Scheduler
Cron (una sola línea):
```cron
* * * * * cd /var/www/rentcar && php artisan schedule:run >> /dev/null 2>&1
```
Tareas programadas: expirar holds de reservas, vencimiento de autorizaciones de
depósito, recordatorios, conciliación de pagos.

## 7. Queue worker
Con Supervisor (`/etc/supervisor/conf.d/rentcar-worker.conf`):
```ini
[program:rentcar-worker]
command=php /var/www/rentcar/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/rentcar/storage/logs/worker.log
stopwaitsecs=3600
```
```bash
supervisorctl reread && supervisorctl update && supervisorctl start rentcar-worker:*
```

## 8. HTTPS
```bash
sudo certbot --nginx -d tudominio.com -d www.tudominio.com
```
Forzar redirección 80→443, activar HSTS. `APP_URL=https://tudominio.com`.

## 9. Backups
- BD: `mysqldump` diario automatizado (o `spatie/laravel-backup`), off-site, cifrado, retención ≥ 7 días.
- Storage: respaldo de documentos/contratos/inspecciones.
- Probar restauración periódicamente.

## 10. Permisos de carpetas
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
```

## 11. Configuración de webhooks
- **Stripe:** crear endpoint `https://tudominio.com/api/v1/payments/webhooks/stripe`,
  copiar el signing secret a `STRIPE_WEBHOOK_SECRET`.
- **PayPal:** crear webhook en el dashboard apuntando a
  `https://tudominio.com/api/v1/payments/webhooks/paypal`, copiar `PAYPAL_WEBHOOK_ID`.
- Verificar que ambos endpoints estén excluidos de CSRF y protegidos por firma.

## 12. Comandos útiles
```bash
php artisan migrate:status
php artisan queue:work / queue:restart
php artisan schedule:list
php artisan config:clear cache:clear route:clear view:clear
php artisan optimize
php artisan test
# Stripe local: stripe listen --forward-to localhost:8000/api/v1/payments/webhooks/stripe
```

## 13. Checklist de despliegue
- [ ] `.env` completo, `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Migraciones y seeders aplicados
- [ ] `storage:link` y discos privados ok
- [ ] HTTPS + HSTS
- [ ] Scheduler (cron) activo
- [ ] Queue worker supervisado
- [ ] Webhooks Stripe/PayPal configurados y verificados
- [ ] Backups automatizados y probados
- [ ] Caches de config/route/view generadas
