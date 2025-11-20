<style>
    .card-stat {
        border-radius: 10px;
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.2s;
        margin-bottom: 20px;
        padding: 20px;
        text-align: center;
    }
    .card-stat:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    .stat-icon {
        font-size: 2.5rem;
        opacity: 0.8;
        margin-bottom: 10px;
    }
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        margin: 10px 0;
    }
    .stat-label {
        
        color: #000;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .bg-blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .bg-green { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
    .bg-orange { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
    .bg-red { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; }
    .bg-purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .bg-yellow { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; }
    .bg-cyan { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; }
    .bg-light { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; }
    .section-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 30px 0 20px 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #3498db;
    }
    .chart-container {
        position: relative;
        height: 300px;
        margin-top: 20px;
    }
    .panel-dashboard {
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .panel-dashboard .panel-heading {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
    }
    .jumbotron {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        margin-bottom: 30px;
        border-radius: 10px;
    }
    .btn-dashboard {
        margin-bottom: 10px;
        transition: all 0.3s;
    }
    .btn-dashboard:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
</style>

<div class="jumbotron text-center">
    <h2><?= __t_db('admin.dashboard.title', 'Panel Administrativo') ?></h2>
    <p>
        <?= __t_db('admin.dashboard.welcome', 'Bienvenido') ?>,
        <strong><?= htmlspecialchars($userName) ?></strong>
        (<?= __t_db('role.' . $userRole, ucfirst($userRole)) ?>)
    </p>
</div>

<!-- PUNTO 1: OCUPACIÓN DE HABITACIONES -->
<div class="section-title">
    <i class="fa fa-bed"></i> <?= __t_db('admin.dashboard.room_occupancy', 'Ocupación de Habitaciones') ?>
</div>

<div class="row">
    <div class="col-sm-3">
        <div class="card-stat bg-blue">
            <i class="fa fa-bed stat-icon"></i>
            <div class="stat-number"><?= $ocupacionHabitaciones['ocupadas'] ?? 0 ?></div>
            <div class="stat-label"><?= __t_db('admin.dashboard.occupied_rooms', 'Habitaciones Ocupadas') ?></div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card-stat bg-green">
            <i class="fa fa-check-circle stat-icon"></i>
            <div class="stat-number"><?= $ocupacionHabitaciones['disponibles'] ?? 0 ?></div>
            <div class="stat-label"><?= __t_db('admin.dashboard.available_rooms', 'Habitaciones Disponibles') ?></div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card-stat bg-orange">
            <i class="fa fa-wrench stat-icon"></i>
            <div class="stat-number"><?= $ocupacionHabitaciones['mantenimiento'] ?? 0 ?></div>
            <div class="stat-label"><?= __t_db('admin.dashboard.maintenance_rooms', 'En Mantenimiento') ?></div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card-stat bg-red">
            <i class="fa fa-percent stat-icon"></i>
            <div class="stat-number"><?= $ocupacionHabitaciones['porcentaje_ocupacion'] ?? 0 ?>%</div>
            <div class="stat-label"><?= __t_db('admin.dashboard.total_occupancy', 'Ocupación Total') ?></div>
        </div>
    </div>
</div>

<!-- Gráficos de Ocupación -->
<div class="row">
    <div class="col-md-6">
        <div class="panel panel-default panel-dashboard">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pie-chart"></i> <?= __t_db('admin.dashboard.room_distribution', 'Distribución de Habitaciones') ?></h3>
            </div>
            <div class="panel-body">
                <div class="chart-container">
                    <canvas id="chartOcupacion"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel panel-default panel-dashboard">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-bar-chart"></i> <?= __t_db('admin.dashboard.occupancy_by_type', 'Ocupación por Tipo de Habitación') ?></h3>
            </div>
            <div class="panel-body">
                <div class="chart-container">
                    <canvas id="chartTipoHabitacion"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PUNTO 2: ESTADÍSTICAS DE RESERVAS -->
<div class="section-title">
    <i class="fa fa-calendar-check-o"></i> <?= __t_db('admin.dashboard.reservation_stats', 'Estadísticas de Reservas') ?>
</div>

<div class="row">
    <div class="col-sm-3">
        <div class="card-stat bg-purple">
            <i class="fa fa-calendar stat-icon"></i>
            <div class="stat-number"><?= $estadisticasReservas['reservas_hoy'] ?? 0 ?></div>
            <div class="stat-label"><?= __t_db('admin.dashboard.today_reservations', 'Reservas de Hoy') ?></div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card-stat bg-yellow">
            <i class="fa fa-sign-in stat-icon"></i>
            <div class="stat-number"><?= $estadisticasReservas['checkins_pendientes'] ?? 0 ?></div>
            <div class="stat-label"><?= __t_db('admin.dashboard.pending_checkins', 'Check-ins Pendientes') ?></div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card-stat bg-cyan">
            <i class="fa fa-sign-out stat-icon"></i>
            <div class="stat-number"><?= $estadisticasReservas['checkouts_pendientes'] ?? 0 ?></div>
            <div class="stat-label"><?= __t_db('admin.dashboard.pending_checkouts', 'Check-outs Pendientes') ?></div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card-stat bg-light">
            <i class="fa fa-calendar-o stat-icon"></i>
            <div class="stat-number"><?= $estadisticasReservas['proximas_reservas'] ?? 0 ?></div>
            <div class="stat-label"><?= __t_db('admin.dashboard.upcoming_reservations', 'Próximas Reservas (7 días)') ?></div>
        </div>
    </div>
</div>

<!-- Gráficos de Reservas -->
<div class="row">
    <div class="col-md-6">
        <div class="panel panel-default panel-dashboard">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-line-chart"></i> <?= __t_db('admin.dashboard.reservations_per_day', 'Reservas por Día (Últimos 7 días)') ?></h3>
            </div>
            <div class="panel-body">
                <div class="chart-container">
                    <canvas id="chartReservasDias"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel panel-default panel-dashboard">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pie-chart"></i> <?= __t_db('admin.dashboard.reservation_status', 'Estado de Reservas') ?></h3>
            </div>
            <div class="panel-body">
                <div class="chart-container">
                    <canvas id="chartEstadoReservas"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PUNTO 3: TOP 5 HABITACIONES -->
<div class="section-title">
    <i class="fa fa-star"></i> <?= __t_db('admin.dashboard.top5_rooms', 'Top 5 Habitaciones / Tipos Más Solicitados') ?>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="panel panel-default panel-dashboard">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-trophy"></i> <?= __t_db('admin.dashboard.top5_room_types', 'Top 5 Tipos de Habitación Más Reservados') ?></h3>
            </div>
            <div class="panel-body">
                <div class="chart-container">
                    <canvas id="chartTop5"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-default panel-dashboard">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pie-chart"></i> <?= __t_db('admin.dashboard.reservation_proportion', 'Proporción de Reservas') ?></h3>
            </div>
            <div class="panel-body">
                <div class="chart-container">
                    <canvas id="chartProporcion"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Accesos Rápidos -->
<div class="section-title">
    <i class="fa fa-th-large"></i> <?= __t_db('admin.dashboard.quick_access', 'Accesos Rápidos') ?>
</div>

<div class="row text-center">
    <div class="col-sm-3">
        <a href="index.php?controller=habitacionesAdmin&action=index" class="btn btn-primary btn-lg btn-block btn-dashboard">
            <i class="fa fa-bed"></i><br>
            <?= __t_db('admin.dashboard.rooms', 'Habitaciones') ?>
        </a>
    </div>
    <div class="col-sm-3">
        <a href="index.php?controller=serviciosAdmin&action=index" class="btn btn-default btn-lg btn-block btn-dashboard">
            <i class="fa fa-cogs"></i><br>
            <?= __t_db('admin.menu.services', 'Servicios') ?>
        </a>
    </div>
    <div class="col-sm-3">
        <a href="index.php?controller=adminReserva&action=listarSolicitudes" class="btn btn-info btn-lg btn-block btn-dashboard">
            <i class="fa fa-calendar"></i><br>
            <?= __t_db('admin.dashboard.reservations', 'Reservas') ?>
        </a>
    </div>
    <div class="col-sm-3">
        <a href="index.php?controller=usuariosAdmin&action=index" class="btn btn-success btn-lg btn-block btn-dashboard">
            <i class="fa fa-user"></i><br>
            <?= __t_db('admin.dashboard.users', 'Usuarios') ?>
        </a>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Datos desde PHP
const ocupacionData = <?= json_encode($ocupacionHabitaciones ?? ['ocupadas' => 0, 'disponibles' => 0, 'mantenimiento' => 0]) ?>;
const ocupacionPorTipoData = <?= json_encode($ocupacionPorTipo ?? []) ?>;
const reservasPorDiaData = <?= json_encode($reservasPorDia ?? []) ?>;
const estadoReservasData = <?= json_encode($estadoReservas ?? ['confirmadas' => 0, 'en_espera' => 0, 'canceladas' => 0]) ?>;
const top5Data = <?= json_encode($top5Habitaciones ?? []) ?>;

// Configuración global de Chart.js
Chart.defaults.font.family = "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
Chart.defaults.font.size = 13;

// Gráfico 1: Distribución de Habitaciones (Circular)
const ctx1 = document.getElementById('chartOcupacion').getContext('2d');
new Chart(ctx1, {
    type: 'doughnut',
    data: {
        labels: [
            '<?= __t_db('admin.dashboard.occupied', 'Ocupadas') ?>',
            '<?= __t_db('admin.dashboard.available', 'Disponibles') ?>',
            '<?= __t_db('admin.dashboard.maintenance', 'Mantenimiento') ?>'
        ],
        datasets: [{
            data: [
                ocupacionData.ocupadas || 0,
                ocupacionData.disponibles || 0,
                ocupacionData.mantenimiento || 0
            ],
            backgroundColor: ['#667eea', '#43e97b', '#fee140'],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { 
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: { size: 12 }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += context.parsed + ' habitaciones';
                        return label;
                    }
                }
            }
        }
    }
});

// Gráfico 2: Ocupación por Tipo (Barras)
const ctx2 = document.getElementById('chartTipoHabitacion').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: ocupacionPorTipoData.map(item => item.tipo_nombre),
        datasets: [{
            label: '<?= __t_db('admin.dashboard.occupied_rooms', 'Habitaciones Ocupadas') ?>',
            data: ocupacionPorTipoData.map(item => item.ocupadas),
            backgroundColor: '#667eea',
            borderRadius: 8,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { 
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: { display: false }
        }
    }
});

// Gráfico 3: Reservas por Día (Barras)
const ctx3 = document.getElementById('chartReservasDias').getContext('2d');
new Chart(ctx3, {
    type: 'bar',
    data: {
        labels: reservasPorDiaData.map(item => {
            const fecha = new Date(item.fecha);
            return fecha.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit' });
        }),
        datasets: [{
            label: '<?= __t_db('admin.dashboard.reservations', 'Reservas') ?>',
            data: reservasPorDiaData.map(item => item.total),
            backgroundColor: '#f5576c',
            borderRadius: 8,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { 
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: { display: false }
        }
    }
});

// Gráfico 4: Estado de Reservas (Circular)
const ctx4 = document.getElementById('chartEstadoReservas').getContext('2d');
new Chart(ctx4, {
    type: 'pie',
    data: {
        labels: [
            '<?= __t_db('admin.dashboard.confirmed', 'Confirmadas') ?>',
            '<?= __t_db('admin.dashboard.waiting', 'En Espera') ?>',
            '<?= __t_db('admin.dashboard.cancelled', 'Canceladas') ?>'
        ],
        datasets: [{
            data: [
                estadoReservasData.confirmadas || 0,
                estadoReservasData.en_espera || 0,
                estadoReservasData.canceladas || 0
            ],
            backgroundColor: ['#43e97b', '#fee140', '#f5576c'],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { 
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: { size: 12 }
                }
            }
        }
    }
});

// Gráfico 5: Top 5 Habitaciones (Barras horizontales)
const ctx5 = document.getElementById('chartTop5').getContext('2d');
new Chart(ctx5, {
    type: 'bar',
    data: {
        labels: top5Data.map(item => item.tipo_nombre),
        datasets: [{
            label: '<?= __t_db('admin.dashboard.reservations_last_month', 'Reservas (último mes)') ?>',
            data: top5Data.map(item => item.total_reservas),
            backgroundColor: ['#667eea', '#f5576c', '#00f2fe', '#43e97b', '#fee140'],
            borderRadius: 8,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        scales: {
            x: { 
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: { display: false }
        }
    }
});

// Gráfico 6: Proporción de Reservas (Circular)
const ctx6 = document.getElementById('chartProporcion').getContext('2d');
new Chart(ctx6, {
    type: 'doughnut',
    data: {
        labels: top5Data.map(item => item.tipo_nombre),
        datasets: [{
            data: top5Data.map(item => item.total_reservas),
            backgroundColor: ['#667eea', '#f5576c', '#00f2fe', '#43e97b', '#fee140'],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { 
                position: 'bottom',
                labels: {
                    padding: 10,
                    font: { size: 11 }
                }
            }
        }
    }
});
</script>