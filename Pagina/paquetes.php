<?php

require_once 'conexion.php';

$filtroDestino = $_GET['destino'] ?? '';
$filtroPension = $_GET['pension'] ?? '';
$filtroMedio = $_GET['medio'] ?? '';

$sql = "SELECT v.ID_Viaje, 
               dOrigen.NombreUbicacion AS Origen, 
               dDestino.NombreUbicacion AS Destino, 
               h.Nombre AS Hotel, 
               h.Ubicacion AS HotelUbicacion, 
               p.Opcion AS Pension, 
               mt.Nombre AS MedioTransporte, 
               c.Clase AS ClaseTransporte, 
               pr.Nombre AS Proveedor,
               v.FechaSalida, 
               v.FechaLlegada, 
               v.Estado
        FROM viajes v
        INNER JOIN rutas r ON v.FK_ID_Ruta = r.ID_Ruta
        INNER JOIN destino dOrigen ON r.ID_Inicio = dOrigen.ID_Destino
        INNER JOIN destino dDestino ON r.ID_Destino = dDestino.ID_Destino
        LEFT JOIN hoteles h ON v.FK_ID_Hotel = h.ID_Hotel
        LEFT JOIN pension p ON v.FK_ID_Pension = p.ID_Pension
        LEFT JOIN vehiculo veh ON v.FK_ID_Vehiculo = veh.ID_Vehiculo
        LEFT JOIN mediotransporte mt ON veh.FK_ID_MedioTrans = mt.ID_MedioTrans
        LEFT JOIN clases c ON veh.FK_ID_Clase = c.ID_Clase
        LEFT JOIN proveedores pr ON v.FK_ID_Proveedor = pr.ID_Proveedor
        WHERE 1=1";

$params = [];

if (!empty($filtroDestino)) {
    $sql .= " AND dDestino.NombreUbicacion LIKE :destino";
    $params[':destino'] = "%$filtroDestino%";
}

if (!empty($filtroPension)) {
    $sql .= " AND v.FK_ID_Pension = :pension";
    $params[':pension'] = $filtroPension;
}

if (!empty($filtroMedio)) {
    $sql .= " AND veh.FK_ID_MedioTrans = :medio";
    $params[':medio'] = $filtroMedio;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$paquetes = $stmt->fetchAll();

// Cargar opciones para filtros
$pensiones = $pdo->query("SELECT * FROM pension")->fetchAll();
$medios = $pdo->query("SELECT * FROM mediotransporte")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Paquetes Disponibles — Aura Viajes</title>
  <link rel="stylesheet" href="aura.css">
  <style>
    .filter-card { background: #fff; padding: 1.5rem; border-radius: 12px; margin: 2rem auto; max-width: 960px; }
    .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
    .pkg-grid { max-width: 960px; margin: 0 auto; display: flex; flex-direction: column; gap: 1rem; }
    .pkg-box { background: #005778; color: white; padding: 1.2rem; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; }
  </style>
</head>
<body>
  <header>
    <img src="imagenes/logo.png" alt="Logo">
    <nav>
      <a href="index.php">Inicio</a>
      <a href="paquetes.php" class="active">Paquetes</a>
      <a href="movilidad.php">Movilidad</a>
    </nav>
  </header>

  <div class="filter-card">
    <h2>Filtrar Paquetes Disponibles</h2>
    <form method="GET" action="paquetes.php" class="filter-form">
      <div>
        <label>Destino:</label>
        <input type="text" name="destino" value="<?= htmlspecialchars($filtroDestino) ?>" placeholder="Ej: Mendoza, Salta">
      </div>
      <div>
        <label>Pensión:</label>
        <select name="pension">
          <option value="">Todas</option>
          <?php foreach ($pensiones as $p): ?>
            <option value="<?= $p['ID_Pension'] ?>" <?= $filtroPension == $p['ID_Pension'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['Opcion']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Medio de Transporte:</label>
        <select name="medio">
          <option value="">Todos</option>
          <?php foreach ($medios as $m): ?>
            <option value="<?= $m['ID_MedioTrans'] ?>" <?= $filtroMedio == $m['ID_MedioTrans'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($m['Nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="align-self: end;">
        <button type="submit" class="btn-search">Aplicar Filtros</button>
      </div>
    </form>
  </div>

  <section class="pkg-grid">
    <h2>Resultados (<?= count($paquetes) ?> paquetes encontrados)</h2>
    <?php if (empty($paquetes)): ?>
      <p>No se encontraron paquetes con los criterios seleccionados.</p>
    <?php else: ?>
      <?php foreach ($paquetes as $pkg): ?>
        <div class="pkg-box">
          <div>
            <h3><?= htmlspecialchars($pkg['Origen']) ?> ➔ <?= htmlspecialchars($pkg['Destino']) ?></h3>
            <p><strong>Hotel:</strong> <?= htmlspecialchars($pkg['Hotel'] ?? 'Sin hotel') ?> (<?= htmlspecialchars($pkg['HotelUbicacion'] ?? 'N/A') ?>)</p>
            <p><strong>Pensión:</strong> <?= htmlspecialchars($pkg['Pension'] ?? 'No especificada') ?></p>
            <p><strong>Transporte:</strong> <?= htmlspecialchars($pkg['MedioTransporte'] ?? 'N/A') ?> (Clase: <?= htmlspecialchars($pkg['ClaseTransporte'] ?? 'N/A') ?>)</p>
            <small>Salida: <?= date('d/m/Y H:i', strtotime($pkg['FechaSalida'])) ?></small>
          </div>
          <div>
            <a href="detalle_paquete.php?id=<?= $pkg['ID_Viaje'] ?>" class="btn-search" style="text-decoration:none; display:inline-block;">Ver Características</a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</body>
</html>