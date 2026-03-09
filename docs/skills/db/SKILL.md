# Database Structure - SKILL

## Overview

This document describes the database schema for the Finanzas MCP system. The database uses SQLite and contains tables for managing personal finances including users, inflows, outflows, budgets, investments, and notifications.

---

## Tables

### users
Users table containing authentication and profile information.

| Column | Type | Description |
|--------|------|-------------|
| id_user | INTEGER | Primary key |
| id_rol | INTEGER | Role ID |
| id_document_type | INTEGER | Document type ID |
| number_document | TEXT | Document number |
| complete_name | TEXT | Full name |
| email | TEXT | Email address |
| password | TEXT | Hashed password |
| image | TEXT | Profile image path |
| email_verify_date | TEXT | Email verification date |
| recovery_pass_token | TEXT | Password recovery token |
| remember_token | TEXT | Remember me token |
| born_date | TEXT | Birth date |
| status | INTEGER | Status (1 = active) |
| update_at | TEXT | Last update timestamp |
| create_at | TEXT | Creation timestamp |

---

### outflowtypes
Types of outflows/expenses.

| Column | Type | Description |
|--------|------|-------------|
| id_outflow_type | INTEGER | Primary key |
| id_user | INTEGER | User ID (nullable for global types) |
| name | TEXT | Type name |
| status | INTEGER | Status (1 = active) |
| create_at | TEXT | Creation timestamp |

---

### categories
Categories for outflows (dependent on outflow type).

| Column | Type | Description |
|--------|------|-------------|
| id_category | INTEGER | Primary key |
| id_outflow_type | INTEGER | Foreign key to outflowtypes |
| id_user | INTEGER | User ID |
| name | TEXT | Category name |
| status | INTEGER | Status (1 = active) |
| create_at | TEXT | Creation timestamp |

---

### porcents (Deposits)
Deposit/percentage allocations for income distribution.

| Column | Type | Description |
|--------|------|-------------|
| id_porcent | INTEGER | Primary key |
| id_user | INTEGER | User ID |
| name | TEXT | Deposit name |
| status | INTEGER | Status (1 = active) |
| create_at | TEXT | Creation timestamp |

---

### inflows
Income records.

| Column | Type | Description |
|--------|------|-------------|
| id_inflow | INTEGER | Primary key |
| id_user | INTEGER | User ID |
| id_inflow_type | INTEGER | Foreign key to inflowtypes |
| total | REAL | Total amount |
| description | TEXT | Description |
| set_date | TEXT | Date of inflow |
| status | INTEGER | Status (1 = active) |
| update_at | TEXT | Last update timestamp |
| create_at | TEXT | Creation timestamp |

---

### inflowtypes
Types of inflows/income.

| Column | Type | Description |
|--------|------|-------------|
| id_inflow_type | INTEGER | Primary key |
| id_user | INTEGER | User ID |
| name | TEXT | Type name |
| status | INTEGER | Status (1 = active) |
| create_at | TEXT | Creation timestamp |

---

### inflow_porcent
Junction table linking inflows to deposits (many-to-many).

| Column | Type | Description |
|--------|------|-------------|
| id_inflow_porcent | INTEGER | Primary key |
| id_inflow | INTEGER | Foreign key to inflows |
| id_porcent | INTEGER | Foreign key to porcents |
| porcent | INTEGER | Percentage allocated |
| status | INTEGER | Status (1 = active) |
| create_at | TEXT | Creation timestamp |

---

### outflows
Expense/outflow records.

| Column | Type | Description |
|--------|------|-------------|
| id_outflow | INTEGER | Primary key |
| id_outflow_type | INTEGER | Foreign key to outflowtypes |
| id_user | INTEGER | User ID |
| id_category | INTEGER | Foreign key to categories |
| id_porcent | INTEGER | Foreign key to porcents |
| amount | REAL | Amount spent |
| description | TEXT | Description |
| set_date | TEXT | Date of outflow |
| status | INTEGER | Status (1 = active) |
| update_at | TEXT | Last update timestamp |
| create_at | TEXT | Creation timestamp |
| is_in_budget | INTEGER | Whether it's part of a budget |

---

### investments
Investment records (created automatically when outflow type contains "inversion").

| Column | Type | Description |
|--------|------|-------------|
| id_investment | INTEGER | Primary key |
| id_outflow | INTEGER | Foreign key to outflows |
| percent_annual_effective | REAL | Annual effective percentage |
| state | TEXT | Investment state |
| init_date | TEXT | Start date |
| end_date | TEXT | End date |
| real_retribution | REAL | Real return amount |
| risk_level | TEXT | Risk level |
| updated_at | TEXT | Last update timestamp |
| created_at | TEXT | Creation timestamp |

---

### notifications
User notifications.

| Column | Type | Description |
|--------|------|-------------|
| id_notification | INTEGER | Primary key |
| id_user | INTEGER | User ID |
| key_notification_type | TEXT | Notification type key |
| readed | INTEGER | Read status (0 = unread, 1 = read) |
| create_at | TEXT | Creation timestamp |

---

### budget
Budget records.

| Column | Type | Description |
|--------|------|-------------|
| id_budget | INTEGER | Primary key |
| id_user | INTEGER | User ID |
| total | REAL | Budget total amount |
| description | TEXT | Budget description |
| created_at | TEXT | Creation timestamp |

---

### temporal_budgets
Temporary/temporary budgets.

| Column | Type | Description |
|--------|------|-------------|
| id_temporal_budget | INTEGER | Primary key |
| id_user | INTEGER | User ID |
| name | TEXT | Budget name |
| description | TEXT | Description |
| created_at | TEXT | Creation timestamp |

---

### temporal_budgets_outflow
Outflows associated with temporary budgets.

| Column | Type | Description |
|--------|------|-------------|
| id_temporal_budget_outflow | INTEGER | Primary key |
| id_temporal_budget | INTEGER | Foreign key to temporal_budgets |
| id_outflow_type | INTEGER | Foreign key to outflowtypes |
| id_user | INTEGER | User ID |
| id_category | INTEGER | Foreign key to categories |
| id_porcent | INTEGER | Foreign key to porcents |
| amount | REAL | Amount |
| description | TEXT | Description |
| status | INTEGER | Status |
| is_in_budget | INTEGER | Budget status |
| update_at | TEXT | Last update timestamp |
| create_at | TEXT | Creation timestamp |

---

### moneyloans
Money lending records.

| Column | Type | Description |
|--------|------|-------------|
| id_money_loan | INTEGER | Primary key |
| id_user | INTEGER | User ID |
| description | TEXT | Description |
| total | REAL | Total amount |
| set_date | TEXT | Date |
| status | INTEGER | Status |
| create_at | TEXT | Creation timestamp |
| type | TEXT | Loan type |

---

### notes
User notes.

| Column | Type | Description |
|--------|------|-------------|
| id_note | INTEGER | Primary key |
| id_user | INTEGER | User ID |
| description | TEXT | Note content |
| total | REAL | Associated amount |
| status | INTEGER | Status |
| create_at | TEXT | Creation timestamp |

---

### rols
User roles.

| Column | Type | Description |
|--------|------|-------------|
| id_rol | INTEGER | Primary key |
| name | TEXT | Role name |

---

### documenttypes
Types of identification documents.

| Column | Type | Description |
|--------|------|-------------|
| id_document_type | INTEGER | Primary key |
| abrev | TEXT | Abbreviation |
| name | TEXT | Document type name |

---

### loggins
Login history tracking.

| Column | Type | Description |
|--------|------|-------------|
| id_login | INTEGER | Primary key |
| id_user | INTEGER | User ID |
| browser | TEXT | Browser information |
| server | TEXT | Server information |
| create_at | TEXT | Login timestamp |

---

### tokenregisters
Token registration for password recovery etc.

| Column | Type | Description |
|--------|------|-------------|
| id_token_register | INTEGER | Primary key |
| id_rol | INTEGER | Role ID |
| id_user | INTEGER | User ID |
| description | TEXT | Description |
| token | TEXT | Token value |
| status | INTEGER | Status |
| create_at | TEXT | Creation timestamp |

---

### countvisits
Visit counter.

| Column | Type | Description |
|--------|------|-------------|
| id_count_visit | INTEGER | Primary key |
| id_user | INTEGER | User ID |
| count | INTEGER | Visit count |
| update_at | TEXT | Last update |
| create_at | TEXT | Creation timestamp |

---

### queries
Saved user queries.

| Column | Type | Description |
|--------|------|-------------|
| id_query | INTEGER | Primary key |
| id_user | INTEGER | User ID |
| description | TEXT | Query description |
| query | TEXT | Query string |
| create_at | TEXT | Creation timestamp |

---

### cook_tracking
Cooking/tracking records.

| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER | Primary key |
| date | TEXT | Date |
| descripcion | TEXT | Description |
| user_id | INTEGER | User ID |
| created_at | TEXT | Creation timestamp |
| title | TEXT | Title |

---

### scrapper
Price scraper data.

| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER | Primary key |
| price | REAL | Price value |
| created_at | TEXT | Creation timestamp |

---

### retirement_investments
Investment retirements.

| Column | Type | Description |
|--------|------|-------------|
| id_retirement_investment | INTEGER | Primary key |
| id_investment | INTEGER | Investment ID |
| id_user | INTEGER | User ID |
| descripcion | TEXT | Description |
| retirement_amount | REAL | Retirement amount |
| init_date | TEXT | Start date |
| end_date | TEXT | End date |
| real_retribution | REAL | Real return |
| created_at | TEXT | Creation timestamp |

---

### moneyloan_notifications
Notifications for money loans.

| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER | Primary key |
| id_money_loan | INTEGER | Money loan ID |
| email | TEXT | Email address |
| username | TEXT | Username |
| is_active | INTEGER | Active status |
| is_subscription | INTEGER | Subscription status |
| created_at | TEXT | Creation timestamp |
| executed_at | TEXT | Execution timestamp |

---

### notificationtypes
Types of notifications.

| Column | Type | Description |
|--------|------|-------------|
| key_notification_type | TEXT | Primary key |
| name | TEXT | Notification type name |

---

## Views

### investments_view
View combining investment data with outflow information.

### view_budget
View for budget summary data.

### view_temporal_budgets
View for temporary budget data.

---

## Important Relationships

1. **Inflows to Deposits**: Many-to-many via `inflow_porcent` table
2. **Outflows**: Linked to `outflowtypes`, `categories`, and `porcents`
3. **Investments**: Automatically created when outflow type name contains "inversion" (case-insensitive)
4. **Categories**: Dependent on `outflowtypes` (each category belongs to an outflow type)

---

## Status Values

- `status = 1`: Active/Enabled
- `status = 0`: Inactive/Disabled

---

## Usage in MCP Tools

When writing MCP tools, use these table and column names exactly as shown above. Remember:

1. Always filter by `status = 1` for active records
2. Always filter by `id_user` for user-specific data
3. Use `outflowtypes.id_user` can be NULL for global types
4. Calculate available balance: `SUM(inflows.amount) - SUM(outflows.amount)` per deposit
