# MCP Tools Reference

## Query Tools

### get_outflow_types

Obtiene todos los tipos de egreso activos.

**Parámetros:**
| Nombre | Tipo | Requerido | Default | Descripción |
|--------|------|-----------|---------|-------------|
| idUser | int | No | 1 | ID del usuario |

**Respuesta exitosa:**
```json
{
  "success": true,
  "count": 3,
  "items": [
    { "id": 1, "name": "Gastos", "status": 1 },
    { "id": 2, "name": "Inversión", "status": 1 },
    { "id": 3, "name": "Ahorro", "status": 1 }
  ]
}
```

---

### get_inflow_types

Obtiene todos los tipos de ingreso activos.

**Parámetros:**
| Nombre | Tipo | Requerido | Default | Descripción |
|--------|------|-----------|---------|-------------|
| idUser | int | No | 1 | ID del usuario |

**Respuesta exitosa:**
```json
{
  "success": true,
  "count": 2,
  "items": [
    { "id": 1, "name": "Salario", "status": 1 },
    { "id": 2, "name": "Freelance", "status": 1 }
  ]
}
```

---

### get_categories

Obtiene las categorías de egreso, opcionalmente filtradas por tipo.

**Parámetros:**
| Nombre | Tipo | Requerido | Default | Descripción |
|--------|------|-----------|---------|-------------|
| idOutflowType | int | No | null | Filtrar por tipo de egreso |

**Respuesta exitosa:**
```json
{
  "success": true,
  "count": 5,
  "items": [
    { "id": 1, "name": "Comida", "type_id": 1 },
    { "id": 2, "name": "Transporte", "type_id": 1 },
    { "id": 3, "name": "Servicios", "type_id": 1 }
  ]
}
```

---

### get_available_by_deposits

Obtiene todos los depósitos con su balance financiero (ingresos - egresos).

**Parámetros:**
| Nombre | Tipo | Requerido | Default | Descripción |
|--------|------|-----------|---------|-------------|
| idUser | int | No | 1 | ID del usuario |

**Respuesta exitosa:**
```json
{
  "success": true,
  "count": 2,
  "items": [
    {
      "id_porcent": 1,
      "name": "Cuenta Principal",
      "status": 1,
      "total_income": 5000000,
      "total_outflow": 3200000,
      "available_balance": 1800000
    }
  ]
}
```

---

### get_deposits_history

Obtiene el historial mensual de ingresos y egresos.

**Parámetros:**
| Nombre | Tipo | Requerido | Default | Descripción |
|--------|------|-----------|---------|-------------|
| idUser | int | No | 1 | ID del usuario |

**Respuesta exitosa:**
```json
[
  { "date": "2026-01", "income": 3000000, "expense": 1500000, "balance": 1500000 },
  { "date": "2026-02", "income": 3000000, "expense": 1800000, "balance": 2700000 }
]
```

---

### get_outflows_by_month

Obtiene los egresos de un mes específico.

**Parámetros:**
| Nombre | Tipo | Requerido | Default | Descripción |
|--------|------|-----------|---------|-------------|
| yearMonth | string | Sí | - | Formato YYYY-MM (ej: "2026-03") |
| idUser | int | No | 1 | ID del usuario |

**Respuesta exitosa:**
```json
{
  "month": "2026-03",
  "total_outflows": 450000,
  "count": 12,
  "outflows": [
    {
      "id_outflow": 1,
      "amount": 50000,
      "description": "Mercado",
      "set_date": "2026-03-01",
      "is_in_budget": 1,
      "outflow_type": "Gastos",
      "category": "Comida",
      "deposit": "Cuenta Principal"
    }
  ]
}
```

---

### get_expense_forecast

Proyecta los gastos de los próximos 6 meses basándose en promedio histórico.

**Parámetros:**
| Nombre | Tipo | Requerido | Default | Descripción |
|--------|------|-----------|---------|-------------|
| idUser | int | No | 1 | ID del usuario |

**Respuesta exitosa:**
```json
{
  "forecast": [
    { "month": "2026-04", "name": "Abr", "projected": 280000 },
    { "month": "2026-05", "name": "May", "projected": 295000 }
  ],
  "total": 1750000,
  "method": "seasonal_avg"
}
```

---

## Action Tools

### inflow_money

Crea un nuevo registro de ingreso.

**Parámetros:**
| Nombre | Tipo | Requerido | Default | Descripción |
|--------|------|-----------|---------|-------------|
| idInflowType | int | Sí | - | ID del tipo de ingreso |
| total | float | Sí | - | Monto total del ingreso (> 0) |
| porcents | array | Sí | - | Array de {idPorcent, porcent} (suma = 100) |
| description | string | Sí | - | Descripción del ingreso |
| setDate | string | No | fecha actual | Fecha del ingreso (YYYY-MM-DD) |
| idUser | int | No | 1 | ID del usuario |
| dryRun | bool | No | false | Solo validar sin persistir |

**Estructura de porcents:**
```json
[
  { "idPorcent": 1, "porcent": 70 },
  { "idPorcent": 2, "porcent": 30 }
]
```

**Ejemplo de llamada:**
```json
{
  "tool": "inflow_money",
  "idInflowType": 1,
  "total": 3000000,
  "porcents": [
    { "idPorcent": 1, "porcent": 100 }
  ],
  "description": "Salario febrero"
}
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Ingreso creado exitosamente.",
  "inflow": {
    "id": 15,
    "total": 3000000,
    "date": "2026-02-01",
    "type": "Salario",
    "deposits": [
      { "idPorcent": 1, "porcent": 100, "depositName": "Cuenta Principal" }
    ]
  }
}
```

---

### outflow_money

Crea un nuevo registro de egreso.

**Parámetros:**
| Nombre | Tipo | Requerido | Default | Descripción |
|--------|------|-----------|---------|-------------|
| idOutflowType | int | Sí | - | ID del tipo de egreso |
| idCategory | int | Sí | - | ID de la categoría |
| idPorcent | int | Sí | - | ID del depósito |
| amount | float | Sí | - | Monto (> 0, <= balance disponible) |
| isInBudget | bool | Sí | - | Si está en presupuesto |
| description | string | Sí | - | Descripción del egreso |
| setDate | string | No | fecha actual | Fecha del egreso (YYYY-MM-DD) |
| idUser | int | No | 1 | ID del usuario |
| dryRun | bool | No | false | Solo validar sin persistir |

**Nota:** Si el tipo de egreso contiene "inversion", automáticamente crea un registro de inversión.

**Ejemplo de llamada:**
```json
{
  "tool": "outflow_money",
  "idOutflowType": 1,
  "idCategory": 1,
  "idPorcent": 1,
  "amount": 50000,
  "isInBudget": true,
  "description": "Mercado semanal"
}
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Egreso creado exitosamente.",
  "outflow": {
    "id": 42,
    "amount": 50000,
    "date": "2026-03-15",
    "type": "Gastos",
    "category": "Comida",
    "deposit": "Cuenta Principal"
  },
  "investment_created": false
}
```

---

## Códigos de Error

| Código | Descripción | Solución |
|--------|-------------|----------|
| USER_NOT_FOUND | Usuario no existe o inactivo | Verificar ID de usuario |
| TYPE_NOT_FOUND | Tipo de egreso/ingreso no válido | Usar get_outflow_types/get_inflow_types |
| CATEGORY_NOT_FOUND | Categoría no válida para el tipo | Usar get_categories |
| DEPOSIT_NOT_FOUND | Depósito no válido o sin fondos | Usar get_available_by_deposits |
| INSUFFICIENT_BALANCE | Balance insuficiente | Reducir monto o usar otro depósito |
| INVALID_PERCENTAGE | Porcentajes no suman 100 | Ajustar array porcents |
