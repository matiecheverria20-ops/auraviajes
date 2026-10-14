import React, { useState, useEffect } from 'react';
import { StyleSheet, Text, View, ScrollView, ActivityIndicator, SafeAreaView, Platform, StatusBar } from 'react-native';

export default function App() {
  const [loading, setLoading] = useState(true);
  const [data, setData] = useState({
    ventasTotales: 0,
    margenBruto: 0,
    expedientesAbiertos: 0,
    cobranzas: [],
    productividad: []
  });

  useEffect(() => {
    // Recuerda cambiar '192.168.X.X' por tu IP local obtenida con ipconfig
    fetch('http://192.168.2.109:3000/api/dashboard')
      .then((res) => res.json())
      .then((json) => {
        setData(json);
        setLoading(false);
      })
      .catch((err) => {
        console.error('Error al conectar con la API:', err);
        setLoading(false);
      });
  }, []);

  if (loading) {
    return (
      <View style={styles.loaderContainer}>
        <ActivityIndicator size="large" color="#007AFF" />
        <Text style={styles.loaderText}>Cargando Dashboard Gerencial...</Text>
      </View>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <ScrollView style={styles.container} contentContainerStyle={styles.contentContainer}>
        <Text style={styles.headerTitle}>Panel Gerencial</Text>
        <Text style={styles.headerSubtitle}>Control y métricas clave del sistema de viajes</Text>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Ventas Totales</Text>
          <Text style={styles.cardValue}>$ {Number(data.ventasTotales).toLocaleString()} ARS</Text>
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Margen Bruto Estimado</Text>
          <Text style={[styles.cardValue, { color: '#34C759' }]}>
            $ {Number(data.margenBruto).toLocaleString()} ARS
          </Text>
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Expedientes Abiertos (Viajes)</Text>
          <Text style={styles.cardValue}>{data.expedientesAbiertos}</Text>
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Estado de Cobranza (Pasajes)</Text>
          {data.cobranzas.map((item, index) => (
            <View key={index} style={styles.rowItem}>
              <Text style={styles.rowLabel}>• Estado: {item.Estado}</Text>
              <Text style={styles.rowValue}>{item.cantidad} pasajes</Text>
            </View>
          ))}
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Productividad por Establecimiento</Text>
          {data.productividad.length > 0 ? (
            data.productividad.map((item, index) => (
              <View key={index} style={styles.rowItem}>
                <Text style={styles.rowLabel}>{item.establecimiento}</Text>
                <Text style={styles.rowValue}>$ {Number(item.recaudacion).toLocaleString()} ARS</Text>
              </View>
            ))
          ) : (
            <Text style={styles.noDataText}>No hay registros de productividad aún.</Text>
          )}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { 
    flex: 1, 
    backgroundColor: '#f8f9fa',
    paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0 
  },
  container: { flex: 1, padding: 16 },
  contentContainer: { paddingBottom: 30 },
  loaderContainer: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#f8f9fa' },
  loaderText: { marginTop: 10, color: '#666', fontSize: 16 },
  headerTitle: { fontSize: 28, fontWeight: '700', color: '#1a1a1a', marginTop: 10 },
  headerSubtitle: { fontSize: 14, color: '#666', marginBottom: 20 },
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 20,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 6,
    elevation: 3,
  },
  cardTitle: { fontSize: 14, fontWeight: '600', color: '#888', textTransform: 'uppercase', marginBottom: 6 },
  cardValue: { fontSize: 24, fontWeight: 'bold', color: '#222' },
  rowItem: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 10, paddingVertical: 4, borderBottomWidth: 0.5, borderBottomColor: '#eee' },
  rowLabel: { fontSize: 15, color: '#444', textTransform: 'capitalize' },
  rowValue: { fontSize: 15, fontWeight: '600', color: '#007AFF' },
  noDataText: { fontSize: 14, color: '#999', fontStyle: 'italic', marginTop: 8 }
});