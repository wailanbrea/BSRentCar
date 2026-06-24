# AI_RULES — Reglas para toda IA / Desarrollador · RentCar E-Commerce

> **Léeme antes de tocar nada.** Estas reglas son obligatorias para Claude Code,
> OpenAI Codex, Gemini, ChatGPT y cualquier otro agente o persona que trabaje en
> este repositorio. La carpeta [`docs/`](docs/) es la **fuente de verdad**.

---

## 1. Orden de lectura obligatorio

Antes de cualquier tarea, lee en este orden:

```txt
docs/00_MASTER_INDEX.md
docs/01_PROJECT_CONTEXT.md
docs/02_BUSINESS_RULES.md
docs/03_TECH_STACK.md
docs/04_DATABASE_SCHEMA.md
docs/05_MODULES.md
docs/06_API_CONTRACTS.md
docs/09_PAYMENTS_WALLET.md
docs/10_RESERVATIONS_FLOW.md
docs/11_SECURITY.md
docs/13_TODO_ROADMAP.md
docs/14_DECISIONS_LOG.md
docs/15_AI_WORK_LOG.md
docs/17_PAYMENT_PROVIDERS.md
docs/20_OPEN_QUESTIONS.md
```

## 2. Ciclo de trabajo obligatorio

```txt
Leer documentación
↓
Entender tarea
↓
Revisar archivos relacionados
↓
Proponer plan corto
↓
Implementar
↓
Probar
↓
Actualizar documentación
↓
Actualizar TODO (docs/13_TODO_ROADMAP.md)
↓
Actualizar AI Work Log (docs/15_AI_WORK_LOG.md)
```

## 3. Prohibido

- ❌ Modificar la arquitectura sin documentarlo en `docs/14_DECISIONS_LOG.md`.
- ❌ Crear tablas sin actualizar `docs/04_DATABASE_SCHEMA.md`.
- ❌ Crear endpoints sin actualizar `docs/06_API_CONTRACTS.md`.
- ❌ Crear vistas sin actualizar `docs/07_FRONTEND_GUIDE.md` o `docs/08_ADMIN_PANEL.md`.
- ❌ Cambiar reglas de negocio sin actualizar `docs/02_BUSINESS_RULES.md`.
- ❌ Cambiar pagos sin actualizar `docs/09_PAYMENTS_WALLET.md` y `docs/17_PAYMENT_PROVIDERS.md`.
- ❌ Completar tareas sin actualizar `docs/13_TODO_ROADMAP.md`.
- ❌ Trabajar sin registrar en `docs/15_AI_WORK_LOG.md`.
- ❌ Borrar logs de otras IA.
- ❌ Asumir decisiones pendientes: regístralas en `docs/20_OPEN_QUESTIONS.md`.

## 4. Reglas técnicas no negociables

- 💰 **Dinero:** nunca `float`. `decimal(12,2)` o centavos enteros. Cálculos con BCMath/enteros. (`docs/09_PAYMENTS_WALLET.md`)
- 📅 **Disponibilidad:** por rango de fechas, validada **dentro de una transacción de BD** con `lockForUpdate`. Solape: `new_start < existing_end AND new_end > existing_start`. (`docs/10_RESERVATIONS_FLOW.md`)
- 💳 **Pagos:** no guardar tarjetas reales; solo tokens. Webhooks con **firma validada** e **idempotentes**. (`docs/17_PAYMENT_PROVIDERS.md`)
- 🔐 **Seguridad:** HTTPS, storage privado para documentos/contratos/inspecciones, roles/permisos (Spatie), 2FA admin, auditoría. (`docs/11_SECURITY.md`)
- 🧩 **Abstracción de pagos:** la lógica de negocio nunca llama SDKs directamente; usa `PaymentGatewayInterface`.

## 5. Calidad de código (Laravel)

- **Services** para lógica de negocio (no en controladores).
- **Form Requests** para validación.
- **Policies** para autorización.
- **API Resources** para respuestas API.
- **Enums** (PHP 8.3) para estados.
- **Jobs** para tareas pesadas; **Events/Listeners** para procesos secundarios.
- **Migrations** limpias; **Seeders** para datos de prueba; **Factories** para tests.
- **Tests** para flujos críticos (`docs/12_TESTING_QA.md`).
- Formato PSR-12 (Pint).

## 6. Estilo de trabajo

- Sé directo, trabaja como senior.
- No inventes decisiones de negocio que no estén claras → `docs/20_OPEN_QUESTIONS.md`.
- Decisión técnica → `docs/14_DECISIONS_LOG.md`.
- Trabajo realizado → `docs/15_AI_WORK_LOG.md`.
- Tareas completadas → marca `[x]` en `docs/13_TODO_ROADMAP.md`.
- Cambios visibles del proyecto → `CHANGELOG.md`.

## 7. Definición de "hecho" (Definition of Done)

Una tarea está terminada solo si:
1. El código funciona y está probado.
2. La documentación afectada está actualizada.
3. El roadmap refleja el avance.
4. Hay una entrada en el AI Work Log con pruebas reales y pendientes declarados.
