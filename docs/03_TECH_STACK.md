# 03 — TECH STACK · RentCar E-Commerce

## 1. Backend

| Tecnología | Versión / Nota |
|-----------|----------------|
| **Laravel** | 12.x |
| **PHP** | 8.2+ (entorno local actual: 8.2.12 vía XAMPP; **8.3+ recomendado** en producción). Laravel 12 requiere PHP ≥ 8.2. |
| **Base de datos** | MySQL 8.x |
| **Autenticación API** | Laravel Sanctum (tokens para web SPA y app móvil) |
| **Roles y permisos** | Spatie laravel-permission |
| **Colas** | Laravel Queues (driver `database` en MVP, Redis recomendado en prod) |
| **Tareas programadas** | Laravel Scheduler (expiración de reservas, liberación de depósitos, recordatorios) |
| **PDF / Contratos** | **DomPDF** (decidido para MVP — contratos y facturas, sin headless Chrome). Browsershot/Puppeteer queda como opción futura para alta fidelidad. Ver `14_DECISIONS_LOG.md`. |
| **Moneda / Impuesto** | Base **DOP** (peso dominicano); **ITBIS 18%** (`settings.tax_rate`). Multi-moneda (USD) a futuro. |
| **Firma de contratos** | Firma electrónica **simple** con validez legal bajo **Ley 126-02 (RD)**: aceptación + hash SHA-256 del PDF + IP + user-agent + timestamp. Firma avanzada (ECD acreditada por INDOTEL) a futuro. |
| **Storage privado** | Disk `local`/privado para documentos, contratos e inspecciones. URLs temporales firmadas. |

## 2. Frontend web (inicial)

| Tecnología | Nota |
|-----------|------|
| **Laravel Blade** | Plantillas server-side |
| **Tailwind CSS** | Estilos utilitarios; design system en `07_FRONTEND_GUIDE.md` |
| **Alpine.js** | Interactividad ligera (dropdowns, modales, filtros) |
| **Livewire** | **Decidido:** Livewire **selectivo** para componentes reactivos (checkout, filtros de catálogo, panel admin). |

> **Decisión (2026-06-24):** **Blade + Tailwind + Alpine con Livewire selectivo**
> (no Inertia/SPA en MVP). Ver `14_DECISIONS_LOG.md`.

## 3. Pagos

- **Stripe** (Payment Intents, Setup Intents, auth/capture, refunds, webhooks).
- **PayPal** (Orders API, capture, refunds, webhooks).
- **Abstracción**: `PaymentGatewayInterface` + `StripePaymentGateway` + `PayPalPaymentGateway`.
  La lógica de negocio nunca llama directamente al SDK del proveedor. Ver `17_PAYMENT_PROVIDERS.md`.

## 3b. Mapas y geolocalización

- **Mapa:** **Leaflet** + **OpenStreetMap** (tiles OSM), sin API key, en el editor de zonas (admin) y el mapa de cobertura (cliente).
- **Geocoding:** pin manual del cliente; búsqueda opcional con **Nominatim** (OSM).
- **Distancia:** **Haversine** (línea recta) para el cobro de entrega a domicilio.
- **Abstracción (clave):** ambos detrás de interfaces para cambiar de proveedor sin tocar negocio:
  - Frontend: `MapProvider` (impl. `LeafletMapProvider`; futuro `GoogleMapsProvider`/`MapboxProvider`).
  - Backend: `DistanceCalculatorInterface` (impl. `HaversineDistanceCalculator`; futuro `RoutingDistanceCalculator` con API de ruta).
- Geometría de zonas: polígonos **GeoJSON** en `delivery_zones.polygon`; el "punto-en-polígono" se evalúa en backend (PHP) y/o en el cliente (Leaflet).

## 4. App móvil futura (NO desarrollar todavía)

La API REST (Sanctum) se diseña pensando en:

- **Kotlin** + **Jetpack Compose** (UI)
- **Retrofit** (HTTP)
- **Room** (cache local)
- **Hilt** (DI)
- **Coroutines** (asincronía)

Implicaciones de diseño: respuestas JSON estables y versionadas (`/api/v1/...`),
paginación consistente, tokens Bearer, errores con formato uniforme.

## 5. Base de datos

- **MySQL 8.x**, motor InnoDB (transacciones + FK obligatorias).
- Migraciones limpias y versionadas.
- Índices en claves de búsqueda (fechas de reserva, `vehicle_id`, `status`).
- Dinero en `decimal(12,2)` o columnas `*_cents` enteras (ver `09_PAYMENTS_WALLET.md`).

## 6. Herramientas de calidad

- **Pest / PHPUnit** para tests (ver `12_TESTING_QA.md`).
- **Laravel Pint** para formato PSR-12.
- **Larastan / PHPStan** (opcional) para análisis estático.
- **Enums** de PHP 8.3 para estados.

## 7. Infraestructura recomendada

| Recurso | Recomendación |
|---------|--------------|
| **VPS** | 2 vCPU / 4 GB RAM mínimo para arrancar (Hetzner, DigitalOcean, Linode). Escalar según tráfico. |
| **Web server** | Nginx + PHP-FPM. |
| **HTTPS** | Obligatorio. Let's Encrypt / Certbot, redirección 80→443, HSTS. |
| **Backups** | BD diaria (mysqldump/automatizado) + storage; retención ≥ 7 días, off-site. |
| **Colas en prod** | Redis + `php artisan queue:work` supervisado (Supervisor/systemd). |
| **Scheduler** | Un cron `* * * * * php artisan schedule:run`. |
| **Logs** | Rotación; nivel `warning`+ en prod. Auditoría en `audit_logs`. |

Detalle operativo en `18_DEPLOYMENT_GUIDE.md` y variables en `19_ENVIRONMENT_VARIABLES.md`.

## 8. Convenciones de código

Ver sección 8 del Master Prompt y `AI_RULES.md`:
Services (lógica), Form Requests (validación), Policies (autorización),
API Resources (respuestas), Enums (estados), Jobs (tareas pesadas),
Events/Listeners (procesos secundarios), Seeders (datos de prueba), Tests
(flujos críticos). **Sin lógica compleja en controladores.**
