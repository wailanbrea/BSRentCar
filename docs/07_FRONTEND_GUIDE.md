# 07 — FRONTEND GUIDE · RentCar E-Commerce

> Interfaz del **cliente** (web). Mobile-first. Stack: Blade + Tailwind + Alpine
> (+ Livewire selectivo). El design system se inspira en el mockup de referencia
> entregado por el usuario (`original-*.webp`). Crear/cambiar vistas obliga a
> actualizar este archivo.

---

## 0. Design System (derivado del mockup)

### Paleta de color (Tailwind tokens sugeridos)

| Token | Hex | Uso |
|-------|-----|-----|
| `primary` (royal blue) | `#2563EB` | Botones principales, iconos, enlaces de acción, "Subscribe", "Watch Video", "Contact Us" |
| `primary-dark` | `#1D4ED8` | Hover de primary |
| `navy` (surface oscura) | `#0B1437` | Secciones "Premium Service" y banda CTA "Ready To Hit The Road" |
| `navy-2` | `#0A1A3F` | Gradiente/variante de navy |
| `slate` (secundario) | `#64748B` | Botones secundarios tipo "Rent Now" |
| `bg` | `#F8FAFC` / `#FFFFFF` | Fondos de sección claros |
| `text` | `#0F172A` | Texto principal |
| `text-muted` | `#64748B` | Texto secundario / descripciones |
| `hero-gradient` | violet `#7C6FF0` → sunset `#F4A26B` | Fondo del hero |

### Tipografía
- Familia: sans geométrica redondeada (**Poppins** para headings, **Inter** para body), fallback `system-ui`.
- Headings: bold/semibold, tracking ligeramente ajustado.
- Body: `text-slate-500/600`, line-height holgado.

### Forma y profundidad
- Bordes: `rounded-2xl` en cards, `rounded-full` en botones/píldoras y avatares.
- Sombra: `shadow-sm`/`shadow-md` suave; cards sobre fondo claro.
- Espaciado generoso; layout en grid con `gap-6`.
- Botón primario: fondo `primary`, texto blanco, `rounded-full`, padding cómodo.
- Botón secundario ("Rent Now"): fondo `slate`, texto blanco, `rounded-lg`.
- Botón outline (nav "Get The App"): borde blanco/`primary`, fondo transparente, `rounded-full`.

### Patrón de página
- Secciones alternan **fondo claro** y **bandas navy** para ritmo visual.
- Hero a sangre con gradiente + imagen de vehículo recortada.
- Header sticky translúcido sobre el hero.

### Componentes reutilizables (Blade components)
`x-button`, `x-card`, `x-vehicle-card`, `x-rating-stars`, `x-input`, `x-select`,
`x-date-range`, `x-badge-status`, `x-empty-state`, `x-section`, `x-navbar`,
`x-footer`, `x-modal`, `x-toast`, `x-delivery-map`, `x-time-window-picker`.

> **`x-delivery-map`:** mapa interactivo donde el cliente ve las **zonas de
> entrega permitidas** (polígonos pintados con el `color` de cada zona), los
> **puntos de entrega comerciales** (pins) y puede ubicar su domicilio. Indica si
> su dirección es elegible para entrega a domicilio y muestra la **tarifa por
> distancia** calculada. **Implementación: Leaflet + OpenStreetMap** (sin API key),
> pin manual + búsqueda Nominatim opcional, **detrás de un wrapper `MapProvider`**
> para poder cambiar a Google/Mapbox en el futuro. Ver `14_DECISIONS_LOG.md`.

### Responsive (mobile-first)
- Grid de catálogo: `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`.
- Nav colapsa a menú hamburguesa (Alpine) en `< md`.
- Hero apila texto sobre imagen en móvil.
- Touch targets ≥ 44px.

### Accesibilidad
- Contraste AA, foco visible, labels en inputs, `alt` en imágenes, navegación por teclado en modales.

---

## 1. Páginas

Para cada pantalla: objetivo, componentes, datos, acciones, estados vacíos, errores.

### Home
- **Objetivo:** convertir visitante en reserva; mostrar valor y deals.
- **Componentes:** navbar, hero (headline + "Choose a Car"), buscador (fecha/ubicación), "Explore Our Deal" (grid de `x-vehicle-card`), "Premium Service" (4 features en navy), "Process To Book" (4 pasos), "Why Choose Us", banda CTA, testimonios, footer.
- **Datos:** vehículos destacados, settings.
- **Acciones:** buscar, ir a catálogo, ver detalle, registrarse.
- **Vacío:** sin vehículos → mensaje + CTA "Próximamente".
- **Errores:** fallback si no cargan destacados.

### Catálogo
- **Objetivo:** explorar y filtrar vehículos disponibles.
- **Componentes:** barra de filtros (`x-date-range`, categoría, transmisión, pasajeros, precio, ubicación), grid de `x-vehicle-card`, paginación, orden.
- **Datos:** `GET /vehicles?filters`.
- **Acciones:** aplicar/limpiar filtros, ordenar, paginar, abrir detalle, "Rent Now".
- **Vacío:** "No hay vehículos para esos filtros" + sugerencia de ampliar fechas.
- **Errores:** toast en fallo de carga; conservar filtros.

### Detalle de vehículo
- **Objetivo:** decidir y reservar.
- **Componentes:** galería de fotos, nombre/marca, precio/día, características, reglas, selector de fechas/horas, punto de entrega, calificaciones (`x-rating-stars` + reviews), botón "Reservar".
- **Datos:** `GET /vehicles/{id}`, `GET /vehicles/{id}/availability`, `GET /vehicles/{id}/reviews`.
- **Acciones:** elegir fechas → cotizar → "Reservar" (→ checkout).
- **Vacío:** sin reviews → "Aún sin calificaciones".
- **Errores:** 404 vehículo; fechas no disponibles → mensaje inline.

### Login / Registro
- **Objetivo:** autenticación.
- **Componentes:** formularios, validación inline, enlaces (recuperar contraseña).
- **Datos:** `POST /auth/login|register`.
- **Errores:** credenciales inválidas, email duplicado, rate limit.

### Dashboard cliente
- **Objetivo:** centro del cliente.
- **Componentes:** resumen (próxima reserva, saldo wallet, estado verificación), accesos a perfil/documentos/reservas/wallet/pagos.
- **Datos:** perfil, próxima reserva, wallet.
- **Vacío:** sin reservas → CTA explorar catálogo.

### Perfil
- **Objetivo:** completar/editar datos personales.
- **Componentes:** formulario, estado de verificación.
- **Datos/Acciones:** `GET/PUT /customer/profile`.
- **Errores:** 422 por campo.

### Documentos
- **Objetivo:** subir licencia/ID.
- **Componentes:** uploader (drag&drop), lista con estado (pending/approved/rejected).
- **Datos/Acciones:** `POST /customer/documents`, `GET` lista.
- **Vacío:** "Sube tu licencia para poder reservar".
- **Errores:** tipo/tamaño inválido.

### Mis reservas
- **Objetivo:** historial y gestión.
- **Componentes:** lista con `x-badge-status`, filtros por estado.
- **Datos:** `GET /customer/reservations`.
- **Vacío:** "Aún no tienes reservas".

### Detalle de reserva
- **Objetivo:** ver estado, pagos, contrato, entrega, inspección.
- **Componentes:** timeline de estados, totales, botones (pagar, cancelar, firmar contrato, calificar si completed).
- **Datos:** `GET /reservations/{id}`.
- **Acciones:** pagar, cancelar, firmar, calificar.

### Wallet
- **Objetivo:** ver saldo y movimientos.
- **Componentes:** tarjeta de saldo, lista de transacciones (`type`, `amount`, `balance_after`).
- **Datos:** `GET /wallet`, `GET /wallet/transactions`.
- **Vacío:** "Sin movimientos".

### Métodos de pago
- **Objetivo:** gestionar tarjetas/PayPal tokenizados.
- **Componentes:** lista de métodos (brand, last_four, default), añadir (Stripe SetupIntent / PayPal vault), marcar default, eliminar.
- **Errores:** fallo de tokenización.

### Historial de pagos
- **Objetivo:** ver pagos/reembolsos.
- **Componentes:** tabla (fecha, tipo, proveedor, monto, estado), descarga de factura.
- **Datos:** pagos del cliente.
- **Vacío:** "Sin pagos".

### Calificaciones
- **Objetivo:** calificar reservas completadas y ver propias.
- **Componentes:** `x-rating-stars` por categoría, comentario.
- **Acciones:** `POST /reservations/{id}/review`.
- **Errores:** 403/409 (no autorizada / ya calificada).

### Checkout
- **Objetivo:** elegir entrega y pagar renta + autorizar depósito.
- **Componentes:**
  - **Selector de entrega** con `x-delivery-map`: el cliente ve las zonas permitidas, elige `home` (si su domicilio cae en zona que admite domicilio) o un **punto de entrega comercial** cercano; se muestra la **tarifa por distancia**.
  - **`x-time-window-picker`:** elegir la ventana horaria de entrega disponible (BR-E09).
  - Resumen de reserva, selección de método (Stripe/PayPal/wallet), desglose (base, **delivery_fee por distancia**, tax, deposit (hold), total), términos.
- **Acciones:** validar elegibilidad del cliente (edad ≥18 + licencia aprobada), cotizar entrega, crear intent/order, confirmar/capturar, **autorizar depósito (hold)**.
- **Estados vacíos:** sin zonas activas → solo entrega en oficina; domicilio fuera de zona → ofrecer puntos comerciales.
- **Errores:** domicilio fuera de cobertura, distancia > máximo, ventana sin cupo, pago fallido, requires_action (3DS), no disponible, **licencia no aprobada**.

### Mapa de cobertura / zonas de entrega
- **Objetivo:** que el cliente vea **antes de reservar** dónde hay entrega.
- **Componentes:** `x-delivery-map` con polígonos de zonas activas + pins de puntos comerciales; buscador de dirección.
- **Datos:** `GET /delivery/zones`, `GET /delivery/pickup-points`.
- **Acciones:** verificar si una dirección es elegible (`POST /delivery/quote`).
- **Vacío:** "Aún no hay zonas de entrega configuradas".

### Confirmación de pago
- **Objetivo:** feedback post-pago.
- **Componentes:** estado, número de reserva, próximos pasos (firmar contrato).
- **Estados:** éxito, pendiente (processing), fallo.

### Contrato
- **Objetivo:** ver y firmar/aceptar contrato.
- **Componentes:** visor PDF, checkbox de aceptación, botón firmar.
- **Acciones:** firmar (registra ip/ua/hash).
- **Estados:** pending → signed.

---

## 2. Flujo de checkout (resumen UX)

```txt
Detalle vehículo → elegir fechas/entrega → Reservar (crea reserva pending_payment)
→ Checkout (elegir método) → Pago renta → (Depósito) → Confirmación
→ Firmar contrato → Reserva confirmada
```

## 3. Estados y feedback
- Loading: skeletons en cards/listas.
- Toasts para éxito/error.
- Badges de estado con color por categoría (ver `x-badge-status`): success=verde, pending=amber, danger=rojo, info=blue, neutral=slate.
