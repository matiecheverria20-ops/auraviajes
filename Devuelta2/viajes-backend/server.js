const express = require('express');
const mysql = require('mysql2/promise');
const cors = require('cors');

const app = express();
app.use(cors());

const pool = mysql.createPool({
  host: '127.0.0.1',
  user: 'root',
  password: '',
  database: 'sistema_viajes',
});

app.get('/api/dashboard', async (req, res) => {
  try {
    // 1. Ventas Totales
    const [ventas] = await pool.query('SELECT SUM(Monto1 + COALESCE(Monto2, 0)) AS ventasTotales FROM pago');
    
    // 2. Margen Bruto (Simulado de manera estimada restando un costo operativo base del 40% o usando datos de proveedores)
    const ventasTotales = ventas[0].ventasTotales || 0;
    const margenBruto = ventasTotales * 0.60; // 60% de margen estimado para el ejercicio escolar

    // 3. Expedientes Abiertos (Viajes programados o en curso)
    const [expedientes] = await pool.query('SELECT COUNT(*) AS expedientesAbiertos FROM viajes WHERE Estado IN ("programado", "en_curso")');

    // 4. Estado de Cobranza / Pagos (Pasajes)
    const [cobranzas] = await pool.query('SELECT Estado, COUNT(*) as cantidad FROM pasaje GROUP BY Estado');

    // 5. Productividad por Agente / Establecimiento (Recaudación por sucursal)
    const [productividad] = await pool.query(`
      SELECT e.Nombre AS establecimiento, SUM(p.Monto1 + COALESCE(p.Monto2, 0)) AS recaudacion
      FROM pago p
      JOIN establecimiento e ON p.FK_ID_Establecimiento = e.ID_Establecimiento
      GROUP BY e.ID_Establecimiento
    `);

    res.json({
      ventasTotales,
      margenBruto,
      expedientesAbiertos: expedientes[0].expedientesAbiertos || 0,
      cobranzas, // Array con los estados (pagado, reservado, cancelado)
      productividad // Array con la recaudación por sucursal/agente
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ error: 'Error al consultar la base de datos' });
  }
});

app.listen(3000, () => {
  console.log('Servidor backend del Gerente corriendo en el puerto 3000');
});