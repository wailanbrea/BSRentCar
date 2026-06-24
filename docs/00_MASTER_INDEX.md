# 00 — MASTER INDEX · RentCar E-Commerce

> **Fuente de verdad del proyecto.** Cualquier IA o desarrollador que trabaje en
> RentCar **debe** leer este archivo primero y seguir el orden de lectura.

---

## 1. Nombre del proyecto

**RentCar E-Commerce** — Plataforma web profesional de renta de vehículos.

## 2. Descripción general

Plataforma e-commerce donde clientes pueden registrarse, completar perfil, subir
documentos, vincular métodos de pago (Stripe / PayPal), mantener una wallet
interna, explorar un catálogo de vehículos, filtrar por fecha/precio/categoría/
transmisión/pasajeros/ubicación, reservar por fecha y hora, elegir punto de
entrega, pagar la renta y el depósito de seguridad, firmar contrato digital, ver
historiales y calificar la experiencia (1–5 estrellas).

Un panel administrativo gestiona clientes, vehículos, fotos, disponibilidad,
reservas, pagos, depósitos, wallet, entregas, inspecciones, contratos, reportes,
auditoría, calificaciones y configuración general.

## 3. Orden obligatorio de lectura

Toda IA debe leer, en este orden, antes de tocar código:

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

## 4. Reglas para IA (resumen — detalle en `AI_RULES.md`)

Ciclo obligatorio de trabajo:

```txt
Leer documentación → Entender tarea → Revisar archivos relacionados →
Proponer plan corto → Implementar → Probar →
Actualizar documentación → Actualizar TODO → Actualizar AI Work Log
```

Prohibido:

- Modificar arquitectura sin documentarlo en `14_DECISIONS_LOG.md`.
- Crear tablas sin actualizar `04_DATABASE_SCHEMA.md`.
- Crear endpoints sin actualizar `06_API_CONTRACTS.md`.
- Crear vistas sin actualizar `07_FRONTEND_GUIDE.md` / `08_ADMIN_PANEL.md`.
- Cambiar reglas de negocio sin actualizar `02_BUSINESS_RULES.md`.
- Cambiar pagos sin actualizar `09_PAYMENTS_WALLET.md` y `17_PAYMENT_PROVIDERS.md`.
- Completar tareas sin actualizar `13_TODO_ROADMAP.md`.
- Trabajar sin registrar en `15_AI_WORK_LOG.md`.
- Borrar logs de otras IA.
- Asumir decisiones pendientes sin registrarlas en `20_OPEN_QUESTIONS.md`.

## 5. Archivos principales (índice completo)

| #  | Archivo | Contenido |
|----|---------|-----------|
| 00 | [00_MASTER_INDEX.md](00_MASTER_INDEX.md) | Este índice maestro |
| 01 | [01_PROJECT_CONTEXT.md](01_PROJECT_CONTEXT.md) | Contexto y visión del producto |
| 02 | [02_BUSINESS_RULES.md](02_BUSINESS_RULES.md) | Reglas de negocio |
| 03 | [03_TECH_STACK.md](03_TECH_STACK.md) | Stack técnico |
| 04 | [04_DATABASE_SCHEMA.md](04_DATABASE_SCHEMA.md) | Esquema de base de datos |
| 05 | [05_MODULES.md](05_MODULES.md) | Módulos del sistema |
| 06 | [06_API_CONTRACTS.md](06_API_CONTRACTS.md) | Contratos de API |
| 07 | [07_FRONTEND_GUIDE.md](07_FRONTEND_GUIDE.md) | Guía frontend cliente + design system |
| 08 | [08_ADMIN_PANEL.md](08_ADMIN_PANEL.md) | Panel administrativo |
| 09 | [09_PAYMENTS_WALLET.md](09_PAYMENTS_WALLET.md) | Lógica financiera y wallet |
| 10 | [10_RESERVATIONS_FLOW.md](10_RESERVATIONS_FLOW.md) | Flujo de reservas |
| 11 | [11_SECURITY.md](11_SECURITY.md) | Seguridad |
| 12 | [12_TESTING_QA.md](12_TESTING_QA.md) | Testing y QA |
| 13 | [13_TODO_ROADMAP.md](13_TODO_ROADMAP.md) | Roadmap y tareas |
| 14 | [14_DECISIONS_LOG.md](14_DECISIONS_LOG.md) | Bitácora de decisiones |
| 15 | [15_AI_WORK_LOG.md](15_AI_WORK_LOG.md) | Bitácora de trabajo de IA |
| 16 | [16_HANDOFF_PROMPT.md](16_HANDOFF_PROMPT.md) | Prompt reusable de handoff |
| 17 | [17_PAYMENT_PROVIDERS.md](17_PAYMENT_PROVIDERS.md) | Stripe & PayPal en detalle |
| 18 | [18_DEPLOYMENT_GUIDE.md](18_DEPLOYMENT_GUIDE.md) | Guía de despliegue |
| 19 | [19_ENVIRONMENT_VARIABLES.md](19_ENVIRONMENT_VARIABLES.md) | Variables de entorno |
| 20 | [20_OPEN_QUESTIONS.md](20_OPEN_QUESTIONS.md) | Preguntas pendientes |

Raíz: [README.md](../README.md) · [AI_RULES.md](../AI_RULES.md) · [CHANGELOG.md](../CHANGELOG.md)

## 6. Estado actual del proyecto

| Aspecto | Estado |
|---------|--------|
| Documentación base (`/docs`) | ✅ Creada (Fase 0) |
| Proyecto Laravel | ⏳ Pendiente de aprobación del usuario |
| Base de datos | ⏳ No migrada |
| Pagos | ⏳ No implementados |
| Frontend | ⏳ No implementado |
| App móvil | ⏳ Futuro |

**Fase actual:** Fase 0 — Documentación y arquitectura.

## 7. Próxima tarea recomendada

> **Crear el proyecto Laravel base** y configurar MySQL, Sanctum, Spatie
> Permission y la estructura modular descrita en `05_MODULES.md`.
> **No iniciar sin aprobación del usuario.**

## 8. Convenciones rápidas

- **Idioma de documentación:** español. **Código / identificadores:** inglés.
- **Dinero:** nunca `float`. Usar `decimal(12,2)` en BD o enteros en centavos. Ver `09_PAYMENTS_WALLET.md`.
- **Fechas:** UTC en BD; mostrar en zona del cliente.
- **Disponibilidad:** por rango de fechas, validada en transacción. Ver `10_RESERVATIONS_FLOW.md`.
