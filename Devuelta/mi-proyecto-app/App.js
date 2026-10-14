// mi-proyecto-app/app/index.js
import React, { useState, useEffect } from 'react';
import { StyleSheet, Text, View, TextInput, TouchableOpacity, FlatList, ActivityIndicator, Alert, Linking } from 'react-native';
import { loginUsuario, obtenerItinerario, obtenerSesion } from '../services/storage';

export default function AppCliente() {
  const [email, setEmail] = useState(''); // Email de prueba del SQL
  const [usuario, setUsuario] = useState(null);
  const [itinerario, setItinerario] = useState([]);
  const [fuenteData, setFuenteData] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    comprobarSesion();
  }, []);

  const comprobarSesion = async () => {
    const sesion = await obtenerSesion();
    if (sesion) {
      setUsuario(sesion);
      cargarViajes(sesion.ID_PasajeroOnline);
    }
  };

  const handleLogin = async () => {
    if (!email) return Alert.alert('Error', 'Ingresa un correo');
    setLoading(true);
    try {
      const user = await loginUsuario(email);
      setUsuario(user);
      await cargarViajes(user.ID_PasajeroOnline);
    } catch (err) {
      Alert.alert('Error', 'No se encontró el usuario en la base de datos.');
    } finally {
      setLoading(false);
    }
  };

  const cargarViajes = async (idPasajero) => {
    setLoading(true);
    const res = await obtenerItinerario(idPasajero);
    setItinerario(res.datos);
    setFuenteData(res.fuente);
    setLoading(false);
  };

  if (!usuario) {
    return (
      <View style={styles.containerCenter}>
        <Text style={styles.title}>Portal del Viajero</Text>
        <Text style={styles.subtitle}>Ingresa con tu correo registrado</Text>
        
        <TextInput
          style={styles.input}
          placeholder="Ej: juan.perez@email.com"
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
        />

        <TouchableOpacity style={styles.btn} onPress={handleLogin} disabled={loading}>
          {loading ? <ActivityIndicator color="#FFF" /> : <Text style={styles.btnText}>Iniciar Sesión</Text>}
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Cabecera */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>¡Hola, {usuario.Nombre}!</Text>
        <Text style={styles.headerSub}>DNI: {usuario.DNI} | Tel: {usuario.Telefono}</Text>
        
        {/* Indicador de Estado Offline/Online */}
        <View style={[styles.badge, fuenteData === 'offline' ? styles.badgeOffline : styles.badgeOnline]}>
          <Text style={styles.badgeText}>
            {fuenteData === 'offline' ? '📡 Modo Offline (Datos Guardados)' : '🌐 Conectado en Tiempo Real'}
          </Text>
        </View>
      </View>

      {/* Lista de Viajes / Itinerario */}
      <Text style={styles.sectionTitle}>Tus Viajes e Itinerarios</Text>

      {loading ? (
        <ActivityIndicator size="large" color="#0066CC" style={{ marginTop: 20 }} />
      ) : (
        <FlatList
          data={itinerario}
          keyExtractor={(item, index) => index.toString()}
          renderItem={({ item }) => (
            <View style={styles.card}>
              <Text style={styles.cardDestino}>📍 {item.DestinoNombre}</Text>
              <Text style={styles.cardInfo}>🎟 Ticket: {item.NroTicket} ({item.EstadoPasaje})</Text>
              <Text style={styles.cardInfo}>📅 Salida: {new Date(item.FechaSalida).toLocaleString()}</Text>
              <Text style={styles.cardInfo}>🏨 Hotel: {item.HotelNombre || 'No asignado'}</Text>
              
              {item.NombreEvento && (
                <View style={styles.eventBox}>
                  <Text style={styles.eventText}>🎉 Evento Incluido: {item.NombreEvento}</Text>
                </View>
              )}

              {/* Botón Ver Mapa del Destino */}
              {item.DestinoMapa && (
                <TouchableOpacity 
                  style={styles.btnMapa} 
                  onPress={() => Linking.openURL(item.DestinoMapa)}
                >
                  <Text style={styles.btnMapaText}>🗺 Ver Ubicación del Destino</Text>
                </TouchableOpacity>
              )}
            </View>
          )}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  containerCenter: { flex: 1, justifyContent: 'center', padding: 20, backgroundColor: '#F4F6F9' },
  container: { flex: 1, padding: 20, backgroundColor: '#F4F6F9', paddingTop: 50 },
  title: { fontSize: 26, fontWeight: 'bold', color: '#1A2B4C', textAlign: 'center', marginBottom: 5 },
  subtitle: { fontSize: 14, color: '#666', textAlign: 'center', marginBottom: 25 },
  input: { backgroundColor: '#FFF', padding: 14, borderRadius: 8, borderWidth: 1, borderColor: '#DDD', marginBottom: 15 },
  btn: { backgroundColor: '#0066CC', padding: 15, borderRadius: 8, alignItems: 'center' },
  btnText: { color: '#FFF', fontWeight: 'bold', fontSize: 16 },
  header: { backgroundColor: '#1A2B4C', padding: 20, borderRadius: 12, marginBottom: 20 },
  headerTitle: { fontSize: 22, fontWeight: 'bold', color: '#FFF' },
  headerSub: { fontSize: 13, color: '#CBD5E1', marginTop: 4 },
  badge: { marginTop: 12, paddingVertical: 4, paddingHorizontal: 10, borderRadius: 20, alignSelf: 'flex-start' },
  badgeOnline: { backgroundColor: '#2E7D32' },
  badgeOffline: { backgroundColor: '#D84315' },
  badgeText: { color: '#FFF', fontSize: 11, fontWeight: 'bold' },
  sectionTitle: { fontSize: 18, fontWeight: 'bold', color: '#333', marginBottom: 12 },
  card: { backgroundColor: '#FFF', padding: 16, borderRadius: 10, marginBottom: 12, elevation: 2 },
  cardDestino: { fontSize: 18, fontWeight: 'bold', color: '#0066CC', marginBottom: 6 },
  cardInfo: { fontSize: 14, color: '#444', marginBottom: 4 },
  eventBox: { marginTop: 8, padding: 8, backgroundColor: '#E3F2FD', borderRadius: 6 },
  eventText: { color: '#0277BD', fontSize: 13, fontWeight: '600' },
  btnMapa: { marginTop: 12, backgroundColor: '#E8F5E9', padding: 10, borderRadius: 6, alignItems: 'center' },
  btnMapaText: { color: '#2E7D32', fontWeight: 'bold', fontSize: 13 }
});