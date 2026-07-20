# Presupuestos — Workflows

## Ciclo de vida del documento

1. **Borrador** — crear/editar (receptor, emisor, líneas, anexos, términos).
2. **Enviar** — app y/o correo; opcional notificar receptor en app.
3. **Receptor** — listado “recibidos”, notificación, o enlace público.
4. **Aceptar / rechazar** — autenticado o vía token público.
5. **Duplicar / reenviar** — desde presupuesto existente.

## PDF

- Preview sin guardar: `POST …/generar-pdf`
- PDF de guardado: `GET …/{presupuesto}/pdf`
- Tema PDF configurable por presupuesto

## Cartera y emisor

- Alta/edición de clientes de cartera en modal de selección (flujo crear presupuesto).
- Tarjetas emisor/receptor en perfil; se usan como snapshot al emitir.
