# Use Case: Money Spent by Deposit (Gastos por Depósito)

## Description

This use case retrieves and displays the total amount of money spent (outflows) for each deposit/percentage registered by the user. It provides a breakdown of expenses grouped by deposit, allowing users to understand where their money from each income source has been allocated.

## Actors

- **Authenticated User**: Person who owns an account in the system.
- **Note**: For testing/development, the system uses `id_user = 1` as the logged-in user.

## Preconditions

1. The user must be authenticated in the system.
2. The user must have at least one active deposit (percentage) registered.
3. Optionally, the user should have at least one outflow associated with a deposit.

## Main Flow

### 1. Request Endpoint
The frontend makes a GET request to `report/moneySpendbyDeposit`.

```javascript
// Frontend: public/assets/js/statistics.js
let result = await fetch(`${URL_PROJECT}report/moneySpendbyDeposit`)
```

### 2. Get User Deposits
The system retrieves all active deposits (percentages) for the current user:
- Filters by `id_user` and `status = 1`
- Selects fields: `id_porcent`, `name`, `status`, `create_at`

```php
// ReportController.php - moneyEgressbyDeposit() method
$porcents = $this->porcent->select(
    ["id_porcent", "name", "status", "create_at"], 
    ["id_user[=]" => $this->id, "status[=]" => 1, "AND"]
)->array();
```

### 3. Extract Deposit IDs
The system extracts the IDs from the deposit list for querying associated outflows.

```php
// ReportController.php - getIdsPorcent() method
private function getIdsPorcent($porcents, $name_id)
{
    $ids_porcent = [];
    foreach ($porcents as $porcent) {
        $porcent->total = 0;  // Initialize total for each deposit
        array_push($ids_porcent, $porcent->{"{$name_id}"});
    }
    return $ids_porcent;
}

// Usage in moneyEgressbyDeposit()
$ids_porcent = $this->getIdsPorcent($porcents, "id_porcent");
if (count($ids_porcent) == 0) {
    return [];
}
```

### 4. Get Associated Outflows
The system retrieves all outflows that are associated with the user's deposits.

```php
// ReportController.php - moneyEgressbyDeposit() method
$outflow_porcents = $this->outflow->in(["id_porcent[in]" => $ids_porcent])->array();
```

### 5. Calculate Totals
The system iterates through each outflow and accumulates the amount to the corresponding deposit:
- Initializes `total = 0` for each deposit
- Sums the `amount` from each outflow to its associated deposit

```php
// ReportController.php - moneyEgressbyDeposit() method
foreach ($outflow_porcents as $outflow_porcent) {
    if (in_array($outflow_porcent->id_porcent, $ids_porcent)) {
        foreach ($porcents as $porcent) {
            if ($outflow_porcent->id_porcent == $porcent->id_porcent) {
                $porcent->total += intval($outflow_porcent->amount);
            }
        }
    }
}
return $porcents;
```

### 6. Return JSON Response
The system returns the deposits with their calculated totals as JSON.

```php
// ReportController.php - moneySpendbyDeposit() method
public function moneySpendbyDeposit()
{
    return httpResponse($this->moneyEgressbyDeposit())->json();
}
```

## Alternative Flows

### AF1: No Deposits Found
If the user has no active deposits:
1. Returns an empty array `[]`
2. No error is thrown

### AF2: No Outflows for Deposits
If deposits exist but no outflows are associated:
1. Returns deposits with `total = 0` for each

## Postconditions

1. The user receives a JSON array containing deposits with their total spent amounts.
2. Each deposit object includes:
   - `id_porcent`: Deposit identifier
   - `name`: Deposit name
   - `status`: Deposit status
   - `create_at`: Creation date
   - `total`: Total amount spent from this deposit (integer)

## Business Rules Summary

| # | Rule | Description |
|---|------|-------------|
| 1 | Active Deposits Only | Only deposits with `status = 1` are considered |
| 2 | Total Calculation | Sum of all outflow amounts per deposit |
| 3 | Empty Result | Returns empty array if no deposits exist |

## Sequence Diagram (Simplified)

```
Frontend              Controller              Model              Database
  |                       |                      |                    |
  |-- GET request ------>|                      |                    |
  |                      |-- moneyEgressbyDeposit()              |
  |                      |                      |                    |
  |                      |-- select() --------->|                    |
  |                      |<-- deposits ---------|                    |
  |                      |                      |                    |
  |                      |-- extract IDs ------>|                    |
  |                      |                      |                    |
  |                      |-- in() ------------->|                    |
  |                      |<-- outflows ---------|                    |
  |                      |                      |                    |
  |                      |-- calculate totals ->|                    |
  |                      |                      |                    |
  |<-- JSON response ----|                      |                    |
```

## Related Methods

| Method | File | Description |
|--------|------|-------------|
| `moneySpendbyDeposit()` | ReportController.php:52 | Public endpoint |
| `moneyEgressbyDeposit()` | ReportController.php:32 | Core logic |
| `moneyTotalbyDeposit()` | ReportController.php:22 | Gets total income per deposit |
| `moneyDisponiblebyDeposit()` | ReportController.php:57 | Calculates available balance |

## Usage Example

### Request
```
GET /report/moneySpendbyDeposit
```

### Response Example
```json
[
  {
    "id_porcent": 1,
    "name": "Savings",
    "status": 1,
    "create_at": "2024-01-15 10:30:00",
    "total": 500
  },
  {
    "id_porcent": 2,
    "name": "Investments",
    "status": 1,
    "create_at": "2024-02-20 14:00:00",
    "total": 1200
  }
]
```

## Complete Code Example

### Controller Method (ReportController.php)

```php
public function moneySpendbyDeposit()
{
    return httpResponse($this->moneyEgressbyDeposit())->json();
}

public function moneyEgressbyDeposit()
{
    // Step 1: Get all active deposits for the user
    $porcents = $this->porcent->select(
        ["id_porcent", "name", "status", "create_at"], 
        ["id_user[=]" => $this->id, "status[=]" => 1, "AND"]
    )->array();
    
    // Step 2: Extract deposit IDs
    $ids_porcent = $this->getIdsPorcent($porcents, "id_porcent");
    if (count($ids_porcent) == 0) {
        return [];
    }
    
    // Step 3: Get all outflows associated with these deposits
    $outflow_porcents = $this->outflow->in(["id_porcent[in]" => $ids_porcent])->array();
    
    // Step 4: Calculate totals per deposit
    foreach ($outflow_porcents as $outflow_porcent) {
        if (in_array($outflow_porcent->id_porcent, $ids_porcent)) {
            foreach ($porcents as $porcent) {
                if ($outflow_porcent->id_porcent == $porcent->id_porcent) {
                    $porcent->total += intval($outflow_porcent->amount);
                }
            }
        }
    }
    
    return $porcents;
}

private function getIdsPorcent($porcents, $name_id)
{
    $ids_porcent = [];
    foreach ($porcents as $porcent) {
        $porcent->total = 0;
        array_push($ids_porcent, $porcent->{"{$name_id}"});
    }
    return $ids_porcent;
}
```

### Frontend Call (statistics.js)

```javascript
async function loadMoneySpendByDeposit() {
    try {
        const response = await fetch(`${URL_PROJECT}report/moneySpendbyDeposit`);
        const data = await response.json();
        
        // Process data for chart display
        const labels = data.map(item => item.name);
        const values = data.map(item => item.total);
        
        console.log('Deposits:', labels);
        console.log('Totals:', values);
        
    } catch (error) {
        console.error('Error loading data:', error);
    }
}
```

## Related Use Cases

- [x] Register income (inflows)
- [x] Create outflow (expense)
- [ ] Manage deposits (percentages)
- [ ] View available balance by deposit
