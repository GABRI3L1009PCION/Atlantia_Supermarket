# Lanzamiento operativo final Atlantia

Este bloque deja tres auditorias ejecutables antes de produccion:

## 1. Roles y permisos

Comando:

```bash
php artisan atlantia:audit-rbac
```

Valida:
- roles base obligatorios
- permisos catalogados
- diferencias entre permisos esperados y permisos reales por rol
- permisos restringidos asignados a roles indebidos

## 2. Limpieza de demo y sandbox

Comando:

```bash
php artisan atlantia:audit-demo-data
```

Busca datos sospechosos en:
- usuarios
- vendedores
- productos
- pedidos
- tickets de soporte

Patrones auditados:
- `@atlantia.test`
- `@example.com`
- `.test`
- `demo`
- `sandbox`
- `sample`
- `prueba`

## 3. Operacion real por actor

Comando:

```bash
php artisan atlantia:operational-readiness
```

Revisa estructura minima para:
- vendedor
- cliente
- administrador

Tambien deja visibles los escenarios manuales obligatorios que aun deben correrse con datos reales.

## Escenarios manuales obligatorios antes de produccion

### Vendedor
- aceptar pedido real
- preparar pedido real
- marcar pedido listo
- cancelar por falta de inventario
- coordinar con logistica un cambio de tiempo o incidencia

### Cliente
- checkout con efectivo y cambio
- checkout con POS al entregar
- checkout con transferencia al entregar
- seguimiento de pedido con notificaciones
- codigo de entrega y cierre real
- reclamo o reintento de entrega

### Administrador
- reasignacion de pedido en simultaneo
- conciliacion de efectivo
- validacion de retiro de repartidor
- atencion de ticket real
- verificacion de auditoria
- validacion de reportes con varios pedidos reales

## Criterio de salida

No publicar si alguno de estos comandos devuelve `error`.

Si `atlantia:operational-readiness` devuelve `warn`, la estructura tecnica puede estar lista, pero todavia faltan pruebas manuales de negocio en staging o piloto real.
