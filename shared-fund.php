<?php
$dataFile = '/var/www/manejo-finanzas-mcp/data/shared_fund_data.json';
$msg = '';

// Load data
if (file_exists($dataFile)) {
    $data = json_decode(file_get_contents($dataFile), true);
} else {
    $data = [];
}

if (!is_array($data)) $data = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $month = (int)($_POST['month'] ?? date('n'));
    $year = (int)($_POST['year'] ?? date('Y'));
    $andres = (float)($_POST['andres'] ?? 0);
    $ivan = (float)($_POST['ivan'] ?? 0);
    $key = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT);
    
    if ($action === 'add') {
        if (!isset($data[$key])) {
            $data[$key] = ['andres' => 0, 'ivan' => 0, 'total' => 0];
        }
        $data[$key]['andres'] += $andres;
        $data[$key]['ivan'] += $ivan;
        $data[$key]['total'] = $data[$key]['andres'] + $data[$key]['ivan'];
        $msg = "Aporte agregado exitosamente";
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
    }
}

// Calculate totals
$totalAndres = 0; $totalIvan = 0;
foreach ($data as $v) {
    $totalAndres += $v['andres'];
    $totalIvan += $v['ivan'];
}
$total = $totalAndres + $totalIvan;

// Prepare chart data
$months = array_keys($data);
$andresData = [];
$ivanData = [];
$cumulativeAndres = 0;
$cumulativeIvan = 0;

foreach ($data as $mes => $v) {
    $cumulativeAndres += $v['andres'];
    $cumulativeIvan += $v['ivan'];
    $andresData[] = $cumulativeAndres;
    $ivanData[] = $cumulativeIvan;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fondo Compartido</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --success: #10b981;
    --andres-color: #3b82f6;
    --ivan-color: #ef4444;
    --bg: #f8fafc;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
body { background: var(--bg); font-family: 'Segoe UI', system-ui, sans-serif; min-height: 100vh; }
.navbar { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); }
.card { border: none; border-radius: 16px; box-shadow: var(--card-shadow); overflow: hidden; }
.card-header { background: white; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
.btn-primary { background: var(--primary); border: none; padding: 12px 24px; border-radius: 10px; }
.btn-primary:hover { background: var(--primary-dark); }
.total-card { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
.total-card .display-4 { font-weight: 700; }
.person-card { transition: transform 0.2s; }
.person-card:hover { transform: translateY(-4px); }
.andres-card { border-left: 4px solid var(--andres-color); }
.ivan-card { border-left: 4px solid var(--ivan-color); }
.table { margin-bottom: 0; }
.table thead th { background: #f1f5f9; border: none; font-weight: 600; color: #475569; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
.table tbody tr { transition: background 0.2s; }
.table tbody tr:hover { background: #f8fafc; }
.andres-text { color: var(--andres-color); font-weight: 600; }
.ivan-text { color: var(--ivan-color); font-weight: 600; }
.form-control, .form-select { border-radius: 10px; border: 2px solid #e2e8f0; padding: 12px; }
.form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
.badge-andres { background: var(--andres-color); }
.badge-ivan { background: var(--ivan-color); }
.alert-success { border-radius: 10px; border: none; background: #d1fae5; color: #065f46; }
.chart-container { position: relative; height: 280px; }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">
            <i class="bi bi-bank2 me-2"></i>Fondo Compartido
        </a>
        <span class="navbar-text text-white-50">Andres & Ivan</span>
    </div>
</nav>

<div class="container pb-5">
<?php if ($msg): ?>
<div class="alert alert-success d-flex align-items-center mb-4">
    <i class="bi bi-check-circle-fill me-2"></i><?= $msg ?>
</div>
<?php endif; ?>

<!-- Total Card -->
<div class="card total-card mb-4">
    <div class="card-body text-center py-4">
        <h5 class="text-white-50 mb-2">Total Acumulado</h5>
        <p class="display-3 mb-0">$<?= number_format($total) ?></p>
    </div>
</div>

<!-- Person Cards -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card person-card andres-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-person-circle text-andres fs-1 mb-3 d-block"></i>
                <h6 class="text-muted mb-2">Andres</h6>
                <h3 class="andres-text mb-0">$<?= number_format($totalAndres) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card person-card ivan-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-person-circle text-ivan fs-1 mb-3 d-block"></i>
                <h6 class="text-muted mb-2">Ivan</h6>
                <h3 class="ivan-text mb-0">$<?= number_format($totalIvan) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center">
        <i class="bi bi-graph-up-arrow me-2"></i>Evolución Acumulada
    </div>
    <div class="card-body">
        <div class="chart-container">
            <canvas id="chart"></canvas>
        </div>
    </div>
</div>

<!-- Add Form -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center">
        <i class="bi bi-plus-circle me-2"></i>Agregar Aporte
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Mes</label>
                    <select name="month" class="form-select">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= ($m == date('n')) ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m)) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Año</label>
                    <select name="year" class="form-select">
                        <?php for ($y = 2025; $y <= 2030; $y++): ?>
                        <option value="<?= $y ?>" <?= ($y == 2026) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Andres ($)</label>
                    <input type="number" name="andres" class="form-control" placeholder="0" min="0" step="1000">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ivan ($)</label>
                    <input type="number" name="ivan" class="form-control" placeholder="0" min="0" step="1000">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-plus-lg me-2"></i>Agregar Aporte
                </button>
            </div>
        </form>
    </div>
</div>

<!-- History Table -->
<div class="card">
    <div class="card-header d-flex align-items-center">
        <i class="bi bi-clock-history me-2"></i>Historial por Mes
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th>Andres</th>
                        <th>Ivan</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No hay registros aún</td></tr>
                    <?php else: ?>
                    <?php foreach (array_reverse($data, true) as $mes => $v): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?= $mes ?></span></td>
                        <td class="andres-text">$<?= number_format($v['andres']) ?></td>
                        <td class="ivan-text">$<?= number_format($v['ivan']) ?></td>
                        <td class="fw-bold">$<?= number_format($v['total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<footer class="text-center text-muted py-4 mt-5">
    <small>🏦 Fondo Compartido - Sistema de Gestión</small>
</footer>

<script>
const ctx = document.getElementById('chart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
            {
                label: 'Andres',
                data: <?= json_encode($andresData) ?>,
                backgroundColor: '#3b82f6',
                borderRadius: 6
            },
            {
                label: 'Ivan',
                data: <?= json_encode($ivanData) ?>,
                backgroundColor: '#ef4444',
                borderRadius: 6
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: v => '$' + v.toLocaleString()
                },
                grid: { color: '#f1f5f9' }
            },
            x: { grid: { display: false } }
        }
    }
});
</script>
</body>
</html>
