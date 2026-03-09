# Use Case: Money Available by Deposit (Dinero Disponible por Depósito)

## Description

This use case calculates and returns the available balance for each deposit/percentage registered by the user. The available balance is calculated by subtracting total outflows (expenses) from total inflows (income) for each deposit.

## Actors

- **Authenticated User**: Person who owns an account in the system.
- **Note**: For testing/development, the system uses `id_user = 1` as the logged-in user.

## Preconditions

1. The user must be authenticated in the system.
2. The user must have at least one active deposit (percentage) registered.
3. Optionally, the user should have inflows and/or outflows associated with deposits.

## Main Flow

### 1. Request Endpoint
The frontend makes a GET request to `report/moneyDisponiblebyDeposit`.

```javascript
// Frontend
let result = await fetch(`${URL_PROJECT}report/moneyDisponiblebyDeposit`)
```

### 2. Get Total Income by Deposit
The system retrieves the total income for each deposit using a SQL query:
- Joins `porcents`, `inflow_porcent`, and `inflows` tables
- Calculates the percentage of each inflow assigned to each deposit
- Groups by deposit ID

```php
// ReportController.php - moneyTotalbyDeposit() method
public function moneyTotalbyDeposit()
{
    $this->id = intval($this->id);
    $porcents = $this->porcent->query_complete(
        "select p.*,sum(i.total * (ip.porcent / 100)) as total  
         from porcents as p
         left join inflow_porcent as ip on ip.id_porcent = p.id_porcent
         left join inflows as i on ip.id_inflow = i.id_inflow  
         where p.id_user = {$this->id} and p.status = 1
         group by p.id_porcent ORDER BY ip.id_porcent ASC"
    )->array();

    return $porcents;
}
```

### 3. Get Total Outflows by Deposit
The system retrieves the total amount spent for each deposit.

```php
// ReportController.php - moneyEgressbyDeposit() method
public function moneyEgressbyDeposit()
{
    // Get active deposits
    $porcents = $this->porcent->select(
        ["id_porcent", "name", "status", "create_at"], 
        ["id_user[=]" => $this->id, "status[=]" => 1, "AND"]
    )->array();
    
    $ids_porcent = $this->getIdsPorcent($porcents, "id_porcent");
    if (count($ids_porcent) == 0) {
        return [];
    }
    
    // Get outflows associated with deposits
    $outflow_porcents = $this->outflow->in(["id_porcent[in]" => $ids_porcent])->array();
    
    // Calculate totals
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
```

### 4. Calculate Available Balance
The system subtracts outflows from inflows for each deposit:

```
Available Balance = Total Income - Total Outflows
```

```php
// ReportController.php - moneyDisponiblebyDeposit() method
public function moneyDisponiblebyDeposit()
{
    $money_disponible = $this->moneyTotalbyDeposit();
    $money_egress = $this->moneyEgressbyDeposit();
    
    for ($i = 0; $i < count($money_egress); $i++) {
        $money_disponible[$i]->total = intval($money_disponible[$i]->total) - intval($money_egress[$i]->total);
    }

    return httpResponse($money_disponible)->json();
}
```

### 5. Return JSON Response
The system returns the deposits with their calculated available balances as JSON.

```php
// Endpoint returns JSON directly
return httpResponse($money_disponible)->json();
```

## Alternative Flows

### AF1: No Deposits Found
If the user has no active deposits:
1. Returns an empty array `[]`
2. No error is thrown

### AF2: No Inflows/Outflows
If deposits exist but no inflows or outflows are associated:
- Inflows default to 0
- Outflows default to 0
- Available balance will be 0

### AF3: Negative Balance
If outflows exceed inflows:
- The `total` field will contain a negative value
- This indicates the user has overspent from that deposit

## Postconditions

1. The user receives a JSON array containing deposits with their available balances.
2. Each deposit object includes:
   - `id_porcent`: Deposit identifier
   - `name`: Deposit name
   - `status`: Deposit status
   - `create_at`: Creation date
   - `total`: Available balance (income - outflows)

## Business Rules Summary

| # | Rule | Description |
|---|------|-------------|
| 1 | Active Deposits Only | Only deposits with `status = 1` are considered |
| 2 | Available Balance Formula | Total Income - Total Outflows per deposit |
| 3 | Income Calculation | Sum of (inflow.total * inflow_porcent.porcent / 100) |
| 4 | Empty Result | Returns empty array if no deposits exist |
| 5 | Negative Balance | Allowed when outflows exceed income |

## Sequence Diagram (Simplified)

```
Frontend              Controller              Model              Database
  |                       |                      |                    |
  |-- GET request ------>|                      |                    |
  |                      |-- moneyDisponiblebyDeposit()            |
  |                      |                      |                    |
  |                      |-- moneyTotalbyDeposit()                 |
  |                      |   |-- query_complete ->|                 |
  |                      |<-- income totals -----|                    |
  |                      |                      |                    |
  |                      |-- moneyEgressbyDeposit()                |
  |                      |   |-- select --------->|                  |
  |                      |<-- outflow totals ----|                    |
  |                      |                      |                    |
  |                      |-- calculate: income - outflow           |
  |                      |                      |                    |
  |<-- JSON response ----|                      |                    |
```

## SQL Query Breakdown

```sql
SELECT p.*, 
       SUM(i.total * (ip.porcent / 100)) AS total
FROM porcents AS p
LEFT JOIN inflow_porcent AS ip ON ip.id_porcent = p.id_porcent
LEFT JOIN inflows AS i ON ip.id_inflow = i.id_inflow
WHERE p.id_user = {id_user} 
  AND p.status = 1
GROUP BY p.id_porcent
ORDER BY ip.id_porcent ASC
```

This query:
1. Gets all active deposits for the user
2. Joins the inflow_porcent junction table
3. Joins the inflows table
4. Calculates the portion of each inflow assigned to each deposit
5. Groups by deposit and sums the totals

## Related Methods

| Method | File | Description |
|--------|------|-------------|
| `moneyDisponiblebyDeposit()` | ReportController.php:57 | Public endpoint |
| `moneyTotalbyDeposit()` | ReportController.php:22 | Gets total income per deposit |
| `moneyEgressbyDeposit()` | ReportController.php:32 | Gets total outflows per deposit |
| `moneySpendbyDeposit()` | ReportController.php:52 | Alias for moneyEgressbyDeposit |
| `getIdsPorcent()` | ReportController.php:85 | Helper to extract deposit IDs |

## Usage Example

### Request
```
GET /report/moneyDisponiblebyDeposit
```

### Response Example
```json
[
  {
    "id_porcent": 1,
    "name": "Savings",
    "status": 1,
    "create_at": "2024-01-15 10:30:00",
    "total": 1500
  },
  {
    "id_porcent": 2,
    "name": "Investments",
    "status": 1,
    "create_at": "2024-02-20 14:00:00",
    "total": -200
  }
]
```

**Note**: The second deposit shows a negative balance, indicating overspending.

## Complete Code Example

### Controller Method (ReportController.php)

```php
public function moneyDisponiblebyDeposit()
{
    // Get total income per deposit
    $money_disponible = $this->moneyTotalbyDeposit();
    
    // Get total outflows per deposit
    $money_egress = $this->moneyEgressbyDeposit();
    
    // Calculate available balance: income - outflows
    for ($i = 0; $i < count($money_egress); $i++) {
        $money_disponible[$i]->total = intval($money_disponible[$i]->total) - intval($money_egress[$i]->total);
    }

    return httpResponse($money_disponible)->json();
}
```

### Frontend Call (statistics.js)

```javascript
async function loadMoneyDisponibleByDeposit() {
    try {
        const response = await fetch(`${URL_PROJECT}report/moneyDisponiblebyDeposit`);
        const data = await response.json();
        
        // Display available balances
        data.forEach(deposit => {
            console.log(`${deposit.name}: $${deposit.total}`);
        });
        
    } catch (error) {
        console.error('Error loading data:', error);
    }
}
```

## Related Use Cases

- [x] Register income (inflows)
- [x] Create outflow (expense)
- [x] View money spent by deposit
- [ ] Manage deposits (percentages)
- [ ] Create inflow with percentage allocation
