# Plan: Servidor MCP para Finanzas

## Visión General

Crear un servidor MCP (Model Context Protocol) en PHP que permita a LLMs (como Claude) interactuar con el sistema de finanzas personales para gestionar ingresos, egresos y generar reportes.

---

## Fase 1: Replicación de Base de Datos ✅

- [x] Conectar a MySQL remoto
- [x] Replicar todas las tablas a SQLite local
- [x] Resultado: `finanzas.db` (7,466 registros, 29 tablas)

---

## Fase 2: Estructura del Proyecto

```
finanzas-mcp/
├── finances.db                    # Base de datos SQLite (existente)
├── php.ini                        # Configuración PHP
├── .env                           # Configuración (existente)
├── src/
│   ├── Database/
│   │   └── Connection.php         # Conexión SQLite
│   ├── MCP/
│   │   ├── Server.php            # Servidor MCP principal
│   └── Tools/
│       ├── EgressMoney/          # Grupo de herramientas de Egresos
│       │   ├── EgressMoneyTool.php        # Tool principal: crear egreso
│       │   ├── GetOutflowTypesTool.php    # Listar tipos de egreso activos
│       │   ├── GetCategoriesTool.php       # Listar categorías por tipo
│       │   ├── GetDepositsTool.php         # Listar depósitos con balance
│       │   ├── GetAvailableBalanceTool.php # Calcular balance disponible
│       │   └── ValidateOutflowTool.php     # Validar egreso antes de crear
│       ├── Inflow/               # Grupo de herramientas de Ingresos
│       │   └── InflowTool.php
│       └── Report/               # Grupo de herramientas de Reportes
│           └── ReportTool.php
├── docs/
│   ├── PLAN.md                   # Este archivo
│   └── create_outflow.md         # Especificación de Egresos
└── config.json                   # Configuración MCP (Claude Desktop)
```

---

## Fase 3: Herramientas MCP

### EgressMoney (Egresos)

Basado en `create_outflow.md`:

#### Tools Principal

| Tool | Descripción | Parámetros |
|------|-------------|------------|
| `egress_money` | Crear un egreso | (ver detalles abajo) |
| `validate_outflow` | Validar egreso antes de crear | (ver detalles abajo) |

#### Tools de Soporte (Dependencias)

| Tool | Descripción | Parámetros |
|------|-------------|------------|
| `get_outflow_types` | Listar tipos de egreso activos | - |
| `get_categories` | Listar categorías por tipo | `id_outflow_type` |
| `get_deposits` | Listar depósitos/porcentajes con balance | `id_user` |
| `get_available_balance` | Calcular balance disponible | `id_user`, `id_porcent` |

#### egress_money Parameters

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id_outflow_type | int | Sí | Tipo de egreso |
| id_category | int | Sí | Categoría |
| id_porcent | int | Sí | Depósito fuente |
| amount | float | Sí | Monto (> 0) |
| description | string | No | Descripción adicional |
| set_date | date | Sí | Fecha del egreso |
| is_in_budget | bool | Sí | Está en presupuesto |

#### Reglas de Negocio a Implementar

1. **Validación de Balance**: `amount <= available_balance`
2. **Balance Disponible**: `Total Ingresos - Total Egresos` por depósito
3. **Tipos Activos**: Solo mostrar tipos con `status = 1`
4. **Depósitos Activos**: Solo mostrar depósitos con `status = 1`
5. **Creación Automática de Inversión**: Si el tipo contiene "inversion" (case-insensitive)
6. **Notificación**: Se envía notificación tras egreso exitoso

### InflowTool (Ingresos)

| Tool | Descripción | Parámetros |
|------|-------------|------------|
| `get_inflow_types` | Listar tipos de ingreso | - |
| `list_inflows` | Listar ingresos | `id_user`, `date_from`, `date_to`, `limit` |
| `create_inflow` | Crear ingreso | `amount`, `id_inflow_type`, `description`, `set_date` |

### ReportTool (Reportes)

| Tool | Descripción | Parámetros |
|------|-------------|------------|
| `get_balance` | Balance total | `id_user` |
| `get_monthly_summary` | Resumen mensual | `id_user`, `year`, `month` |
| `get_category_summary` | Resumen por categoría | `id_user`, `date_from`, `date_to` |

---

## Fase 4: Implementación del Servidor MCP

### Protocolo JSON-RPC 2.0

El servidor MCP usa JSON-RPC 2.0 sobre STDIO:

- **initialize**: Inicializa el servidor
- `tools/list`: Lista todas las herramientas disponibles
- `tools/call`: Ejecuta una herramienta específica
- `resources/list`: Lista recursos disponibles
- `resources/read`: Lee un recurso

### Formato de Respuesta

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "content": [
      {
        "type": "text",
        "text": "Resultado en formato legible"
      }
    ]
  }
}
```

---

## Fase 5: Configuración

### config.json (Claude Desktop)

```json
{
  "mcpServers": {
    "finanzas": {
      "command": "php",
      "args": ["src/MCP/Server.php"],
      "env": {
        "DB_PATH": "./finanzas.db"
      }
    }
  }
}
```

---

## Dependencias

```json
{
    "require": {
        "mcp/sdk": "^0.4.0",
        "illuminate/database": "^11.0"
    }
}
```

### mcp/sdk
SDK oficial de PHP para Model Context Protocol. Proporciona una API agnóstica para implementar servidores y clientes MCP en PHP.

**Instalación:**
```bash
composer require 

**Características:**
- Descubrimiento basado en atributos (`#[McpTool]`, `#[McpResource]`)
- Registro manual de herramientas
- Múltiples transportes (STDIO, HTTP)
- Gestión de sesiones

### illuminate/database (Eloquent)
ORM de Laravel para acceso a bases de datos. Proporciona un constructor de consultas fluent y un ORM completo.

**Instalación:**
```bash
composer require illuminate/database
```

**Configuración de Conexión:**

La conexión a la base de datos sigue esta lógica:

```php
// src/Database/Connection.php

if (file_exists(__DIR__ . '/../../finanzas.db')) {
    // Usar SQLite local si existe el archivo
    $capsule->addConnection([
        'driver' => 'sqlite',
        'database' => __DIR__ . '/../../finanzas.db',
        'prefix' => '',
    ]);
} else {
    // Usar MySQL del .env
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => $_ENV['DBHOST'] ?? 'localhost',
        'database' => $_ENV['DBNAME'] ?? 'finanzas',
        'username' => $_ENV['DBUSER'] ?? 'root',
        'password' => $_ENV['DBPASS'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ]);
}
```

**Características:**
- Constructor de consultas fluent
- ORM Active Record
- Soporte para SQLite, MySQL, PostgreSQL
- Protege contra inyección SQL

- Para desarrollo/pruebas: `id_user = 1`
- Base de datos local: SQLite (`finanzas.db`)
- Protocolo: STDIO (stdin/stdout)

---

## Estado Actual

### Estructura de Tablas

| Tabla | Primary Key | Notas |
|-------|-------------|-------|
| `porcents` | `id_porcent` | Depósitos/porcentajes |
| `outflows` | `id_outflow` | Egresos |
| `inflows` | `id_inflow` | Ingresos |
| `inflow_porcent` | `id_inflow_porcent` | Relación inflow-porcent |
| `outflowtypes` | `id_outflow_type` | Tipos de egreso |
| `categories` | `id_category` | Categorías |
| `users` | `id_user` | Usuarios |
| `investments` | `id_investment` | Inversiones |
| `notifications` | `id_notification` | Notificaciones |

### Implementado ✅

- [x] `src/Database/Connection.php` - Conexión SQLite/MySQL
- [x] `src/MCP/Server.php` - Servidor MCP con HTTP transport
- [x] `src/MCP/Tools/BaseTool.php` - Clase base con logging
- [x] `src/MCP/Tools/EgressMoney/GetOutflowTypesTool.php`
- [x] `src/MCP/Tools/EgressMoney/GetCategoriesTool.php`
- [x] `src/MCP/Tools/EgressMoney/GetAvailableByDepositsTool.php` - **CORREGIDO** (era `percents` -> `porcents`)
- [x] `src/MCP/Tools/EgressMoney/OutflowMoneyTool.php` - **CORREGIDO** (era `percents` -> `porcents`)
- [x] `README.md` - Documentación
- [x] `docs/skills/db/SKILL.md` - Documentación de BD

### Pendiente

- [ ] Probar las herramientas
- [ ] Implementar herramientas de Ingresos
- [ ] Implementar herramientas de Reportes
