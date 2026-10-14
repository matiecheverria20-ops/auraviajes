// mi-proyecto-app/services/notifications.js

export async function registrarNotificaciones() {
  console.log('Permisos de notificaciones simulados para Expo Go.');
  return true;
}

export async function programarNotificacionPrueba(titulo, mensaje, segundos = 5) {
  console.log('Notificación simulada:', titulo, mensaje);
}