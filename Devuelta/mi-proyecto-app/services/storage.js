// mi-proyecto-app/services/storage.js
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_URL } from '../config';

const STORAGE_KEY_USER = '@usuario_logueado';
const STORAGE_KEY_ITINERARIO = '@itinerario_cliente';

export const loginUsuario = async (email, password) => {
  try {
    const response = await fetch(`${API_URL}/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Error en las credenciales');
    }

    const usuario = await response.json();
    await AsyncStorage.setItem(STORAGE_KEY_USER, JSON.stringify(usuario));
    return usuario;
  } catch (error) {
    console.error('Error en login:', error);
    throw error;
  }
};

export const cerrarSesion = async () => {
  await AsyncStorage.removeItem(STORAGE_KEY_USER);
  await AsyncStorage.removeItem(STORAGE_KEY_ITINERARIO);
};

export const obtenerItinerario = async (idPasajero) => {
  try {
    const response = await fetch(`${API_URL}/itinerario/${idPasajero}`);
    if (!response.ok) throw new Error('Error al obtener itinerario');
    
    const data = await response.json();
    
    // Guardar en caché para offline
    await AsyncStorage.setItem(`itinerario_${idPasajero}`, JSON.stringify(data));
    
    return { datos: data, fuente: 'online' }; // <-- Garantiza la estructura esperada por index.tsx
  } catch (error) {
    console.log('Modo offline activado para itinerario');
    const cachedData = await AsyncStorage.getItem(`itinerario_${idPasajero}`);
    return { 
      datos: cachedData ? JSON.parse(cachedData) : [], 
      fuente: 'offline' 
    };
  }
};

export const obtenerSesion = async () => {
  const user = await AsyncStorage.getItem(STORAGE_KEY_USER);
  return user ? JSON.parse(user) : null;
};
export const registrarUsuario = async (datosRegistro) => {
  try {
    const response = await fetch(`${API_URL}/registro`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(datosRegistro)
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Error al registrar usuario');
    }

    const usuario = await response.json();
    await AsyncStorage.setItem(STORAGE_KEY_USER, JSON.stringify(usuario));
    return usuario;
  } catch (error) {
    console.error('Error en registro:', error);
    throw error;
  }
};