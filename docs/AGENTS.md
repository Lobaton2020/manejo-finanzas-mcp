# AGENTS.md - Finanzas MCP Server

## Project Overview

MCP (Model Context Protocol) server for personal finance management. Handles income and expenses tracking via a standardized MCP interface.

## Tech Stack

- **Language**: PHP 8.1+
- **Database**: MySQL or SQLite (via Laravel Eloquent ORM)
- **Protocol**: MCP over STDIO or HTTP
- **Dependencies**: `mcp/sdk`, `illuminate/database`, `nyholm/psr7`

## Directory Structure

```
manejo-finanzas-mcp/
├── src/
│   ├── Database/
│   │   └── Connection.php        # Database connection singleton
│   └── MCP/
│       ├── Server.php            # MCP server entry point
│       └── Tools/
│           ├── BaseTool.php      # Base class with DB access & helpers
│           ├── EgressMoney/      # Expense-related tools
│           └── InflowMoney/      # Income-related tools
├── index.php                     # HTTP entry point
├── composer.json
└── .env                         # Database configuration
```

## Running the Server

```bash
# STDIO mode (for Claude Desktop, etc.)
php src/MCP/Server.php

# HTTP mode
php -S 127.0.0.1:8080 -t .
```

## Available Tools

### Query Tools (Read-only)

| Tool | Purpose |
|------|---------|
| `get_outflow_types` | List active expense types (Food, Transport, etc.) |
| `get_inflow_types` | List active income types (Salary, Investment, etc.) |
| `get_categories` | List categories, optionally filtered by outflow type |
| `get_available_by_deposits` | Get all deposits with balance (income - expenses) |
| `get_deposits_history` | Monthly income/expense summary |
| `get_outflows_by_month` | List expenses for a specific month |
| `get_expense_forecast` | Project next 6 months expenses |

### Action Tools (Write)

| Tool | Purpose |
|------|---------|
| `inflow_money` | Create income record |
| `outflow_money` | Create expense record |

## Workflow for Creating Transactions

**IMPORTANT**: Always discover available options BEFORE creating transactions.

### For Inflows (Income)

1. Call `get_inflow_types` to get valid income type IDs
2. Call `get_available_by_deposits` to get deposit IDs
3. Call `inflow_money` with:
   - `idInflowType`: from step 1
   - `total`: amount
   - `porcents`: array of `{idPorcent, porcent}` where sum = 100
   - `description`: description
   - Optional: `setDate`, `idUser`, `dryRun`

### For Outflows (Expenses)

1. Call `get_outflow_types` to get valid expense type IDs
2. Call `get_categories` (optionally filtered by type) to get category IDs
3. Call `get_available_by_deposits` to check available balance
4. Call `outflow_money` with:
   - `idOutflowType`: from step 1
   - `idCategory`: from step 2
   - `idPorcent`: deposit ID from step 3
   - `amount`: must be > 0 and <= available balance
   - `isInBudget`: whether it's a budgeted expense
   - `description`: description
   - Optional: `setDate`, `idUser`, `dryRun`

### Example: Creating an Expense

```json
// First, discover options
{ "tool": "get_outflow_types", "idUser": 1 }
// Returns: [{"id": 1, "name": "Gastos"}, {"id": 2, "name": "Inversión"}]

{ "tool": "get_categories", "idOutflowType": 1 }
// Returns: [{"id": 1, "name": "Comida", "type_id": 1}, ...]

{ "tool": "get_available_by_deposits", "idUser": 1 }
// Returns: [{"id_porcent": 1, "name": "principal", "available_balance": 500000}]

// Then create the outflow
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

## Response Format

All tools return JSON in this structure:

```json
{
  "content": [
    {
      "type": "text",
      "text": "{\"success\": true, \"data\": {...}}"
    }
  ]
}
```

Parse the `text` field to get the actual JSON response.

## Error Handling

Errors include actionable hints:

```json
{
  "valid": false,
  "error": "El tipo de egreso no existe o está inactivo.",
  "hint": "Usa get_outflow_types para ver los disponibles."
}
```

## Database Schema

**The database is encapsulated** - do not query tables directly. Use the provided tools.

Key entities (via tools only):
- `users` - User accounts
- `inflowtypes` - Income categories
- `outflowtypes` - Expense categories  
- `categories` - Sub-categories linked to outflow types
- `porcents` - Deposits/accounts (user's money containers)
- `inflows` - Income records
- `outflows` - Expense records
- `inflow_porcent` - Links inflows to deposits with percentages
- `investments` - Investment records (auto-created for "inversion" type)

## Code Style

- PHP 8.1+ with strict types
- Classes: PascalCase (e.g., `OutflowMoneyTool`)
- Methods: camelCase
- Use `BaseTool` methods for DB access:
  - `$this->table('table_name')` - Query builder
  - `$this->transaction(callable)` - Wrap in DB transaction
  - `$this->successResponse()`, `$this->errorResponse()`, `$this->validationError()` - Standardized responses
- All MCP tools use `#[McpTool]` attribute with `name` and `description`

## Common Tasks

### Adding a New Tool

1. Create class in `src/MCP/Tools/ModuleName/`
2. Extend `BaseTool`
3. Add `#[McpTool(name: 'tool_name', description: '...')]` attribute
4. Register in `Server.php`:
   ```php
   ->addTool([ToolClass::class, 'methodName'], 'tool_name')
   ```

### Debugging

Check logs in `/tmp/finanzas-mcp-logs/YYYY-MM-DD.log`

### Testing with dryRun

All write tools support `dryRun=true` to validate without persisting:

```json
{
  "tool": "outflow_money",
  "idOutflowType": 1,
  "idCategory": 1,
  "idPorcent": 1,
  "amount": 50000,
  "isInBudget": true,
  "description": "Test",
  "dryRun": true
}
```

## Notes for AI Agents

- **ALWAYS** query available options before creating transactions
- The database is encapsulated - never write SQL directly
- Use `dryRun=true` to validate complex operations
- All monetary amounts are in the user's currency (assume COP)
- Dates are in `YYYY-MM-DD` format
- User ID defaults to 1 for single-user scenarios
