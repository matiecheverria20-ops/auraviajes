<?php
require_once "conexion.php";

$stmtDestinos = $pdo->query("SELECT * FROM destino ORDER BY NombreUbicacion ASC");
$destinos = $stmtDestinos->fetchAll();

$sqlRutas = "SELECT r.ID_Ruta, d1.NombreUbicacion AS Origen, d2.NombreUbicacion AS Destino, r.DistanciaTotal, r.TiempoEstimado
    FROM rutas r
    INNER JOIN destino d1 ON r.ID_Inicio = d1.ID_Destino
    INNER JOIN destino d2 ON r.ID_Destino = d2.ID_Destino";
$rutas = $pdo->query($sqlRutas)->fetchAll();

$sqlViajes = "SELECT v.ID_Viaje, p.Nombre AS Proveedor, h.Nombre AS Hotel, h.Ubicacion AS HotelUbicacion, 
                     pen.Opcion AS Pension, v.FechaSalida, v.FechaLlegada, v.Estado
              FROM viajes v
              LEFT JOIN proveedores p ON v.FK_ID_Proveedor = p.ID_Proveedor
              LEFT JOIN hoteles h ON v.FK_ID_Hotel = h.ID_Hotel
              LEFT JOIN pension pen ON v.FK_ID_Pension = pen.ID_Pension";
$viajes = $pdo->query($sqlViajes)->fetchAll();

$eventos = $pdo->query("SELECT * FROM eventos ORDER BY Fecha_Inicio ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aura Viajes — Tu próximo destino te espera</title>
  <link rel="stylesheet" href="aura.css">
</head>
<body>

  <header>
    <img src="imagenes/logo.png" alt="Logo Aura Viajes">
    <nav>
      <a href="index.php" class="active">Inicio</a>
      <div class="nav-dropdown">
        <button class="nav-dropbtn">Destinos ▾</button>
        <div class="nav-dropmenu">
          <?php foreach ($destinos as $dest): ?>
            <div class="dropmenu-col">
              <span class="dropmenu-title"><?= htmlspecialchars($dest['NombreUbicacion']) ?></span>
              <a href="paquetes.php?destino=<?= urlencode($dest['NombreUbicacion']) ?>">Ver paquetes</a>
              <?php if (!empty($dest['Link'])): ?>
                <a href="<?= htmlspecialchars($dest['Link']) ?>" target="_blank">Ver en mapa →</a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <a href="paquetes.php">Paquetes</a>
      <a href="movilidad.php">Movilidad</a>
      <a href="solicitudes.php">Solicitudes</a>
      <a href="agente.php">Agente</a>
      <a href="encuesta.php">Encuesta</a>
    </nav>

    <div class="header-phone">
      <span class="phone-label">Venta telefónica</span>
      <a href="tel:08002222872" class="phone-number">0800-222-AURA</a>
    </div>
  </header>

  <!-- HERO + BUSCADOR -->
  <section class="hero">
    <div class="hero-badge">Sistema de Agencia de Viajes</div>
    <h1>Bienvenido a <em>Aura Viajes</em></h1>
    <p>Encontrá tu próximo destino con vuelo, hotel y todo incluido</p>

    <div class="search-box">
      <div class="search-tabs">
        <button class="search-tab active">Paquetes</button>
      </div>
      <form class="search-form" id="search-form" action="paquetes.php" method="GET">
        <div class="form-group">
          <label for="destino">Destino</label>
          <input type="text" id="destino" name="destino" placeholder="Formosa, Bariloche, Salta..." list="destinos-db">
          <datalist id="destinos-db">
            <?php foreach ($destinos as $d): ?>
              <option value="<?= htmlspecialchars($d['NombreUbicacion']) ?>"></option>
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="form-group">
          <label for="mes">Mes de viaje</label>
          <select id="mes" name="mes">
            <option value="">Cualquier mes</option>
            <option value="01">Enero</option><option value="02">Febrero</option>
            <option value="03">Marzo</option><option value="04">Abril</option>
            <option value="05">Mayo</option><option value="06">Junio</option>
            <option value="07">Julio</option><option value="08">Agosto</option>
            <option value="09">Septiembre</option><option value="10">Octubre</option>
            <option value="11">Noviembre</option><option value="12">Diciembre</option>
          </select>
        </div>
        <button type="submit" class="btn-search">Buscar Paquetes</button>
      </form>
    </div>
  </section>

  <!-- BARRA DE BENEFICIOS -->
  <section class="features-bar">
    <div class="features-container">
      <div class="feature-item">
        <img src="imagenes/tarjeta.png" alt="tarjeta" class="iconos">
        <div><h4>Cuotas y Financiación</h4><p>Múltiples medios de pago</p></div>
      </div>
      <div class="feature-item">
        <img src="imagenes/medico.png" alt="médico" class="iconos">
        <div><h4>Viajá Seguro</h4><p>Asistencia médica incluida</p></div>
      </div>
      <div class="feature-item">
        <img src="imagenes/atencion.png" alt="atención" class="iconos">
        <div><h4>Atención Personalizada</h4><p>Agentes a tu disposición</p></div>
      </div>
      <div class="feature-item">
        <img src="imagenes/precio.png" alt="precio" class="iconos">
        <div><h4>Mejor Precio</h4><p>Garantía en todos los paquetes</p></div>
      </div>
    </div>
  </section>

  <!-- CARRUSEL DE DESTINOS (tus imágenes locales) -->
  <section class="carusel-section">
    <div class="carusel-wrapper">
      <div class="carusel-track" id="carusel-track">

        <div class="carusel-slide active">
          <img src="imagenes/BA.jpg" alt="Buenos Aires">
          <div class="carusel-caption">
            <div class="carusel-country">Argentina</div>
            <div class="carusel-city">Buenos Aires</div>
            <div class="carusel-sub">La capital que nunca duerme</div>
            <a href="paquetes.html" class="carusel-cta">Ver paquetes</a>
          </div>
        </div>

        <div class="carusel-slide">
          <img src="imagenes/rio.jpg" alt="Rio de Janeiro">
          <div class="carusel-caption">
            <div class="carusel-country">Brasil</div>
            <div class="carusel-city">Rio de Janeiro</div>
            <div class="carusel-sub">Maravilla natural del mundo</div>
            <a href="paquetes.html" class="carusel-cta">Ver paquetes</a>
          </div>
        </div>

        <div class="carusel-slide">
          <img src="imagenes/asuncion.jpg" alt="Asunción">
          <div class="carusel-caption">
            <div class="carusel-country">Paraguay</div>
            <div class="carusel-city">Asunción</div>
            <div class="carusel-sub">Madre de ciudades</div>
            <a href="paquetes.html" class="carusel-cta">Ver paquetes</a>
          </div>
        </div>

      </div>

      <!-- Flechas -->
      <button class="carousel-arrow carousel-prev" onclick="moveCarousel(-1)">&#8249;</button>
      <button class="carousel-arrow carousel-next" onclick="moveCarousel(1)">&#8250;</button>

      <!-- Dots -->
      <div class="carousel-dots">
        <button class="carousel-dot active" onclick="goTo(0)"></button>
        <button class="carousel-dot" onclick="goTo(1)"></button>
        <button class="carousel-dot" onclick="goTo(2)"></button>
      </div>
    </div>
  </section>

  <!-- MÓDULOS DE ACCESO RÁPIDO -->
  <section class="modulos">
    <h2>Accesos rápidos al sistema</h2>
    <div class="grid">
      <a href="paquetes.php" class="mod-card">
        <img src="imagenes/paquetes.png" alt="paquetes" class="iconos">
        <h3>Paquetes turísticos</h3>
        <p>Filtrá paquetes con hotel, pensión y transporte incluido.</p>
        <span class="arrow">Ver paquetes →</span>
      </a>
      <a href="movilidad.php" class="mod-card">
        <img src="imagenes/avion.png" alt="avion" class="iconos">
        <h3>Movilidad y Traslados</h3>
        <p>Rutas, vehículos y transporte disponible.</p>
        <span class="arrow">Ver movilidad →</span>
      </a>
      <a href="pago.php" class="mod-card">
        <img src="imagenes/pago.png" alt="pago" class="iconos">
        <h3>Pagos</h3>
        <p>Gestión de pagos y métodos disponibles.</p>
        <span class="arrow">Ir a pagos →</span>
      </a>
    </div>
  </section>

  <!-- SECCIÓN BD: Rutas disponibles -->
  <section class="pkg-section">
    <div class="pkg-section-head">
      <h2>Rutas registradas</h2>
    </div>
    <div class="pkg-list">
      <?php foreach ($rutas as $r): ?>
        <div class="pkg-item" style="color: #fff;">
          <div class="pkg-item-noches"><?= $r['DistanciaTotal'] ?> km</div>
          <div class="pkg-item-name">
            <strong><?= htmlspecialchars($r['Origen']) ?> ➔ <?= htmlspecialchars($r['Destino']) ?></strong>
          </div>
          <div class="pkg-item-precio">Tiempo est.: <?= $r['TiempoEstimado'] ?> hs</div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- SECCIÓN BD: Paquetes y Viajes con Hotel -->
  <section class="pkg-section">
    <div class="pkg-section-head">
      <h2>Viajes y Hoteles Programados</h2>
      <a href="paquetes.php" class="ver-todos">Ver todos los paquetes →</a>
    </div>
    <div class="pkg-list">
      <?php foreach ($viajes as $v): ?>
        <a href="detalle_paquete.php?id=<?= $v['ID_Viaje'] ?>" class="pkg-item" style="color: #fff;">
          <div class="pkg-item-noches"><?= htmlspecialchars($v['Estado']) ?></div>
          <div class="pkg-item-name">
            <strong>Hotel: <?= htmlspecialchars($v['Hotel'] ?? 'Sin hotel asignado') ?></strong>
            <br><small>Pensión: <?= htmlspecialchars($v['Pension'] ?? 'N/A') ?> | Salida: <?= date('d/m/Y H:i', strtotime($v['FechaSalida'])) ?></small>
          </div>
          <div class="pkg-item-precio">Ver Detalles ➔</div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- SECCIÓN BD: Eventos -->
  <section class="pkg-section">
    <div class="pkg-section-head">
      <h2>Eventos y Festivales Registrados</h2>
    </div>
    <div class="pkg-list">
      <?php foreach ($eventos as $ev): ?>
        <div class="pkg-item" style="color: #fff;">
          <div class="pkg-item-noches">Evento</div>
          <div class="pkg-item-name">
            <strong><?= htmlspecialchars($ev['Nombre']) ?></strong>
            <br><small>Del <?= $ev['Fecha_Inicio'] ?> al <?= $ev['Fecha_Final'] ?></small>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <footer>
      <div class="footer-top">
        <div class="footer-grid">
          <div class="footer-col footer-brand">
            <img src="imagenes/logo.png" alt="Aura Viajes" class="footer-logo">
            <div class="footer-bottom">
            <span><strong>Aura Viajes S.A.</strong></span>
            <span>Todos los derechos reservados 2026</span>
      </div>
            <p>Horario de atención: Lunes a Viernes de 9 a 20 hs y Sábados 9 a 18 hs</p>
            <a href="tel:08002222872" class="footer-phone">0800-222-AURA</a>
            <a href="mailto:info@auraviajes.com" class="footer-email">info@auraviajes.com</a>
        </div>
          <div class="footer-col">
            <h4>Información útil</h4>
            <a href="#">Documentación para salir del país</a>
            <a href="#">Políticas de cancelación</a>
            <a href="#">Términos y condiciones</a>
            <a href="#">Política de privacidad</a>
            <a href="#">Medios de pago</a>
          </div>
          <div class="footer-col">
            <h4>Sucursales</h4>
            <a href="#">Formosa Centro — Av. 25 de Mayo 850</a>
            <a href="#">Resistencia — Av. Sarmiento 1250</a>
            <a href="#">Posadas — Calle Bolívar 540</a>
            <a href="#">Salta — Av. Belgrano 920</a>
            <a href="#">Mendoza — Av. San Martín 650</a>
          </div>
        </div>
      </div>
    </footer>
</body>
</html>