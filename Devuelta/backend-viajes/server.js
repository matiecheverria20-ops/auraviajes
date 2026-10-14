const express = require('express');
const mysql = require('mysql2');
const cors = require('cors');

const app = express();
app.use(express.json());
app.use(cors());

// Conexión a la Base de Datos
const db = mysql.createConnection({
  host: 'localhost',
  user: 'root',
  password: '',
  database: 'sistema_viajes'
});

db.connect((err) => {
  if (err) {
    console.error('Error conectando a la BD:', err);
    return;
  }
  console.log('¡Conectado a la Base de Datos sistema_viajes!');
});

// ==========================================
// ENDPOINT 2: Obtener Itinerario Activo
// ==========================================
app.get('/api/itinerario/:idPasajero', (req, res) => {
  const { idPasajero } = req.params;

  const query = `
    SELECT 
      v.ID_Viaje,
      p.NroTicket,
      p.Estado AS EstadoPasaje,
      v.FechaSalida,
      v.FechaLlegada,
      d.NombreUbicacion AS DestinoNombre,
      d.Link AS DestinoMapa,
      h.Nombre AS HotelNombre
    FROM pasaje p
    JOIN viajes v ON p.FK_ID_Viaje = v.ID_Viaje
    JOIN pasajero pa ON p.FK_ID_Pasajero = pa.ID_Pasajero
    JOIN rutas r ON v.FK_ID_Ruta = r.ID_Ruta
    JOIN destino d ON r.ID_Destino = d.ID_Destino
    LEFT JOIN hoteles h ON v.FK_ID_Hotel = h.ID_Hotel
    WHERE (pa.FK_ID_PasajeroOnline = ? OR pa.ID_Pasajero = ?)
      AND DATE(v.FechaSalida) >= CURDATE()
    ORDER BY v.FechaSalida ASC
  `;

  db.query(query, [idPasajero, idPasajero], (err, results) => {
    if (err) {
      console.error('Error en consulta de itinerario:', err);
      return res.status(500).json({ error: err.message });
    }
    res.json(results);
  });
});

// ... (Aquí van el resto de tus otros endpoints como /api/registro, /api/login, /api/infoviaje/:idViaje, etc.)
app.get('/api/infoviaje/:idViaje', (req, res) => {
  const { idViaje } = req.params;

  const query = `
    SELECT 
      Informacion,
      Clima,
      Moneda,
      Recomendaciones
    FROM infoviajes
    WHERE FK_ID_Viaje = ?
  `;

  db.query(query, [idViaje], (err, results) => {
    if (err) {
      console.error('Error al obtener info del viaje:', err);
      return res.status(500).json({ error: err.message });
    }
    
    // Si no hay registro específico, devolvemos un objeto por defecto para que la app no rompa
    if (results.length === 0) {
      return res.json({
        Informacion: 'Información general disponible próximamente.',
        Clima: '24°C - Templado',
        Moneda: 'Peso Argentino (ARS)',
        Recomendaciones: 'Mantén tu documentación a mano.'
      });
    }

    res.json(results[0]);
  });
});

// ==========================================
// ENDPOINT: Dashboard para la App de Gerente
// ==========================================
app.get('/api/gerente/dashboard', (async (req, res) => {
  try {
    // 1. Ventas Totales y Margen Bruto (asumiendo un margen estimado del 30% sobre las ventas totales)
    const queryVentas = `
      SELECT 
        COALESCE(SUM(Monto1 + COALESCE(Monto2, 0)), 0) AS VentasTotales,
        COALESCE(SUM(Monto1 + COALESCE(Monto2, 0)) * 0.30, 0) AS MargenBrutoEstimado
      FROM pago
    `;

    // 2. Expedientes Abiertos (Viajes en estado programado o en_curso)
    const queryViajesAbiertos = `
      SELECT COUNT(*) AS ExpedientesAbiertos 
      FROM viajes 
      WHERE Estado IN ('programado', 'en_curso')
    `;

    // 3. Estado de Cobranza (Conteo de pasajes por su estado: pagado, reservado, cancelado)
    const queryCobranza = `
      SELECT Estado, COUNT(*) AS Cantidad
      FROM pasaje
      GROUP BY Estado
    `;

    // 4. Productividad por Agente / Establecimiento
    const queryProductividad = `
      SELECT 
        e.ID_Establecimiento,
        e.Nombre AS Sucursal,
        e.TitularACargo AS Agente,
        COALESCE(SUM(p.Monto1 + COALESCE(p.Monto2, 0)), 0) AS TotalVendido,
        COUNT(p.ID_Pago) AS CantidadOperaciones
      FROM establecimiento e
      LEFT JOIN pago p ON e.ID_Establecimiento = p.FK_ID_Establecimiento
      GROUP BY e.ID_Establecimiento, e.Nombre, e.TitularACargo
    `;

    // Ejecutamos las consultas en paralelo con promesas de mysql2 (asegúrate de usar mysql2 con .promise())
    db.query(queryVentas, (err, ventasRes) => {
      if (err) return res.status(500).json({ error: err.message });

      db.query(queryViajesAbiertos, (err, viajesRes) => {
        if (err) return res.status(500).json({ error: err.message });

        db.query(queryCobranza, (err, cobranzaRes) => {
          if (err) return res.status(500).json({ error: err.message });

          db.query(queryProductividad, (err, prodRes) => {
            if (err) return res.status(500).json({ error: err.message });

            // Devolvemos todo unificado en un solo objeto JSON para el frontend
            res.json({
              ventasGenerales: ventasRes[0],
              expedientesAbiertos: viajesRes[0].ExpedientesAbiertos,
              estadoCobranza: cobranzaRes,
              productividadAgentes: prodRes
            });
          });
        });
      });
    });

  } catch (error) {
    console.error('Error en el dashboard del gerente:', error);
    res.status(500).json({ error: 'Error interno del servidor' });
  }
}));

// Iniciar Servidor
app.listen(3000, () => {
  console.log('Servidor API corriendo en http://localhost:3000');
});
