# 08 — ADMIN PANEL · RentCar E-Commerce

> Panel administrativo. Misma identidad visual que el cliente (ver design system
> en `07_FRONTEND_GUIDE.md`) pero en layout de dashboard (sidebar + topbar).
> Acceso protegido por roles/permisos (Spatie) + 2FA admin. Crear/cambiar vistas
> admin obliga a actualizar este archivo.

---

## Roles y acceso (resumen, detalle en `11_SECURITY.md`)

| Rol | Acceso |
|-----|--------|
| `admin` | Todo. |
| `staff` | Operación: reservas, entregas, inspecciones, clientes (lectura/edición acotada). Sin configuración ni reembolsos > umbral. |
| `driver` | Solo sus entregas e inspecciones asignadas. |
| `customer` | **Sin** acceso al panel. |

Cada pantalla declara su `permission` requerido.

---

## Pantallas

### Dashboard
- **Función:** visión general operativa y financiera.
- **Datos visibles:** KPIs (reservas hoy/semana, ingresos, ocupación de flota, pagos pendientes, depósitos activos, reviews recientes), alertas (documentos por verificar, autorizaciones de depósito por vencer).
- **Acciones:** navegar a módulos, filtros por rango.
- **Roles:** admin, staff (vista reducida). **Permiso:** `dashboard.view`.

### Vehículos
- **Función:** listar/buscar/filtrar flota.
- **Datos:** nombre, categoría, estado, precio/día, rating, disponibilidad.
- **Acciones:** crear, editar, archivar, gestionar fotos, gestionar disponibilidad.
- **Roles:** admin. **Permiso:** `vehicles.view`.

### Crear vehículo
- **Función:** alta de vehículo.
- **Datos:** ficha completa (marca, modelo, año, categoría, transmisión, asientos, precio, depósito, reglas).
- **Acciones:** guardar, subir fotos, definir reglas de precio.
- **Roles:** admin. **Permiso:** `vehicles.create`.

### Editar vehículo
- **Función:** edición + fotos + disponibilidad + price rules.
- **Acciones:** actualizar, ordenar fotos, marcar principal, bloquear fechas (`vehicle_availability_blocks`).
- **Roles:** admin. **Permiso:** `vehicles.update`.

### Reservas
- **Función:** gestionar todas las reservas.
- **Datos:** número, cliente, vehículo, rango, estado, pago, total.
- **Acciones:** ver detalle, confirmar, cancelar, reprogramar, asignar entrega.
- **Roles:** admin, staff. **Permiso:** `reservations.view`.

### Detalle de reserva
- **Función:** centro de operación de una reserva.
- **Datos:** cliente, vehículo, fechas, entrega, pagos, depósito, contrato, inspecciones, penalidades, timeline (`reservation_status_logs`).
- **Acciones:** cambiar estado, generar contrato, registrar inspección, capturar/liberar depósito, aplicar penalidad, emitir factura, reembolsar.
- **Roles:** admin, staff (acciones acotadas). **Permiso:** `reservations.manage`.

### Clientes
- **Función:** gestionar clientes.
- **Datos:** nombre, contacto, estado de verificación, # reservas, saldo wallet.
- **Acciones:** ver detalle, verificar/rechazar documentos, congelar wallet.
- **Roles:** admin, staff. **Permiso:** `customers.view`.

### Detalle de cliente
- **Función:** ficha 360°.
- **Datos:** perfil, documentos, reservas, pagos, wallet, reviews.
- **Acciones:** aprobar/rechazar documentos, ajustar wallet (`manual_adjustment`), notas.
- **Roles:** admin. **Permiso:** `customers.manage`.

### Pagos
- **Función:** ver y conciliar pagos.
- **Datos:** proveedor, IDs externos, monto, estado, tipo, fecha.
- **Acciones:** ver intentos, reembolsar (según permiso/umbral), exportar.
- **Roles:** admin. **Permiso:** `payments.view` / `payments.refund`.

### Wallet
- **Función:** gestión de monederos.
- **Datos:** saldo, transacciones, reconciliación.
- **Acciones:** ajuste manual (auditado), promo credit, congelar.
- **Roles:** admin. **Permiso:** `wallet.manage`.

### Depósitos
- **Función:** ciclo de vida de depósitos.
- **Datos:** reserva, tipo (hold/capture/release), estado, vencimiento de autorización.
- **Acciones:** capturar total/parcial, liberar, ver evidencia.
- **Roles:** admin. **Permiso:** `deposits.manage`.

### Entregas
- **Función:** logística de entregas/devoluciones.
- **Datos:** reserva, tipo (home/pickup_point/office...), zona, punto, ventana horaria, distancia, tarifa, estado, responsable.
- **Acciones:** asignar responsable, cambiar estado, reprogramar ventana.
- **Roles:** admin, staff, driver (las suyas). **Permiso:** `deliveries.manage`.

### Zonas de entrega (configuración en mapa)
- **Función:** **dibujar y editar en un mapa las zonas permitidas** (polígonos geofence) que verá el cliente; definir tarifas por distancia y puntos comerciales.
- **Datos visibles:** lista de zonas (`delivery_zones`) con polígono, color, `allows_home_delivery`, `base_fee`, `free_radius_km`, `price_per_km`, `max_distance_km`, estado; puntos de entrega (`delivery_pickup_points`); ventanas horarias (`delivery_time_windows`).
- **Acciones:**
  - Crear/editar/eliminar zona dibujando el polígono en el mapa; asignar color y origen de referencia para distancia.
  - Activar/desactivar entrega a domicilio por zona y configurar parámetros de distancia/tarifa.
  - Gestionar **puntos de entrega comerciales** (alta/baja, ubicación en mapa, tarifa, horarios).
  - Configurar **ventanas horarias** (rango de hora, días, capacidad) globales o por zona.
- **Roles:** admin. **Permiso:** `delivery_zones.manage`.
- **Seguridad:** cambios auditados (`audit_logs`).

### Inspecciones
- **Función:** registrar/ver inspección inicial y final.
- **Datos:** combustible, km, daños, fotos, firma.
- **Acciones:** crear inspección, adjuntar fotos, derivar penalidad/captura de depósito.
- **Roles:** admin, staff, driver. **Permiso:** `inspections.manage`.

### Contratos
- **Función:** generar y archivar contratos.
- **Datos:** número, estado, PDF, firma.
- **Acciones:** generar, descargar, anular.
- **Roles:** admin. **Permiso:** `contracts.manage`.

### Calificaciones
- **Función:** moderar reviews.
- **Datos:** rating, comentario, cliente, vehículo, estado (visible/hidden).
- **Acciones:** ocultar/mostrar, responder (futuro).
- **Roles:** admin. **Permiso:** `reviews.moderate`.

### Reportes
- **Función:** inteligencia operativa.
- **Datos:** ingresos por rango, ocupación, top vehículos, tasa de cancelación, depósitos capturados.
- **Acciones:** filtrar, exportar CSV/PDF.
- **Roles:** admin. **Permiso:** `reports.view`.

### Configuración
- **Función:** ajustes generales (`settings`).
- **Datos:** moneda, impuestos, depósito por defecto, política de cancelación, tiempos de expiración, claves de proveedores (referencia, no secretos en UI).
- **Acciones:** editar settings.
- **Roles:** admin. **Permiso:** `settings.manage`.

### Auditoría
- **Función:** trazabilidad de acciones sensibles.
- **Datos:** usuario, acción, entidad, valores antes/después, ip, fecha.
- **Acciones:** filtrar, exportar (solo lectura).
- **Roles:** admin. **Permiso:** `audit.view`.

---

## Reglas de seguridad transversales (panel)
- 2FA obligatorio para `admin`.
- Toda acción que modifica dinero/estado registra `audit_logs`.
- Reembolsos y ajustes de wallet por encima de umbral requieren rol `admin`.
- Documentos, contratos y fotos de inspección se sirven con URLs firmadas temporales.
- Rate limiting y CSRF en formularios. Ver `11_SECURITY.md`.
