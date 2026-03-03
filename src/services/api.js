// src/services/api.js
import axios from 'axios';
// Servicio simple para consumir el backend PHP de Clubs y Alumnos

const CLUBS_BASE = '/api/clubs.php';
const ALUMNOS_BASE = '/api/Alumnos.php';
const CARRERAS_BASE = '/api/carreras.php';
const ASISTENCIAS_BASE = '/api/asistencias.php';
const USUARIOS_BASE = '/api/Usuarios.php';
const UPLOAD_BASE = '/api/upload.php';
const ALUMNOS_PENDIENTES_BASE = '/api/AlumnosPendientes.php';

async function toJson(res) {
  const text = await res.text();
  try { return JSON.parse(text); } catch { return { raw: text }; }
}

// Clubs
export async function getClubs() {
  const res = await fetch(CLUBS_BASE, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.error || 'Error al obtener clubs');
  return data.data || [];
}

export async function createClub(payload) {
  const res = await fetch(CLUBS_BASE, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error((data && (data.message || data.error)) || 'Error al crear club');
  return data.data;
}

export async function updateClub(id, payload) {
  const url = `${CLUBS_BASE}?id=${encodeURIComponent(id)}`;
  const res = await fetch(url, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error((data && (data.message || data.error)) || 'Error al actualizar club');
  return data.data;
}

export async function deleteClub(id) {
  const url = `${CLUBS_BASE}?id=${encodeURIComponent(id)}`;
  const res = await fetch(url, { method: 'DELETE' });
  const data = await toJson(res);
  if (!res.ok) throw new Error((data && (data.message || data.error)) || 'Error al eliminar club');
  return data;
}

// Carreras
export async function getCarreras() {
  const res = await fetch(CARRERAS_BASE, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) throw new Error((data && (data.message || data.error)) || 'Error al obtener carreras');
  return data.data || [];
}

export async function createCarrera(payload) {
  const res = await fetch(CARRERAS_BASE, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error((data && (data.message || data.error)) || 'Error al crear carrera');
  return data.data;
}

export async function updateCarrera(id, payload) {
  const url = `${CARRERAS_BASE}?id=${encodeURIComponent(id)}`;
  const res = await fetch(url, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error((data && (data.message || data.error)) || 'Error al actualizar carrera');
  return data.data;
}

export async function deleteCarrera(id) {
  const url = `${CARRERAS_BASE}?id=${encodeURIComponent(id)}`;
  const res = await fetch(url, { method: 'DELETE' });
  const data = await toJson(res);
  if (!res.ok) throw new Error((data && (data.message || data.error)) || 'Error al eliminar carrera');
  return data;
}

// Asistencias
export async function getAsistenciasPorClub(clubId) {
  const url = `${ASISTENCIAS_BASE}?club_id=${encodeURIComponent(clubId)}`;
  const res = await fetch(url, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al obtener asistencias');
  return data.data || { fechas: [], asistencias: {}, alumnos: [] };
}


// Reinscripciones (batch)
export async function batchReinscripciones(payload) {
  const res = await fetch('/api/reinscripciones.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || data?.error || 'Error en reinscripciones');
  return data;
}

export async function crearFechaAsistencias({ club_id, fecha, registros }) {
  const res = await fetch(ASISTENCIAS_BASE, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ club_id, fecha, registros })
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al crear fecha/asistencias');
  return data;
}

export async function actualizarAsistencia({ alumno_id, fecha, presente }) {
  const res = await fetch(ASISTENCIAS_BASE, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ alumno_id, fecha, presente })
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al actualizar asistencia');
  return data;
}

// Usuarios
export async function getUsuarios() {
  const res = await fetch(USUARIOS_BASE, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al obtener usuarios');
  return data.data || [];
}

export async function updateUsuario(id, payload) {
  const url = `${USUARIOS_BASE}?id=${encodeURIComponent(id)}`;
  const res = await fetch(url, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al actualizar usuario');
  return data;
}

export async function deleteUsuario(id) {
  const url = `${USUARIOS_BASE}?id=${encodeURIComponent(id)}`;
  const res = await fetch(url, { method: 'DELETE' });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al eliminar usuario');
  return data;
}

export async function uploadFoto(file) {
  const form = new FormData();
  form.append('foto', file);
  const res = await fetch(UPLOAD_BASE, { method: 'POST', body: form });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al subir imagen');
  return data; // { status, file, filename }
}

// Monitores por Club
export async function getMonitoresPorClub(clubId) {
  const url = `/api/getMonitores.php?club_id=${encodeURIComponent(clubId)}`;
  const res = await fetch(url, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) {
    console.error('Error fetching monitores:', res.status, data);
    return { club_id: clubId, monitores: [] };
  }
  return data.data || { club_id: clubId, monitores: [] };
}

// Obtener todos los monitores con sus clubs asignados
export async function getAllMonitoresWithClubs() {
  const res = await fetch(`/api/Usuarios.php`, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) {
    console.error('Error fetching all usuarios:', res.status, data);
    return [];
  }
  // Filtrar solo monitores que tengan club asignado
  return (data.data || []).filter(u => u.tipo === 'MONITOR' && u.club_asignado);
}

// Asignar monitor a un club
export async function asignarMonitorAClub(monitorId, clubId) {
  const url = `/api/asignarMonitor.php`;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ monitor_id: monitorId, club_id: clubId })
  });
  const data = await toJson(res);
  if (!res.ok) {
    console.error('Error asigning monitor:', res.status, data);
    throw new Error(data?.message || 'Error al asignar monitor');
  }
  return data.data;
}

// Alumnos
export async function getAlumnos() {
  const res = await fetch(ALUMNOS_BASE, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) throw new Error((data && (data.message || data.error)) || 'Error al obtener alumnos');
  return data.data || [];
}

export async function createAlumno(payload) {
  const res = await fetch(ALUMNOS_BASE, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await toJson(res);
  if (!res.ok) {
    const msg = data?.message || data?.error || (Array.isArray(data?.details) ? data.details.join(', ') : 'Error al crear alumno');
    throw new Error(msg);
  }
  return data.data;
}

export async function updateAlumno(id, payload) {
  const url = `${ALUMNOS_BASE}?id=${encodeURIComponent(id)}`;
  const res = await fetch(url, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await toJson(res);
  if (!res.ok) {
    const msg = data?.message || data?.error || (Array.isArray(data?.details) ? data.details.join(', ') : 'Error al actualizar alumno');
    throw new Error(msg);
  }
  return data.data;
}

export async function deleteAlumno(id) {
  const url = `${ALUMNOS_BASE}?id=${encodeURIComponent(id)}`;
  const res = await fetch(url, { method: 'DELETE' });
  const data = await toJson(res);
  if (!res.ok) {
    const msg = data?.message || data?.error || 'Error al eliminar alumno';
    throw new Error(msg);
  }
  return data;
}

// Alumnos Pendientes
export async function getAlumnosPendientes() {
  try {
    const response = await axios.get(ALUMNOS_PENDIENTES_BASE);
    return response.data.data || [];
  } catch (e) {
    const errorMsg = e.response?.data?.error || e.message || 'Error al obtener alumnos pendientes';
    throw new Error(errorMsg);
  }
}

export async function saveAlumnosPendientes(students) {
  const res = await fetch(ALUMNOS_PENDIENTES_BASE, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(students),
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || data?.error || 'Error al guardar alumnos pendientes');
  return data;
}

export async function updateEstatusPendiente(id, payload) {
  const url = `${ALUMNOS_PENDIENTES_BASE}?id=${encodeURIComponent(id)}`;
  const res = await fetch(url, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || data?.error || 'Error al actualizar estado');
  return data;
}

export async function deleteAlumnoPendiente(id) {
  const url = `${ALUMNOS_PENDIENTES_BASE}?id=${encodeURIComponent(id)}`;
  const res = await fetch(url, { method: 'DELETE' });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || data?.error || 'Error al eliminar registro');
  return data;
}

// Auditoría
const AUDITORIA_BASE = '/api/auditoria.php';

export async function getAuditoria() {
  const res = await fetch(AUDITORIA_BASE, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al obtener auditoría');
  return data.data || [];
}

export async function registrarAuditoria({ id_usuario, accion, descripcion }) {
  const res = await fetch(AUDITORIA_BASE, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id_usuario, accion, descripcion })
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al registrar auditoría');
  return data.data;
}
// Evaluación
export async function saveEvaluacion(payload) {
  const res = await fetch('/api/evaluacion.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al guardar evaluación');
  return data;
}

export async function getEvaluacion(params) {
  const q = new URLSearchParams(params).toString();
  const res = await fetch('/api/evaluacion.php?' + q);
  const data = await toJson(res);
  if (data.status === 'error') throw new Error(data.message || 'Error en el servidor');
  return data.data; // object or null
}

export async function getEvaluatedStudents(clubName) {
  const q = new URLSearchParams({ type: 'list', nombre_club: clubName }).toString();
  const res = await fetch('/api/evaluacion.php?' + q);
  const data = await toJson(res);
  if (data.status === 'error') throw new Error(data.message || 'Error en el servidor');
  return data.data; // array
}

// Asignación de Cargos
const ASIGNACION_CARGOS_BASE = '/api/asignacion_cargos.php';
const CONFIG_BASE = '/api/configuracion.php';
const FIRMAS_BASE = '/api/obtener_firmas.php';

export async function getAsignacionCargos() {
  const res = await fetch(ASIGNACION_CARGOS_BASE, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al obtener asignaciones de cargos');
  return data.data || [];
}

export async function asignarCargo(cargo, idUsuario, tituloCargo = null) {
  const payload = { cargo, id_usuario: idUsuario };
  if (tituloCargo !== null) payload.titulo_cargo = tituloCargo;

  const res = await fetch(ASIGNACION_CARGOS_BASE, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al asignar cargo');
  return data;
}

export async function getConfig(clave) {
  const url = clave ? `${CONFIG_BASE}?clave=${encodeURIComponent(clave)}` : CONFIG_BASE;
  const res = await fetch(url, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al obtener configuración');
  return data.data;
}

export async function saveConfig(clave, valor) {
  const res = await fetch(CONFIG_BASE, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ clave, valor })
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al guardar configuración');
  return data;
}

export async function getFirmas() {
  const res = await fetch(FIRMAS_BASE, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al obtener firmas');
  return data.data || {};
}
