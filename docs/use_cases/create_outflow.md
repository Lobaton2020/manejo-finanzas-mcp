# Use Case: Create an Outflow (Expense)

## Description

This use case allows the user to register an expense (outflow) from their account, affecting one of their existing deposits. The system validates that the user has sufficient balance in the selected deposit.

## Actors

- **Authenticated User**: Person who owns an account in the system and has registered incomes.
- **Note**: For testing/development, the system uses `id_user = 1` as the logged-in user.

## Preconditions

1. The user must be authenticated in the system (development: `id_user = 1`).
2. The user must have at least one active deposit (percentage).
3. The user must have at least one outflow type configured.
4. The user must have at least one outflow category configured.

## Main Flow

### 1. Access the Outflow Form
The user navigates to the outflow section and clicks "Create" or equivalent button.

### 2. Select Outflow Type
The user selects the type of expense from the dropdown list.
- **Business Rule**: Only active outflow types are shown (`status = 1`).
- **Business Rule**: If there are no outflow types, a message is displayed indicating they need to create one.

### 3. Select Category
After selecting the type, the system displays categories associated with that type. The user selects one.
- **Business Rule**: Categories are filtered by the selected outflow type.

### 4. Select Deposit (Percentage)
The user selects the deposit from which they want to make the outflow.
- **Business Rule**: Only active deposits are shown (`status = 1`).
- **Business Rule**: The available balance of each deposit is shown in real-time.

### 5. Enter Amount
The user enters the amount to withdraw.
- **Business Rule**: The amount must be greater than 0.
- **Business Rule**: The amount cannot exceed the available balance of the selected deposit.

### 6. (Business Rule) Available Balance Validation
```
Available Balance = Total Income for deposit - Total Outflows for deposit
```
If the amount exceeds the available balance, the system displays an error and does not allow continuation.

### 7. Enter Description (optional)
The user can add an additional description for the outflow.

### 8. Select Date
The user selects the date of the outflow.
- **Business Rule**: If the outflow is made from a budget, the date is automatically set to the current date.

### 9. Indicate if it's in Budget
The user indicates if this outflow is within a planned budget.
- **Values**: Yes / No

### 10. Submit Form
The user submits the form.

### 11. (Business Rule) Automatic Investment Creation
If the outflow type contains the word "inversion" (case-insensitive), the system automatically creates an investment record associated with the outflow.

### 12. (Business Rule) Notification
After a successful outflow, the system sends a notification to the user.

### 13. Display Result
The system displays a success message and redirects to the outflow list.

## Alternative Flows

### AF1: Insufficient Balance
If in step 6 the amount exceeds the available balance:
1. The system displays error message: "The balance of the selected deposit is NOT sufficient for the amount you want to withdraw."
2. The user must modify the amount or select another deposit.
3. Return to step 5.

### AF2: Required Fields Empty
If the user attempts to submit without completing required fields:
1. The system displays message: "You must fill in all required fields."
2. The form remains open for correction.

### AF3: Outflow from Budget
If the outflow is made from a budget (passed as parameter `is_budget=true`):
1. The system omits the date field (current date is used automatically).
2. The outflow is linked to the specified temporary budget.

## Postconditions

1. The outflow is recorded in the database with active status.
2. The available balance of the deposit is updated (decremented).
3. If applicable, an investment record is created.
4. A notification is generated for the user.
5. The user is redirected to the outflow list with a success message.

## Business Rules Summary

| # | Rule | Description |
|---|------|-------------|
| 1 | Available Balance | Calculated as: Income - Outflows per deposit |
| 2 | Amount Validation | amount > 0 and amount <= available balance |
| 3 | Outflow Types | Only active ones are shown |
| 4 | Deposits | Only active ones are shown |
| 5 | Automatic Investment Creation | If type contains "inversion" |
| 6 | Notification | Notification sent after successful outflow |
| 7 | Date in Budget | Current date used if from budget |
| 8 | Description | Optional field |

## Sequence Diagram (Simplified)

```
User                  Form                  Controller            Service              Model
  |                     |                       |                    |                    |
  |-- access create -->|                       |                    |                    |
  |<-- show form ------|                       |                    |                    |
  |                     |                       |                    |                    |
  |-- select type ---->|                       |                    |                    |
  |                     |-- get types -------->|                    |                    |
  |                     |<-- types ------------|                    |                    |
  |                     |                       |                    |                    |
  |-- select deposit -->|                       |                    |                    |
  |                     |-- get balances ----->|                    |                    |
  |                     |<-- balances ----------|                    |                    |
  |                     |                       |                    |                    |
  |-- enter amount ---->|                       |                    |                    |
  |                     |                       |                    |                    |
  |-- submit form ----->|                       |                    |                    |
  |                     |-- perform_egress ---->|                    |                    |
  |                     |                       |-- validate balance ->|                  |
  |                     |                       |<-- result ---------|                    |
  |                     |                       |-- insert outflow -->|                  |
  |                     |                       |                    |-- insert ----->    |
  |                     |                       |                    |<-- ok -----------   |
  |                     |                       |<-- ok -------------|                    |
  |                     |                       |-- check investment->|                    |
  |                     |                       |<-- result ---------|                    |
  |                     |                       |-- create investment>|                  |
  |                     |                       |                    |-- create ----->   |
  |                     |                       |                    |<-- ok -----------   |
  |                     |                       |-- send notification>|                  |
  |                     |                       |<-- ok -------------|                    |
  |<-- success ---------|                       |                    |                    |
```

## Form Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id_outflow_type | Select | Yes | Outflow type |
| id_category | Radio | Yes | Outflow category |
| id_porcent | Select | Yes | Deposit/Percentage source |
| amount | Number | Yes | Amount to withdraw |
| description | Text | No | Additional description |
| set_date | Date | Yes | Date of outflow |
| is_in_budget | Select | Yes | Indicates if it's in budget |

## Related Use Cases

- [ ] Create budget
- [ ] Execute budget
- [ ] Register income
- [ ] Manage deposits (percentages)
- [ ] Create outflow type
- [ ] Create outflow category
