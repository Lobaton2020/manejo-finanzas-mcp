# Finanzas MCP — Referencia de Tools

Servidor MCP que expone el dominio financiero de la app Manejo-Finanzas sobre MySQL (prod) o SQLite (test). Todas las tools viven bajo `src/MCP/Tools/` agrupadas por dominio.

Convención común para todas las tools:

| Aspecto | Detalle |
|---|---|
| **Formato de respuesta** | JSON con estructura `content[0].text` (protocolo MCP). Texto = `json_encode([...], JSON_PRETTY_PRINT \| JSON_UNESCAPED_UNICODE)`. |
| **Respuesta exitosa** | `{success: true, message: "...", data: {...}}` (vía `BaseTool::successResponse`). Algunas tools pre-existentes usan formato propio. |
| **Respuesta de error** | `{success: false, error: "...", hint: "..."}` (vía `BaseTool::validationError`). El `hint` se genera automáticamente según palabras clave del mensaje. |
| **Manejo de excepciones** | `BaseTool::executeWithLogging` captura toda excepción y devuelve `errorResponse`. |
| **Validación de ownership** | Toda escritura valida que el registro pertenezca al `idUser` antes de modificar. |
| **Logs** | `BaseTool::debug/info/error()` en cada tool + logs automáticos de `executeWithLogging` a `/tmp/finanzas-mcp-logs/YYYY-MM-DD.log`. |

---

## 📑 Índice por dominio

| Dominio | # Tools | Carpeta |
|---|---:|---|
| [Egresos (Outflow)](#egresos-outflow) | 11 | `EgressMoney/` |
| [Ingresos (Inflow)](#ingresos-inflow) | 5 | `InflowMoney/` |
| [Lookups (Tipos/Categorías/Depósitos)](#lookups) | 17 | `Lookups/` |
| [Inversiones](#inversiones) | 8 | `Investments/` |
| [Presupuestos](#presupuestos) | 11 | `Budgets/` |
| [Notas](#notas) | 4 | `Notes/` |
| [Notificaciones](#notificaciones) | 2 | `Notifications/` |
| [Reportes](#reportes) | 2 | `Reports/` |
| **Total** | **60** | |

---

## Egresos (Outflow)

### `outflow_money` — Crear egreso

Crea un egreso. Si el tipo de egreso contiene `"inversion"` (case-insensitive), crea también la fila en `investments`. **Atocada en transacción**: si falla la creación del investment, hace rollback del outflow.

**Input**

| Param | Tipo | Required | Default | Descripción |
|---|---|---|---|---|
| `idOutflowType` | int | ✓ | — | Tipo de egreso (FK `outflowtypes.id_outflow_type`) |
| `idCategory` | int | ✓ | — | Categoría del egreso (FK `categories.id_category`). Debe pertenecer al `idOutflowType` |
| `idPorcent` | int | ✓ | — | Depósito de origen (FK `porcents.id_porcent`). Debe pertenecer al `idUser` |
| `amount` | float | ✓ | — | Monto (> 0). No debe superar el balance disponible del depósito |
| `isInBudget` | bool | ✓ | — | `true` si el egreso cuenta para el presupuesto |
| `description` | string | ✓ | — | Texto libre |
| `setDate` | string? | ✗ | hoy (YYYY-MM-DD) | Fecha del egreso |
| `idUser` | int | ✗ | 1 | Dueño |
| `idGroupInvestment` | int? | ✗ | null | Grupo de inversión. Solo válido si el tipo contiene `"inversion"`. Debe pertenecer al `idUser` |
| `dryRun` | bool | ✗ | false | Si true, valida pero no persiste |

**Output (success)**

```json
{
  "success": true,
  "message": "Egreso creado exitosamente.",
  "outflow": {
    "id": 42,
    "amount": 1000.0,
    "date": "2026-08-15",
    "type": "Inversion Crypto",
    "category": "BTC",
    "deposit": "Efectivo"
  },
  "investment_created": true,
  "id_group_investment": 10
}
```

**Output (validation error)**

```json
{
  "success": false,
  "error": "El balance disponible (500) NO es suficiente para el monto solicitado (1000).",
  "hint": "Usa get_available_by_deposits para ver los depositos y sus balances."
}
```

**Errores posibles**

- `El usuario no existe o esta inactivo.`
- `El tipo de egreso no existe o esta inactivo.`
- `La categoria no existe, esta inactiva o no pertenece al tipo de egreso seleccionado.`
- `El deposito no existe, esta inactivo o no pertenece al usuario.`
- `idGroupInvestment solo aplica cuando el tipo de egreso contiene "inversion".`
- `El grupo de inversion no existe o no pertenece al usuario.`
- `El monto debe ser mayor a 0.`
- `El balance disponible (X) NO es suficiente para el monto solicitado (Y).`

---

### `list_outflows` — Listar egresos paginado

**Input**

| Param | Tipo | Required | Default | Descripción |
|---|---|---|---|---|
| `idUser` | int | ✗ | 1 | Dueño |
| `idOutflowType` | int? | ✗ | null | Filtro por tipo |
| `idCategory` | int? | ✗ | null | Filtro por categoría |
| `idPorcent` | int? | ✗ | null | Filtro por depósito |
| `description` | string? | ✗ | null | LIKE `%desc%` |
| `isInBudget` | int? | ✗ | null | Filtro 0/1 |
| `dateFrom` | string? | ✗ | null | `set_date >= dateFrom` |
| `dateTo` | string? | ✗ | null | `set_date <= dateTo` |
| `sort` | string | ✗ | `id_outflow` | `id_outflow` \| `amount` \| `set_date` \| `description` |
| `order` | string | ✗ | `DESC` | `ASC` \| `DESC` |
| `page` | int | ✗ | 1 | Página |
| `length` | int | ✗ | 50 | Tamaño de página. Valores permitidos: 10, 25, 50, 100. Otro valor → default 50 |

**Output**

```json
{
  "success": true,
  "message": "Egresos listados.",
  "data": {
    "items": [
      {
        "id_outflow": 1,
        "id_outflow_type": 2,
        "id_category": 2,
        "id_porcent": 1,
        "amount": 1500.0,
        "description": "Almuerzo",
        "set_date": "2026-08-10",
        "status": 1,
        "is_in_budget": 1,
        "create_at": "2026-08-10 10:00:00",
        "update_at": "2026-08-10 10:00:00"
      }
    ],
    "pagination": {
      "current": 1,
      "perPage": 50,
      "total": 7,
      "totalPages": 1
    },
    "sort": "id_outflow",
    "order": "DESC",
    "totalAmount": 19998.0
  }
}
```

---

### `get_outflow` — Obtener egreso por id

**Input**: `idOutflow` (int, required), `idUser` (int, default 1)

**Output (success)**:

```json
{
  "success": true,
  "message": "Egreso obtenido.",
  "data": {
    "id_outflow": 1,
    "id_outflow_type": 2,
    "id_category": 2,
    "id_porcent": 1,
    "amount": 1500.0,
    "description": "Almuerzo",
    "set_date": "2026-08-10",
    "status": 1,
    "is_in_budget": 1,
    "create_at": "2026-08-10 10:00:00",
    "update_at": "2026-08-10 10:00:00"
  }
}
```

**Error**: `El egreso no existe o no pertenece al usuario.`

---

### `update_outflow` — Actualizar egreso

Solo modifica campos permitidos: `amount`, `setDate`, `description`, `isInBudget`, `idCategory`. **No** permite cambiar `idOutflowType` ni `idPorcent` (afectan balances).

**Input**

| Param | Tipo | Required | Default | Descripción |
|---|---|---|---|---|
| `idOutflow` | int | ✓ | — | Egreso a actualizar |
| `idUser` | int | ✗ | 1 | Dueño |
| `amount` | float? | ✗ | — | Nuevo monto (> 0) |
| `setDate` | string? | ✗ | — | Nueva fecha |
| `description` | string? | ✗ | — | Nueva descripción |
| `isInBudget` | bool? | ✗ | — | Nuevo flag |
| `idCategory` | int? | ✗ | — | Nueva categoría (debe existir) |

**Output (success)**:

```json
{
  "success": true,
  "message": "Egreso actualizado.",
  "data": {
    "id_outflow": 1,
    "amount": 1800.0,
    "set_date": "2026-08-12",
    "description": "Almuerzo actualizado",
    "is_in_budget": 1,
    "id_category": 2,
    "update_at": "2026-08-15 12:30:00"
  }
}
```

**Errores posibles**

- `El egreso no existe o no pertenece al usuario.`
- `El monto debe ser mayor a 0.`
- `La categoria no existe.`
- `Debes enviar al menos un campo a actualizar.`

---

### `get_outflow_types` — Tipos de egreso del usuario

(Pre-existente, formato legacy)

**Input**: `idUser` (int, default 1)

**Output**: array JSON crudo (sin wrapper `success`)

```json
[
  {"id": 2, "name": "Comida", "status": 1},
  {"id": 1, "name": "Inversion Crypto", "status": 1}
]
```

Si no hay tipos del usuario, fallback a tipos globales. Si no hay nada: `"No hay tipos de egreso activos disponibles. Debe crear al menos uno."`

---

### `get_categories` — Categorías de egreso

(Pre-existente, formato legacy)

**Input**

| Param | Tipo | Default |
|---|---|---|
| `idOutflowType` | int? | null (todas) |

**Output**: array JSON crudo `[{id, name, type_id}]` o `"No hay categorías disponibles."`

---

### `get_available_by_deposits` — Balance disponible por depósito

(Pre-existente)

**Input**: `idUser` (int, default 1)

**Output**: array JSON `[{id_porcent, name, status, create_at, total_income, total_outflow, available_balance}]`

---

### `get_deposits_history` — Historial mensual resumido

(Refactorizado para cross-DB)

**Input**: `idUser` (int, default 1)

**Output**: array JSON `[{date: "YYYY-MM", income, expense, balance}]` ordenado cronológicamente.

---

### `get_outflows_by_month` — Egresos detallados de un mes

(Refactorizado para cross-DB)

**Input**

| Param | Tipo | Required | Default | Descripción |
|---|---|---|---|---|
| `yearMonth` | string | ✓ | — | Formato `YYYY-MM` (regex validado) |
| `idUser` | int? | ✗ | 1 | Dueño |

**Output (success)**: array JSON `[{id_outflow, amount, description, set_date, is_in_budget, outflow_type, category, deposit}]`

**Output (vacío)**:
```json
{"message": "No se encontraron egresos para YYYY-MM", "outflows": []}
```

---

### `get_expense_forecast` — Proyección estacional 6 meses

(Refactorizado para cross-DB)

**Input**: `idUser` (int, default 1)

**Output (success)**:
```json
{
  "forecast": [
    {"month": "2026-09", "name": "Sep", "projected": 1234.56}
  ],
  "total": 7407.36,
  "method": "seasonal_avg"
}
```

**Output (sin datos)**: `"No hay datos"`

---

### `get_investment_groups` — Grupos de inversión

**Input**

| Param | Tipo | Default | Descripción |
|---|---|---|---|
| `idUser` | int | 1 | Dueño |
| `includeInvestmentCount` | bool | false | Si true, agrega `investment_count` por grupo |

**Output**

```json
{
  "success": true,
  "count": 2,
  "groups": [
    {
      "id_group_investment": 10,
      "id_user": 1,
      "name": "Cripto",
      "description": "Inversiones cripto",
      "created_at": "2026-08-01 10:00:00",
      "updated_at": "2026-08-01 10:00:00",
      "investment_count": 3
    }
  ]
}
```

---

### `create_investment_group` — Crear grupo de inversión

**Input**

| Param | Tipo | Required | Default | Descripción |
|---|---|---|---|---|
| `name` | string | ✓ | — | Nombre (no vacío) |
| `idUser` | int | ✗ | 1 | Dueño |
| `description` | string? | ✗ | null | Descripción opcional |

**Output**:
```json
{
  "success": true,
  "message": "Grupo creado.",
  "data": {
    "id_group_investment": 11,
    "id_user": 1,
    "name": "Acciones USA",
    "description": "ETF/SP500"
  }
}
```

**Error**: `El nombre del grupo es requerido.`

---

### `update_investment_group` — Actualizar grupo

**Input**

| Param | Tipo | Required | Default | Descripción |
|---|---|---|---|---|
| `idGroupInvestment` | int | ✓ | — | Grupo a actualizar |
| `idUser` | int | ✗ | 1 | Dueño |
| `name` | string? | ✗ | — | Nuevo nombre |
| `description` | string? | ✗ | — | Nueva descripción |

**Output**: `{success: true, message: "Grupo actualizado.", data: {id_group_investment, name, description, updated_at}}`

**Errores**

- `El grupo no existe.`
- `El grupo no pertenece al usuario.`
- `Debes enviar al menos un campo a actualizar (name o description).`

---

## Ingresos (Inflow)

### `inflow_money` — Crear ingreso con distribución en depósitos

**Input**

| Param | Tipo | Required | Default | Descripción |
|---|---|---|---|---|
| `idInflowType` | int | ✓ | — | Tipo de ingreso (FK `inflowtypes.id_inflow_type`) |
| `total` | float | ✓ | — | Total (> 0) |
| `porcents` | array | ✓ | — | Lista de `{idPorcent, porcent}`. **La suma de `porcent` debe ser exactamente 100**. Cada depósito debe pertenecer al usuario |
| `description` | string | ✓ | — | Texto libre |
| `setDate` | string? | ✗ | hoy | Fecha |
| `idUser` | int | ✗ | 1 | Dueño |
| `dryRun` | bool | ✗ | false | Solo valida |

**Output (success)**:
```json
{
  "success": true,
  "message": "Ingreso creado exitosamente.",
  "inflow": {
    "id": 5,
    "total": 1000.0,
    "date": "2026-08-10",
    "type": "Salario",
    "deposits": [
      {"idPorcent": 1, "porcent": 70, "depositName": "Efectivo"},
      {"idPorcent": 2, "porcent": 30, "depositName": "Banco"}
    ]
  }
}
```

**Errores**

- `El usuario no existe o esta inactivo.`
- `El tipo de ingreso no existe o esta inactivo.`
- `El monto total debe ser mayor a 0.`
- `Debe especificar al menos un deposito con su porcentaje.`
- `Cada elemento de porcents debe tener 'idPorcent' y 'porcent'. Error en indice N.`
- `El deposito con ID X no existe, esta inactivo o no pertenece al usuario.`
- `La suma de los porcentajes debe ser igual a 100. Suma actual: X`

---

### `list_inflows` — Listar ingresos paginado

**Input**

| Param | Tipo | Default | Descripción |
|---|---|---|---|
| `idUser` | int | 1 | Dueño |
| `idInflowType` | int? | null | Filtro |
| `description` | string? | null | LIKE `%desc%` |
| `dateFrom` | string? | null | `set_date >=` |
| `dateTo` | string? | null | `set_date <=` |
| `sort` | string | `id_inflow` | `id_inflow` \| `total` \| `set_date` |
| `order` | string | `DESC` | `ASC` \| `DESC` |
| `page` | int | 1 | Página |
| `length` | int | 50 | 10 \| 25 \| 50 \| 100 |

**Output**: igual a `list_outflows` pero con `id_inflow`, `id_inflow_type`, `total`.

---

### `get_inflow` — Obtener ingreso por id (con distribución)

**Input**: `idInflow` (int, required), `idUser` (int, default 1)

**Output**:
```json
{
  "success": true,
  "message": "Ingreso obtenido.",
  "data": {
    "id_inflow": 1,
    "id_inflow_type": 1,
    "total": 1000.0,
    "description": "Salario",
    "set_date": "2026-08-01",
    "status": 1,
    "create_at": "...",
    "update_at": "...",
    "distribution": [
      {"id_inflow_porcent": 1, "id_porcent": 1, "porcent": 70, "status": 1},
      {"id_inflow_porcent": 2, "id_porcent": 2, "porcent": 30, "status": 1}
    ]
  }
}
```

---

### `update_inflow` — Actualizar ingreso

**Input**: `idInflow` (int, required), `idUser`, `total`, `setDate`, `description`, `idInflowType` (todos opcionales)

**Output**: `{success: true, message: "Ingreso actualizado.", data: {id_inflow, id_inflow_type, total, description, set_date, update_at}}`

---

### `get_inflow_types` — Tipos de ingreso del usuario

(Pre-existente, formato legacy)

**Input**: `idUser` (int, default 1)

**Output**: array JSON crudo `[{id, name, status}]` o `"No hay tipos de ingreso activos disponibles."`

---

## Lookups

CRUD completo (sin delete) para las tablas de catálogo: `outflowtypes`, `inflowtypes`, `categories`, `porcents`.

### `create_outflow_type`

**Input**: `name` (string, required), `idUser` (int, default 1), `status` (int, default 1)

**Output**: `{success: true, data: {id_outflow_type, id_user, name, status}, message: "Tipo de egreso creado exitosamente."}`

---

### `update_outflow_type`

**Input**: `idOutflowType` (int, required), `idUser` (int, default 1), `name` (string?), `status` (int?)

**Output**: `{success: true, data: {id_outflow_type, id_user, name, status}, message: "Tipo de egreso actualizado."}`

---

### `disable_outflow_type` — Soft disable (status=0)

**Input**: `idOutflowType` (int, required), `idUser` (int, default 1)

**Output**: `{success: true, data: {id_outflow_type, status: 0}, message: "Tipo de egreso desactivado."}`

---

### `enable_outflow_type` — Soft enable (status=1)

**Input**: `idOutflowType`, `idUser`

**Output**: `{success: true, data: {id_outflow_type, status: 1}, message: "Tipo de egreso activado."}`

---

### `create_inflow_type`

**Input**: `name`, `idUser`, `status`

**Output**: `{success: true, data: {id_inflow_type, id_user, name, status}, message: "Tipo de ingreso creado exitosamente."}`

---

### `update_inflow_type`

**Input**: `idInflowType`, `idUser`, `name?`, `status?`

**Output**: `{success: true, data: {id_inflow_type, id_user, name, status}, message: "Tipo de ingreso actualizado."}`

---

### `disable_inflow_type`

**Input**: `idInflowType`, `idUser`

**Output**: `{success: true, data: {id_inflow_type, status: 0}, message: "Tipo de ingreso desactivado."}`

---

### `enable_inflow_type`

**Input**: `idInflowType`, `idUser`

**Output**: `{success: true, data: {id_inflow_type, status: 1}, message: "Tipo de ingreso activado."}`

---

### `create_category`

**Input**

| Param | Tipo | Required | Default |
|---|---|---|---|
| `idOutflowType` | int | ✓ | — |
| `name` | string | ✓ | — |
| `idUser` | int | ✗ | 1 |
| `status` | int | ✗ | 1 |

**Errores**

- `El nombre de la categoria es requerido.`
- `El tipo de egreso no existe.`

---

### `update_category`

**Input**: `idCategory` (required), `idUser`, `name?`, `status?`, `idOutflowType?`

**Output**: `{success: true, data: {id_category, id_outflow_type, id_user, name, status}, message: "Categoria actualizada."}`

---

### `disable_category`

**Input**: `idCategory`, `idUser`

**Output**: `{success: true, data: {id_category, status: 0}, message: "Categoria desactivada."}`

---

### `enable_category`

**Input**: `idCategory`, `idUser`

**Output**: `{success: true, data: {id_category, status: 1}, message: "Categoria activada."}`

---

### `create_deposit`

**Input**: `name` (required), `idUser` (default 1), `status` (default 1)

**Output**: `{success: true, data: {id_porcent, id_user, name, status}, message: "Deposito creado exitosamente."}`

---

### `get_deposits` — Listar depósitos del usuario

**Input**: `idUser` (default 1), `includeInactive` (default false)

**Output**:
```json
{
  "success": true,
  "count": 2,
  "deposits": [
    {"id_porcent": 1, "id_user": 1, "name": "Efectivo", "status": 1, "create_at": "..."}
  ]
}
```

---

### `update_deposit`

**Input**: `idPorcent` (required), `idUser`, `name?`, `status?`

**Output**: `{success: true, data: {id_porcent, id_user, name, status}, message: "Deposito actualizado."}`

---

### `disable_deposit`

**Input**: `idPorcent`, `idUser`

**Output**: `{success: true, data: {id_porcent, status: 0}, message: "Deposito desactivado."}`

---

### `enable_deposit`

**Input**: `idPorcent`, `idUser`

**Output**: `{success: true, data: {id_porcent, status: 1}, message: "Deposito activado."}`

---

**Errores comunes a toda la familia Lookups**

- `El tipo X no existe.` (outflowtypes / inflowtypes)
- `La categoria no existe.` (categories)
- `El X no pertenece al usuario.` (ownership)
- `Debes enviar al menos un campo a actualizar.`
- `El nombre es requerido.` (validación `empty(trim($name))`)

---

## Inversiones

### `list_investments` — Listar inversiones (vía `investments_view`)

**Input**

| Param | Tipo | Default | Descripción |
|---|---|---|---|
| `idUser` | int | 1 | Dueño |
| `includeHidden` | bool | false | Si true, incluye `state='Ocultar'` |
| `state` | string? | null | Filtro exacto: `Creado` \| `Activo` \| `Expirado` \| `Cancelado` \| `Completado` \| `Perdido` \| `Ocultar` |
| `idGroupInvestment` | int? | null | Filtro por grupo |

**Output**:
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id_investment": 1,
        "id_outflow": 42,
        "state": "Creado",
        "risk_level": "Conservador",
        "init_date": "2026-08-15",
        "end_date": "2026-09-15",
        "real_retribution": 0.0,
        "percent_annual_effective": 0.0,
        "id_group_investment": 10,
        "group_investment_name": "Cripto",
        "original_amount": 1500.0,
        "amount": 1500.0,
        "earn_amount": null,
        "earn_amount_all": 0.0,
        "description": "compra btc"
      }
    ],
    "count": 1
  }
}
```

---

### `get_investment` — Inversión individual

**Input**: `idInvestment` (required), `idUser` (default 1)

**Output**: misma estructura que un item de `list_investments`.

---

### `update_investment` — Editar campos editables

**Input**

| Param | Tipo | Required | Descripción |
|---|---|---|---|
| `idInvestment` | int | ✓ | Inversión a editar |
| `idUser` | int | ✗ (1) | Dueño (valida vía outflows.id_user) |
| `initDate` | string? | ✗ | Fecha inicio (YYYY-MM-DD) |
| `endDate` | string? | ✗ | Fecha fin |
| `state` | string? | ✗ | Estado nuevo |
| `riskLevel` | string? | ✗ | Nivel de riesgo (`Conservador` \| `Moderado` \| `Agresivo`) |
| `realRetribution` | float? | ✗ | Retribución real acumulada |
| `percentAnnualEffective` | float? | ✗ | Porcentaje anual efectivo |
| `idGroupInvestment` | int? | ✗ | Reasignar grupo (debe pertenecer al usuario) |

**Output**: `{success: true, data: {id_investment, state, risk_level, init_date, end_date, real_retribution, percent_annual_effective, id_group_investment, updated_at}, message: "Inversion actualizada."}`

---

### `hide_investment` — Soft hide (state=`Ocultar`)

**Input**: `idInvestment`, `idUser`

**Output**: `{success: true, data: {id_investment, state: "Ocultar"}, message: "Inversion ocultada."}`

---

### `list_investment_retirements` — Retiros de una inversión

**Input**: `idInvestment` (required), `idUser` (default 1)

**Output**:
```json
{
  "success": true,
  "count": 2,
  "retirements": [
    {
      "id_retirement_investment": 1,
      "id_investment": 1,
      "id_user": 1,
      "descripcion": "Retiro parcial",
      "retirement_amount": 300.0,
      "init_date": "2026-08-20",
      "end_date": "2026-08-25",
      "real_retribution": 20.0,
      "created_at": "..."
    }
  ]
}
```

---

### `create_investment_retirement` — Crear retiro parcial

**Validaciones**

- `retirementAmount > 0`
- `realRetribution > retirementAmount` → rechaza (estricto `>`)
- `retirementAmount <= (original_amount - suma(retirementAmounts previos))`
- La inversión pertenece al `idUser`

**Input**

| Param | Tipo | Required | Default |
|---|---|---|---|
| `idInvestment` | int | ✓ | — |
| `retirementAmount` | float | ✓ | — |
| `initDate` | string (YYYY-MM-DD) | ✓ | — |
| `endDate` | string (YYYY-MM-DD) | ✓ | — |
| `idUser` | int | ✗ | 1 |
| `realRetribution` | float | ✗ | 0 |
| `descripcion` | string? | ✗ | null |

**Output**:
```json
{
  "success": true,
  "message": "Retiro registrado.",
  "data": {
    "id_retirement_investment": 5,
    "id_investment": 1,
    "retirement_amount": 300.0,
    "real_retribution": 20.0,
    "init_date": "2026-08-20",
    "end_date": "2026-08-25",
    "available_remaining": 200.0
  }
}
```

---

## Presupuestos

### `get_monthly_budget` — Budget mensual del usuario

**Input**: `idUser` (int, default 1)

**Output (sin budget)**:
```json
{
  "success": true,
  "data": {"has_budget": false, "message": "No hay budget configurado para este usuario."}
}
```

**Output (con budget)**:
```json
{
  "success": true,
  "data": {
    "has_budget": true,
    "id_budget": 1,
    "budget": 2500000.0,
    "total": 1500.0,
    "remain": 2498500.0,
    "percent": 0.06,
    "date": "2026-08-15 12:00:00"
  }
}
```

---

### `set_monthly_budget` — Upsert budget mensual

Si existe budget activo, lo actualiza. Si no, lo crea.

**Input**

| Param | Tipo | Required | Default |
|---|---|---|---|
| `total` | float | ✓ (> 0) | — |
| `idUser` | int | ✗ | 1 |
| `description` | string? | ✗ | null |

**Output**:
```json
{
  "success": true,
  "data": {
    "id_budget": 1,
    "id_user": 1,
    "total": 2500000.0,
    "action": "created"
  },
  "message": "Budget creado."
}
```
> El campo `action` puede ser `"created"` o `"updated"`. Análogamente, el `message` será `"Budget creado."` o `"Budget actualizado."` según el caso.

---

### `list_temporal_budgets` — Listar presupuestos temporales

**Input**: `idUser` (int, default 1)

**Output**:
```json
{
  "success": true,
  "count": 1,
  "budgets": [
    {
      "id_temporal_budget": 1,
      "name": "Mes Agosto",
      "description": "Presupuesto agosto",
      "created_at": "2026-08-01 10:00:00",
      "total_amount": 500.0
    }
  ]
}
```

---

### `create_temporal_budget` — Crear presupuesto temporal

**Input**: `name` (string, required), `idUser`, `description`

**Output**: `{success: true, data: {id_temporal_budget, id_user, name, description, created_at}, message: "Presupuesto creado."}`

---

### `update_temporal_budget`

**Input**: `idTemporalBudget`, `idUser`, `name?`, `description?`

---

### `add_temporal_budget_outflow` — Agregar item al presupuesto

**Input**

| Param | Tipo | Required | Descripción |
|---|---|---|---|
| `idTemporalBudget` | int | ✓ | — |
| `idOutflowType` | int | ✓ | — |
| `idCategory` | int | ✓ | Debe pertenecer al `idOutflowType` |
| `idPorcent` | int | ✓ | Depósito del usuario |
| `amount` | float | ✓ | (> 0) |
| `isInBudget` | bool | ✓ | — |
| `idUser` | int | ✗ | 1 |
| `description` | string? | ✗ | null |

---

### `update_temporal_budget_outflow`

**Input**: `idTemporalBudgetOutflow`, `idUser`, `amount?` (> 0), `description?`

---

### `disable_temporal_budget_outflow` — status=0

**Input**: `idTemporalBudgetOutflow`, `idUser`

---

### `enable_temporal_budget_outflow` — status=1

**Input**: `idTemporalBudgetOutflow`, `idUser`

---

### `execute_temporal_budget` — Ejecutar todos los items activos

**Atómico en transacción.** Por cada item activo (status=1):
1. Valida que el depósito exista y pertenezca al usuario
2. Valida que el balance del depósito sea suficiente
3. Crea la fila en `outflows`
4. Marca el item como `status=0`

Si **cualquier** item falla → **rollback total**.

**Input**

| Param | Tipo | Required | Default |
|---|---|---|---|
| `idTemporalBudget` | int | ✓ | — |
| `idUser` | int | ✗ | 1 |
| `setDate` | string? | ✗ | hoy |

**Output (success)**:
```json
{
  "success": true,
  "message": "Presupuesto ejecutado.",
  "data": {
    "id_temporal_budget": 1,
    "executed_count": 3,
    "created_outflows": [
      {"id_temporal_budget_outflow": 1, "id_outflow": 42, "amount": 500.0},
      {"id_temporal_budget_outflow": 2, "id_outflow": 43, "amount": 200.0}
    ]
  }
}
```

---

### `execute_temporal_budget_item` — Ejecutar un solo item

Misma lógica pero para un único item. Valida `status=1` antes.

**Input**

| Param | Tipo | Required |
|---|---|---|
| `idTemporalBudgetOutflow` | int | ✓ |
| `idUser` | int | ✗ |
| `setDate` | string? | ✗ |

---

**Errores comunes a la familia Budgets**

- `El presupuesto temporal no existe o no pertenece al usuario.`
- `El item no existe o no pertenece al usuario.`
- `El item no esta activo. Solo se ejecutan items activos.`
- `Deposito invalido.`
- `Saldo insuficiente: disponible X, requerido Y.`
- `La suma de los porcentajes debe ser igual a 100.` (al crear el outflow)
- `Debes enviar al menos un campo a actualizar.`

---

## Notas

### `list_notes`

**Input**: `idUser` (default 1), `includeInactive` (default false)

**Output**:
```json
{
  "success": true,
  "count": 1,
  "notes": [
    {"id_note": 1, "description": "Pago tarjeta", "total": 500000.0, "status": 1, "create_at": "..."}
  ]
}
```

---

### `create_note`

**Input**: `description` (required, no vacío), `total` (required), `idUser`, `status`

---

### `update_note`

**Input**: `idNote`, `idUser`, `description?` (no vacío), `total?`

---

### `disable_note` — status=0

**Input**: `idNote`, `idUser`

---

## Notificaciones

### `list_notifications`

**Input**: `idUser` (default 1), `onlyUnread` (default false), `limit` (default 50, max 500, min 1)

**Output**:
```json
{
  "success": true,
  "count": 5,
  "notifications": [
    {"id_notification": 1, "id_user": 1, "key_notification_type": "egress", "readed": 0, "create_at": "..."}
  ]
}
```

---

### `mark_notification_read` — readed=1

**Input**: `idNotification`, `idUser`

**Output**: `{success: true, data: {id_notification, readed: 1}, message: "Notificacion marcada como leida."}`

---

## Reportes

### `get_net_worth` — Patrimonio neto

Fórmula: `Σ inflows.status=1 − Σ outflows.status=1`

**Input**: `idUser` (default 1)

**Output**:
```json
{
  "success": true,
  "data": {
    "id_user": 1,
    "total_income": 5000000.0,
    "total_outflow": 1500.0,
    "net_worth": 4998500.0
  }
}
```

---

### `get_net_worth_with_loans` — Patrimonio neto menos préstamos FROM_ME

Fórmula: `net_worth − Σ moneyloans.status=1 type='FROM_ME'`

**Input**: `idUser` (default 1)

**Output**:
```json
{
  "success": true,
  "data": {
    "id_user": 1,
    "total_income": 5000000.0,
    "total_outflow": 1500.0,
    "loans_from_me": 100000.0,
    "net_worth": 4898500.0
  }
}
```

---

## Códigos de error

| Mensaje clave | Tool afectada | Hint generado |
|---|---|---|
| `tipo` (no existe) | outflow/inflow types | `Usa get_outflow_types o get_inflow_types para ver los disponibles.` |
| `categor` (no existe) | categories | `Usa get_categories para ver las disponibles.` |
| `deposito`/`porcent` (no existe) | deposits | `Usa get_available_by_deposits para ver los depositos y sus balances.` |
| `usuario` (no existe) | users | `Verifica que el usuario existe y esta activo.` |
| `grupo` (no existe) | investment groups | `Usa get_investment_groups para ver los grupos del usuario.` |
| otro | — | `Revisa los parametros enviados.` |

---

## Ciclo de vida

- **Desarrollo**: tests con SQLite in-memory + schema migrado en `tests/Schema.php`. Cobertura 100% lines (1950/1950), 100% methods, 100% classes.
- **Producción**: apunta a MySQL `192.168.20.240:3306/finanzas` (credenciales vía `.env`).
- **Logs**: `/tmp/finanzas-mcp-logs/YYYY-MM-DD.log` (auto-rotation por día).
- **Tests de mutación**: `tests/Tools/Mutation/MutationTest.php` cubre 64 mutantes típicos (off-by-one, ownership removal, status inversion, etc.).