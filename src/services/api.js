// src/services/api.js
// Servicio simple para consumir el backend PHP de Clubs y Alumnos

const CLUBS_BASE = 'http://localhost/Backend/clubs.php';
const ALUMNOS_BASE = 'http://localhost/Backend/Alumnos.php';

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
