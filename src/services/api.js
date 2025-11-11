import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost/Backend',
  headers: { 'Content-Type': 'application/json' }
});

// Registro de OFICINA
export async function registrarOficina({ nombre, apellidoP, apellidoM, usuario, password }) {
  const payload = {
    nombre,
    apellidoP, // también soportado por backend: apellido_paterno, apellidoPaterno
    apellidoM, // también soportado por backend: apellido_materno, apellidoMaterno
    tipo: 'OFICINA',
    usuario,
    password
  };
  const { data } = await api.post('/Registrar.php', payload);
  return data;
}

// Registro de MONITOR
export async function registrarMonitor({ nombre, apellidoP, apellidoM, usuario, password, numeroControl, telefono, carrera_id, semestre_id }) {
  const payload = {
    nombre,
    apellidoP,
    apellidoM,
    tipo: 'MONITOR',
    usuario,
    password,
    numeroControl,
    telefono,
    carrera_id,
    semestre_id
  };
  const { data } = await api.post('/Registrar.php', payload);
  return data;
}

export default api;
