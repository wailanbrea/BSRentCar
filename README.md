# RentCar E-Commerce

Plataforma web profesional de **renta de vehículos**: catálogo con filtros,
reservas por fecha/hora, pagos con Stripe y PayPal, wallet interna, depósitos de
seguridad, contratos digitales, inspecciones de salida/retorno, entregas,
calificaciones y un panel administrativo completo con auditoría.

> **Estado:** Fase 0 — Documentación y arquitectura completada. El código de
> negocio **aún no** ha comenzado (a la espera de aprobación).

---

## 📚 Documentación (fuente de verdad)

Toda la memoria técnica del proyecto vive en [`docs/`](docs/). **Empieza aquí:**

1. [`docs/00_MASTER_INDEX.md`](docs/00_MASTER_INDEX.md) — índice maestro y orden de lectura.
2. [`AI_RULES.md`](AI_RULES.md) — reglas obligatorias para toda IA/dev.
3. [`docs/16_HANDOFF_PROMPT.md`](docs/16_HANDOFF_PROMPT.md) — prompt reusable para continuar.

| Doc | Tema |
|-----|------|
| [01](docs/01_PROJECT_CONTEXT.md) | Contexto y visión |
| [02](docs/02_BUSINESS_RULES.md) | Reglas de negocio |
| [03](docs/03_TECH_STACK.md) | Stack técnico |
| [04](docs/04_DATABASE_SCHEMA.md) | Esquema de BD |
| [05](docs/05_MODULES.md) | Módulos |
| [06](docs/06_API_CONTRACTS.md) | Contratos de API |
| [07](docs/07_FRONTEND_GUIDE.md) | Frontend + design system |
| [08](docs/08_ADMIN_PANEL.md) | Panel admin |
| [09](docs/09_PAYMENTS_WALLET.md) | Pagos y wallet |
| [10](docs/10_RESERVATIONS_FLOW.md) | Flujo de reservas |
| [11](docs/11_SECURITY.md) | Seguridad |
| [12](docs/12_TESTING_QA.md) | Testing / QA |
| [13](docs/13_TODO_ROADMAP.md) | Roadmap |
| [14](docs/14_DECISIONS_LOG.md) | Decisiones |
| [15](docs/15_AI_WORK_LOG.md) | Bitácora de IA |
| [17](docs/17_PAYMENT_PROVIDERS.md) | Stripe & PayPal |
| [18](docs/18_DEPLOYMENT_GUIDE.md) | Despliegue |
| [19](docs/19_ENVIRONMENT_VARIABLES.md) | Variables de entorno |
| [20](docs/20_OPEN_QUESTIONS.md) | Preguntas abiertas |

---

## 🧱 Stack

- **Backend:** Laravel 12, PHP 8.3+, MySQL (InnoDB), Sanctum, Spatie Permission, Queues, Scheduler, DomPDF/Browsershot, storage privado.
- **Frontend web:** Blade + Tailwind CSS + Alpine.js (+ Livewire selectivo).
- **Pagos:** Stripe y PayPal detrás de una abstracción `PaymentGatewayInterface`.
- **App móvil (futuro):** Kotlin · Jetpack Compose · Retrofit · Room · Hilt · Coroutines.

---

## 🚀 Puesta en marcha (cuando exista el proyecto Laravel)

```bash
composer install
cp .env.example .env
php artisan key:generate
# configurar .env (ver docs/19_ENVIRONMENT_VARIABLES.md)
php artisan migrate --seed
php artisan storage:link
npm install && npm run dev
php artisan serve
```

Despliegue en producción: ver [`docs/18_DEPLOYMENT_GUIDE.md`](docs/18_DEPLOYMENT_GUIDE.md).

---

## 🤖 Para IA y colaboradores

Este proyecto está diseñado para que **múltiples IA** (Claude Code, Codex, Gemini,
ChatGPT, etc.) trabajen sin perder contexto. Antes de tocar código, **lee
[`AI_RULES.md`](AI_RULES.md)** y sigue el ciclo:

```txt
Leer docs → Entender → Revisar archivos → Plan corto → Implementar →
Probar → Actualizar docs → Actualizar TODO → Actualizar AI Work Log
```

Reglas no negociables: dinero sin `float`, disponibilidad por rango validada en
transacción, no guardar tarjetas reales, webhooks firmados e idempotentes,
documentar todo cambio.

---

## 📄 Licencia
Privado / propietario (definir).
