# Mapa de pendientes ClickUp — Presupuestos + generalidades

> **No es contexto operativo.** No sustituye `docs/context/`.  
> Última actualización: 2026-08-01 (cierre jornada)  
> Alcance: tareas ClickUp de presupuestos y aspectos generales de la app (no catálogo de productos; no SP salvo outlier anotado).

**Estados de tarea:** `pendiente` | `en_curso` | `hecho` | `hecho · review`  
**Orden de ataque acordado:** A → H → F → C → D → E → G → B → Z

---

## Índice ClickUp → oleada

| ID ClickUp | Título corto | Oleada | Estado |
|------------|--------------|--------|--------|
| 86aht2zpz | Buscador unidades + “otro” | A | **hecho** |
| 86aj0nc3v | Form conceptos: unificar modal → página catálogo | A | **hecho** |
| 86aj3y86n | Totalizar opcional | A | **hecho** |
| 86aj4b9zb | Ocultar total / IVA / importe letra | A | **hecho · review** |
| 86aj541y2 | Párrafo postdata | A | pendiente |
| 86ahkrr1d | Términos: investigar formas de pago | A | pendiente (solo investigación) |
| 86ahha5cu | Corregir y agregar “Más términos” | A | **hecho** |
| 86agutpem | Histórico estados / fechas aceptación-rechazo | A | **hecho** |
| 86aht32jb | Notificación: nº, empresa, título, tipo | A | pendiente |
| 86aj0n50y | Preview sin preguntar guardar | A | **hecho** |
| 86aj4b61q | Vista preliminar cuadrada | A | **hecho · review** |
| 86aj4b779 | Márgenes PDF / `ppto_config` | A | **hecho** |
| wdx6zev52j | Icono sin borde en app (borde solo impresión) | A | **hecho** |
| wdx6zerea7 | QR pie de página no funciona | A | **hecho** (rutas alineadas; validar `APP_FRONTEND_URL`) |
| wdx6zetawe | Mayúscula automática 1ª letra (nombres propios) | A | **hecho** |
| 86ahxkanc | Imagen en concepto (print vs preview) | A | **hecho** |
| wdx6zev52b | Usuarios: agregar | H | pendiente |
| wdx6zev52c | Usuarios: facultades | H | pendiente |
| 86ah82rb5 | Excluir admin/prueba de estadísticas | H | pendiente |
| 86af16v4f | Sección compartir información | F | pendiente |
| 86ahct5vm | Botones compartir (invitar, cédula, bancos, tarjeta) | F | pendiente |
| 86agv2bbd | Solicitud permiso info bancaria | F | pendiente |
| wdx6zeuxr4 | Ver/corregir datos bancos al compartir | F | pendiente |
| 86ahxk9mz | Evitar doble enlace en invitación | F | pendiente |
| 86ae5jcfj | Invitación DG: validaciones desistibles | F | pendiente |
| 86ah87jnh | Mi empresa: 3ª tarjeta recibe/envía | C | pendiente |
| 86ah5qggq | Mi empresa: quién recibe presupuesto | C | pendiente |
| 86ah5q5u3 | Mi empresa: quién envía presupuesto | C | pendiente |
| 86ahaf9b3 | Dirigido a quién según tarjetas | C | pendiente |
| 86ahcqpap | Cédula fiscal: confirmar reemplazo | D | pendiente |
| 86ahcrf12 | Régimen fiscal: elegir de cédula + default | D | pendiente |
| 86ahdjumu | Número americano fiscal | D | pendiente |
| 86ah5q5j8 | Apellido paterno y materno | D | pendiente |
| 86aj4b1nu | Editar imagen perfil sin recargar | D | pendiente |
| 86ahj0t1k | Detallar catálogo Empresas y clientes | E | pendiente |
| 86agv28yu | Empresas registradas visibles para todos | E | pendiente |
| 86ahdc9xj | Opt-in listado red de proveedores | E | pendiente |
| 86ah6ygvb | Seed ≥30 empresas Construc | E | pendiente (outlier datos/SP) |
| 86ahcnymh | Borrar aceptados → bandeja eliminados | G | pendiente |
| 86ahcnyxn | Marcar pagado + comprobante | G | pendiente |
| wdx6zeunk3 | Ventana en blanco al actualizar | B | pendiente |
| 86aj1hjwx | Ajuste tamaño de ventanas | B | pendiente |
| 86agj0a6a | Socialite | Z | pendiente |
| 86agz21gv | Guardar usuario/contraseña iPhone | Z | pendiente |
| wdx6zev52a | Cerrar sesión | Z | pendiente |
| 86ahcrgfw | PayPal y Stripe | Z | pendiente |

**URLs ClickUp:** `https://app.clickup.com/t/90131590108/{id}`

---

## Acuerdos de implementación — Oleada A

### Hechos en este ciclo (jornada 2026-08-01)
- A1–A2 unidades + Otro (modal captura y form catálogo); control imagen catálogo = mismo card Reajustar/Cambiar/Quitar que captura.
- A8 `presupuesto_estado_logs` + sheet historial (listado cards + final del preview); rechazo con motivo = corrección; reenvío desde rechazo.
- A9 notificaciones con nº, empresa, `concepto_general`, tipo de evento.
- A12 `ppto_config` completo (8 keys mm) en modal Ajustes + API/Blade; gap logo default 7 mm.
- A13 iconos app sin borde / impresión con borde.
- A14 QR URL = `/public/presupuesto/{token}` (validar env `frontend_url`).
- A15 sentence case nombres propios presupuestos.
- Regla UX: **Solicitar aprobación** solo emisor (`esEmisorSesion`); el receptor no ve ese botón.

### Pendientes de A
- A5 postdata (`86aj541y2`)
- A6 investigación formas de pago (`86ahkrr1d`)

### Ya estaban cerrados
- A3, A4·review, A7, A10, A11·review, `86ahxkanc`

### Siguiente oleada
**H** (usuarios / facultades / stats)

---

## Oleadas (orden)

A → H → F → C → D → E → G → B → Z

---

## Cómo actualizar

1. Al terminar una tarea ClickUp: poner **hecho** (o **hecho · review**) en la tabla índice.
2. No mover ítems entre oleadas sin acordar el orden.
3. Tareas del día pueden ir en `.TODO` con enlace a este mapa.
