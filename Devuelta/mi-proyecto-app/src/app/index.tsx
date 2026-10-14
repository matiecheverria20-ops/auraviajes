import { registrarNotificaciones, programarNotificacionPrueba } from '../../services/notifications';
import React, { useState, useEffect } from 'react';
import { 
  StyleSheet, Text, View, TextInput, TouchableOpacity, 
  ActivityIndicator, Alert, Linking, ScrollView 
} from 'react-native';
import * as Print from 'expo-print';
import * as Sharing from 'expo-sharing';
import { loginUsuario, registrarUsuario, obtenerItinerario, obtenerSesion, cerrarSesion } from '../../services/storage';
import { API_URL } from '../../config';

export default function AppCliente() {
  const [isRegistering, setIsRegistering] = useState(false);
  
  // Formulario
  const [email, setEmail] = useState('juan.perez@email.com');
  const [password, setPassword] = useState('hashdemo001');
  const [nombre, setNombre] = useState('');
  const [apellido, setApellido] = useState('');
  const [dni, setDni] = useState('');
  const [telefono, setTelefono] = useState('');

  // Estados de Usuario e Itinerario
  const [usuario, setUsuario] = useState<any>(null);
  const [itinerario, setItinerario] = useState<any[]>([]);
  const [notificaciones, setNotificaciones] = useState<any[]>([]);
  const [infoDestino, setInfoDestino] = useState<any>(null);
  const [fuenteData, setFuenteData] = useState('');
  const [loading, setLoading] = useState(false);
  const [tab, setTab] = useState('itinerario');

  useEffect(() => {
    registrarNotificaciones();
    comprobarSesion();
  }, []);

  const comprobarSesion = async () => {
    const sesion = await obtenerSesion();
    if (sesion) {
      setUsuario(sesion);
      cargarDatos(sesion.ID_PasajeroOnline);
    }
  };

  const handleLogin = async () => {
    if (!email || !password) return Alert.alert('Error', 'Ingresa correo y contraseña');
    setLoading(true);
    try {
      const user = await loginUsuario(email, password);
      setUsuario(user);
      await cargarDatos(user.ID_PasajeroOnline);
    } catch (err: any) {
      Alert.alert('Error', err.message || 'No se pudo iniciar sesión.');
    } finally {
      setLoading(false);
    }
  };

  const handleRegistro = async () => {
    if (!email || !password || !nombre || !dni) {
      return Alert.alert('Error', 'Completa los campos obligatorios (Nombre, DNI, Correo y Contraseña)');
    }
    setLoading(true);
    try {
      const newUser = await registrarUsuario({ nombre, apellido, dni, telefono, email, password });
      setUsuario(newUser);
      await cargarDatos(newUser.ID_PasajeroOnline);
      Alert.alert('¡Bienvenido!', 'Cuenta creada con éxito.');
    } catch (err: any) {
      Alert.alert('Error', err.message || 'No se pudo completar el registro.');
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = async () => {
    await cerrarSesion();
    setUsuario(null);
    setItinerario([]);
    setInfoDestino(null);
    setNotificaciones([]);
  };

  const cargarDatos = async (idPasajero: number) => {
    setLoading(true);
    
    // 1. Limpieza preventiva de estados antiguos
    setItinerario([]);
    setInfoDestino(null);
    setNotificaciones([]);

    // 2. Obtener Itinerario
    const res = await obtenerItinerario(idPasajero);
    const viajes = res.datos || [];
    setItinerario(viajes);
    setFuenteData(res.fuente);

    // 3. Cargar Información de Destino y Notificaciones SOLO si hay un viaje activo
    if (viajes.length > 0) {
      const proximoViaje = viajes[0];

      // Notificación local de prueba
      await programarNotificacionPrueba(
        `✈️ ¡Recordatorio de Viaje a ${proximoViaje.DestinoNombre}!`,
        `Tu pasaje ${proximoViaje.NroTicket} está confirmado. Revisa tu itinerario.`,
        5
      );

      // Cargar información útil del destino desde MySQL
      try {
        const respInfo = await fetch(`${API_URL}/infoviaje/${proximoViaje.ID_Viaje}`);
        if (respInfo.ok) {
          const infoData = await respInfo.json();
          setInfoDestino(infoData);
        }
      } catch (e) {
        console.log('Error obteniendo info de viaje desde MySQL');
      }

      // Cargar notificaciones del servidor
      try {
        const respNotif = await fetch(`${API_URL}/notificaciones/${idPasajero}`);
        if (respNotif.ok) {
          const notifs = await respNotif.json();
          setNotificaciones(notifs);
        }
      } catch (e) {
        setNotificaciones([]);
      }
    } else {
      // Si NO tiene viajes, aseguramos que la pantalla quede limpia
      setInfoDestino(null);
      setNotificaciones([]);
    }

    setLoading(false);
  };

  const generarPDF = async (viaje: any) => {
    if (!viaje || !viaje.NroTicket) {
      return Alert.alert('Aviso', 'No tienes un ticket válido para generar el PDF.');
    }

    const htmlContent = `
      <html>
        <head>
          <style>
            body { font-family: Arial, sans-serif; padding: 20px; color: #333; }
            .ticket { border: 2px dashed #0066CC; padding: 20px; border-radius: 10px; background: #fafafa; }
            .header { text-align: center; border-bottom: 2px solid #0066CC; padding-bottom: 10px; }
            .title { color: #0066CC; font-size: 24px; font-weight: bold; margin: 0; }
            .subtitle { color: #666; font-size: 14px; }
            .row { display: flex; justify-content: space-between; margin: 15px 0; }
            .label { font-size: 12px; color: #888; text-transform: uppercase; }
            .value { font-size: 16px; font-weight: bold; color: #1A2B4C; }
            .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #777; }
          </style>
        </head>
        <body>
          <div class="ticket">
            <div class="header">
              <h1 class="title">VOUCHER DE VIAJE</h1>
              <p class="subtitle">Devuelta Viajes & Turismo</p>
            </div>
            
            <div class="row">
              <div>
                <div class="label">Pasajero</div>
                <div class="value">${usuario?.Nombre || ''} ${usuario?.Apellido || ''}</div>
              </div>
              <div>
                <div class="label">DNI</div>
                <div class="value">${usuario?.DNI || ''}</div>
              </div>
            </div>

            <div class="row">
              <div>
                <div class="label">Ticket N°</div>
                <div class="value">${viaje.NroTicket}</div>
              </div>
              <div>
                <div class="label">Estado</div>
                <div class="value" style="color: green;">${(viaje.EstadoPasaje || 'CONFIRMADO').toUpperCase()}</div>
              </div>
            </div>

            <div class="row">
              <div>
                <div class="label">Destino</div>
                <div class="value">${viaje.DestinoNombre || 'Sin Destino'}</div>
              </div>
              <div>
                <div class="label">Fecha Salida</div>
                <div class="value">${new Date(viaje.FechaSalida || Date.now()).toLocaleString()}</div>
              </div>
            </div>

            <div class="row">
              <div>
                <div class="label">Hotel Asignado</div>
                <div class="value">${viaje.HotelNombre || 'No asignado'}</div>
              </div>
            </div>

            <div class="footer">
              <p>¡Gracias por viajar con nosotros!</p>
              <p>Soporte Tel: +54 9 370 521-6651</p>
            </div>
          </div>
        </body>
      </html>
    `;

    try {
      const { uri } = await Print.printToFileAsync({ html: htmlContent });
      await Sharing.shareAsync(uri);
    } catch (error) {
      Alert.alert('Error', 'No se pudo generar el archivo PDF.');
    }
  };

  // VISTA DE LOGIN Y REGISTRO
  if (!usuario) {
    return (
      <ScrollView contentContainerStyle={styles.containerCenter}>
        <Text style={styles.title}>Portal del Viajero</Text>
        <Text style={styles.subtitle}>
          {isRegistering ? 'Crea tu cuenta para gestionar tus viajes' : 'Ingresa con tu cuenta registrada'}
        </Text>
        
        {isRegistering && (
          <>
            <TextInput
              style={styles.input}
              placeholder="Nombre (*)"
              value={nombre}
              onChangeText={setNombre}
            />
            <TextInput
              style={styles.input}
              placeholder="Apellido"
              value={apellido}
              onChangeText={setApellido}
            />
            <TextInput
              style={styles.input}
              placeholder="DNI (*)"
              value={dni}
              onChangeText={setDni}
              keyboardType="numeric"
            />
            <TextInput
              style={styles.input}
              placeholder="Teléfono"
              value={telefono}
              onChangeText={setTelefono}
              keyboardType="phone-pad"
            />
          </>
        )}

        <TextInput
          style={styles.input}
          placeholder="Correo Electrónico (*)"
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
          keyboardType="email-address"
        />

        <TextInput
          style={styles.input}
          placeholder="Contraseña (*)"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
        />

        <TouchableOpacity 
          style={styles.btn} 
          onPress={isRegistering ? handleRegistro : handleLogin} 
          disabled={loading}
        >
          {loading ? (
            <ActivityIndicator color="#FFF" />
          ) : (
            <Text style={styles.btnText}>
              {isRegistering ? 'Crear Cuenta' : 'Iniciar Sesión'}
            </Text>
          )}
        </TouchableOpacity>

        <TouchableOpacity 
          style={styles.switchBtn} 
          onPress={() => setIsRegistering(!isRegistering)}
        >
          <Text style={styles.switchBtnText}>
            {isRegistering 
              ? '¿Ya tienes una cuenta? Inicia sesión aquí' 
              : '¿No tienes cuenta? Regístrate aquí'}
          </Text>
        </TouchableOpacity>
      </ScrollView>
    );
  }

  const viajeActual = itinerario[0] || null;

  return (
    <View style={styles.container}>
      {/* Cabecera */}
      <View style={styles.header}>
        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
          <View>
            <Text style={styles.headerTitle}>¡Hola, {usuario.Nombre}!</Text>
            <Text style={styles.headerSub}>DNI: {usuario.DNI}</Text>
          </View>
          <TouchableOpacity style={styles.btnLogout} onPress={handleLogout}>
            <Text style={styles.btnLogoutText}>Salir 🚪</Text>
          </TouchableOpacity>
        </View>
        
        <View style={[styles.badge, fuenteData === 'offline' ? styles.badgeOffline : styles.badgeOnline]}>
          <Text style={styles.badgeText}>
            {fuenteData === 'offline' ? '📡 Modo Offline (Datos Guardados)' : '🌐 Conectado en Tiempo Real'}
          </Text>
        </View>
      </View>

      {/* Navegación por Pestañas */}
      <View style={styles.navBar}>
        <TouchableOpacity 
          style={[styles.navBtn, tab === 'itinerario' && styles.navBtnActive]} 
          onPress={() => setTab('itinerario')}
        >
          <Text style={[styles.navText, tab === 'itinerario' && styles.navTextActive]}>🗺 Itinerario</Text>
        </TouchableOpacity>

        <TouchableOpacity 
          style={[styles.navBtn, tab === 'documentos' && styles.navBtnActive]} 
          onPress={() => setTab('documentos')}
        >
          <Text style={[styles.navText, tab === 'documentos' && styles.navTextActive]}>📄 Vouchers</Text>
        </TouchableOpacity>

        <TouchableOpacity 
          style={[styles.navBtn, tab === 'destino' && styles.navBtnActive]} 
          onPress={() => setTab('destino')}
        >
          <Text style={[styles.navText, tab === 'destino' && styles.navTextActive]}>🌴 Destino</Text>
        </TouchableOpacity>

        <TouchableOpacity 
          style={[styles.navBtn, tab === 'contacto' && styles.navBtnActive]} 
          onPress={() => setTab('contacto')}
        >
          <Text style={[styles.navText, tab === 'contacto' && styles.navTextActive]}>📞 Agencia</Text>
        </TouchableOpacity>
      </View>

      <ScrollView style={{ flex: 1 }}>
        {/* PESTAÑA ITINERARIO */}
        {tab === 'itinerario' && (
          <View>
            <Text style={styles.sectionTitle}>🔔 Notificaciones e Avisos</Text>
            {notificaciones.length > 0 ? (
              notificaciones.map((n, i) => (
                <View key={i} style={styles.notifCard}>
                  <Text style={styles.notifTitle}>⚠️ {n.Tipo}</Text>
                  <Text style={styles.notifBody}>{n.Mensaje}</Text>
                </View>
              ))
            ) : (
              <Text style={{ color: '#666', marginBottom: 15, fontStyle: 'italic' }}>Sin avisos pendientes.</Text>
            )}

            <Text style={styles.sectionTitle}>📍 Detalle del Viaje</Text>
            {itinerario.length > 0 ? (
              itinerario.map((item, index) => (
                <View key={index} style={styles.card}>
                  <Text style={styles.cardDestino}>📍 {item.DestinoNombre}</Text>
                  <Text style={styles.cardInfo}>🎟 Ticket: {item.NroTicket} ({item.EstadoPasaje})</Text>
                  <Text style={styles.cardInfo}>📅 Salida: {new Date(item.FechaSalida).toLocaleString()}</Text>
                  <Text style={styles.cardInfo}>🏁 Llegada: {new Date(item.FechaLlegada).toLocaleString()}</Text>
                  <Text style={styles.cardInfo}>🏨 Hotel: {item.HotelNombre || 'No asignado'}</Text>

                  {item.DestinoMapa && (
                    <TouchableOpacity 
                      style={styles.btnMapa} 
                      onPress={() => Linking.openURL(item.DestinoMapa)}
                    >
                      <Text style={styles.btnMapaText}>🗺 Ver Ubicación en el Mapa</Text>
                    </TouchableOpacity>
                  )}
                </View>
              ))
            ) : (
              <View style={styles.card}>
                <Text style={styles.cardInfo}>Aún no posees pasajes vigentes cargados a tu nombre.</Text>
              </View>
            )}
          </View>
        )}

        {/* PESTAÑA VOUCHERS */}
        {tab === 'documentos' && (
          <View>
            <Text style={styles.sectionTitle}>📄 Voucher Digital Real</Text>
            {viajeActual ? (
              <View style={styles.card}>
                <Text style={styles.cardDestino}>🎟 Boleto de Pasaje Confirmado</Text>
                <Text style={styles.cardInfo}>• N° de Ticket: {viajeActual.NroTicket}</Text>
                <Text style={styles.cardInfo}>• Destino: {viajeActual.DestinoNombre}</Text>
                <Text style={styles.cardInfo}>• Alojamiento: {viajeActual.HotelNombre || 'No asignado'}</Text>
                <Text style={styles.cardInfo}>• Estado: {(viajeActual.EstadoPasaje || 'PAGADO').toUpperCase()}</Text>
                
                <TouchableOpacity 
                  style={styles.btnDescarga} 
                  onPress={() => generarPDF(viajeActual)}
                >
                  <Text style={styles.btnDescargaText}>⬇ Descargar Voucher PDF Real</Text>
                </TouchableOpacity>
              </View>
            ) : (
              <View style={styles.card}>
                <Text style={styles.cardInfo}>No posees tickets ni vouchers disponibles para emitir en este momento.</Text>
              </View>
            )}
          </View>
        )}

        {/* PESTAÑA DESTINO (100% DINÁMICA DE MYSQL) */}
        {tab === 'destino' && (
          <View>
            <Text style={styles.sectionTitle}>💡 Información Útil del Destino</Text>
            {viajeActual && infoDestino ? (
              <View style={styles.card}>
                <Text style={styles.cardDestino}>📍 {viajeActual.DestinoNombre}</Text>
                <Text style={styles.cardInfo}>ℹ️ {infoDestino.Informacion}</Text>
                <Text style={[styles.cardInfo, { marginTop: 8 }]}>☀️ Clima: {infoDestino.Clima}</Text>
                <Text style={styles.cardInfo}>💵 Moneda: {infoDestino.Moneda}</Text>
                <Text style={styles.cardInfo}>🎒 Recomendaciones: {infoDestino.Recomendaciones}</Text>
              </View>
            ) : (
              <View style={styles.card}>
                <Text style={styles.cardInfo}>No tienes pasajes vigentes para consultar la información del destino.</Text>
              </View>
            )}
          </View>
        )}

        {/* PESTAÑA CONTACTO */}
        {tab === 'contacto' && (
          <View>
            <Text style={styles.sectionTitle}>📞 Contacto Rápido con la Agencia</Text>
            <View style={styles.card}>
              <Text style={styles.cardDestino}>Devuelta Viajes & Turismo</Text>
              <Text style={styles.cardInfo}>📱 Atención al Cliente 24/7</Text>
              <Text style={styles.cardInfo}>📞 Teléfono: +54 9 370 521-6651</Text>
              
              <TouchableOpacity 
                style={[styles.btnAction, { backgroundColor: '#25D366', marginTop: 15 }]} 
                onPress={() => Linking.openURL('https://wa.me/5493705216651')}
              >
                <Text style={styles.btnActionText}>💬 Enviar mensaje por WhatsApp</Text>
              </TouchableOpacity>

              <TouchableOpacity 
                style={[styles.btnAction, { backgroundColor: '#0066CC', marginTop: 10 }]} 
                onPress={() => Linking.openURL('tel:+5493705216651')}
              >
                <Text style={styles.btnActionText}>📞 Llamar Directamente</Text>
              </TouchableOpacity>
            </View>
          </View>
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  containerCenter: { flexGrow: 1, justifyContent: 'center', padding: 20, backgroundColor: '#F4F6F9' },
  container: { flex: 1, padding: 15, backgroundColor: '#F4F6F9', paddingTop: 45 },
  title: { fontSize: 26, fontWeight: 'bold', color: '#1A2B4C', textAlign: 'center', marginBottom: 5 },
  subtitle: { fontSize: 14, color: '#666', textAlign: 'center', marginBottom: 25 },
  input: { backgroundColor: '#FFF', padding: 14, borderRadius: 8, borderWidth: 1, borderColor: '#DDD', marginBottom: 15 },
  btn: { backgroundColor: '#0066CC', padding: 15, borderRadius: 8, alignItems: 'center' },
  btnText: { color: '#FFF', fontWeight: 'bold', fontSize: 16 },
  switchBtn: { marginTop: 15, alignItems: 'center' },
  switchBtnText: { color: '#0066CC', fontSize: 13, fontWeight: 'bold' },
  header: { backgroundColor: '#1A2B4C', padding: 16, borderRadius: 12, marginBottom: 12 },
  headerTitle: { fontSize: 20, fontWeight: 'bold', color: '#FFF' },
  headerSub: { fontSize: 12, color: '#CBD5E1', marginTop: 2 },
  btnLogout: { backgroundColor: '#DC2626', paddingVertical: 6, paddingHorizontal: 12, borderRadius: 6 },
  btnLogoutText: { color: '#FFF', fontSize: 12, fontWeight: 'bold' },
  badge: { marginTop: 8, paddingVertical: 4, paddingHorizontal: 10, borderRadius: 20, alignSelf: 'flex-start' },
  badgeOnline: { backgroundColor: '#2E7D32' },
  badgeOffline: { backgroundColor: '#D84315' },
  badgeText: { color: '#FFF', fontSize: 10, fontWeight: 'bold' },
  navBar: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 15 },
  navBtn: { flex: 1, paddingVertical: 10, backgroundColor: '#E2E8F0', borderRadius: 8, marginHorizontal: 2, alignItems: 'center' },
  navBtnActive: { backgroundColor: '#0066CC' },
  navText: { fontSize: 11, fontWeight: 'bold', color: '#475569' },
  navTextActive: { color: '#FFF' },
  sectionTitle: { fontSize: 16, fontWeight: 'bold', color: '#333', marginBottom: 10, marginTop: 5 },
  card: { backgroundColor: '#FFF', padding: 16, borderRadius: 10, marginBottom: 12, elevation: 2 },
  cardDestino: { fontSize: 17, fontWeight: 'bold', color: '#0066CC', marginBottom: 6 },
  cardInfo: { fontSize: 13, color: '#444', marginBottom: 4 },
  notifCard: { backgroundColor: '#FFF3CD', padding: 12, borderRadius: 8, marginBottom: 10, borderWidth: 1, borderColor: '#FFEEBA' },
  notifTitle: { fontSize: 13, fontWeight: 'bold', color: '#856404' },
  notifBody: { fontSize: 12, color: '#856404', marginTop: 2 },
  btnMapa: { marginTop: 12, backgroundColor: '#E8F5E9', padding: 10, borderRadius: 6, alignItems: 'center' },
  btnMapaText: { color: '#2E7D32', fontWeight: 'bold', fontSize: 12 },
  btnDescarga: { marginTop: 12, backgroundColor: '#0066CC', padding: 10, borderRadius: 6, alignItems: 'center' },
  btnDescargaText: { color: '#FFF', fontWeight: 'bold', fontSize: 12 },
  btnAction: { padding: 12, borderRadius: 8, alignItems: 'center' },
  btnActionText: { color: '#FFF', fontWeight: 'bold', fontSize: 13 }
});