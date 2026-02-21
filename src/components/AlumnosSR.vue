<template>
  <div>
    <h3>Alumnos sin registrar (formularios)</h3>

    <div class="d-flex justify-content-between align-items-center my-3">
      <div>
        <button class="btn btn-info me-2" @click="triggerCSV">Importar CSV (Local)</button>
        <button class="btn btn-primary me-2" @click="fetchFromGoogleSheets">Jalar desde Google Sheets (En línea)</button>
        <input type="file" ref="csvInput" style="display: none" accept=".csv" @change="handleCSVUpload" />
      </div>
      <div>
        <small class="text-muted">Los registros muestran 3 opciones de club propuestas.</small>
      </div>
    </div>

    <div v-if="!unregistered || unregistered.length === 0" class="text-muted">No hay alumnos sin registrar.</div>

    <table v-if="unregistered && unregistered.length" class="table table-bordered table-striped align-middle">
      <thead class="table-primary">
        <tr>
          <th>Nombre</th>
          <th>No. Control</th>
          <th>Teléfono</th>
          <th>Estado</th>
          <th>Opciones de club</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(u, idx) in unregistered" :key="u.id || (u.numeroControl || '') + idx">
          <td>{{ u.nombre }} {{ u.apellidoP }} {{ u.apellidoM }}</td>
          <td>{{ u.numeroControl }}</td>
          <td>{{ u.telefono }}</td>
          <td>
            <span :class="['badge', u.estado === 'APROBADO' ? 'bg-success' : u.estado === 'RECHAZADO' ? 'bg-danger' : 'bg-warning']">
              {{ u.estado || 'PENDIENTE' }}
            </span>
          </td>
          <td>
            <div class="d-flex gap-2">
              <button v-for="(opt, oIndex) in getOpciones(u)" :key="oIndex" class="btn btn-outline-secondary btn-sm" disabled>{{ opt }}</button>
            </div>
          </td>
          <td>
            <div class="d-flex gap-2" v-if="u.estado === 'PENDIENTE' || !u.estado">
              <button v-for="(opt, oIndex) in getOpciones(u)" :key="'btn'+oIndex" 
                class="btn btn-success btn-sm" 
                @click="assignToClub(u, opt)">
                Asignar {{ oIndex + 1 }}
              </button>
              <button class="btn btn-danger btn-sm" @click="rejectStudent(u)">Rechazar</button>
              <button class="btn btn-sm btn-outline-danger" @click="deleteRecord(u, idx)">Eliminar</button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import { getAlumnosPendientes, saveAlumnosPendientes, updateEstatusPendiente, deleteAlumnoPendiente } from '../services/api';
import axios from 'axios';

export default {
  name: 'AlumnosSR',
  props: ['clubs', 'carreras'],
  data() {
    return {
      unregistered: [],
    };
  },
  async mounted() {
    await this.loadUnregistered();
  },
  methods: {
    safeNotify(type, msg) {
      if (type === 'error') {
        if (this.$parent && typeof this.$parent.showError === 'function') this.$parent.showError(msg);
        else if (this.$root && typeof this.$root.showError === 'function') this.$root.showError(msg);
        else console.error(msg);
      } else {
        if (this.$parent && typeof this.$parent.showToast === 'function') this.$parent.showToast(msg);
        else if (this.$root && typeof this.$root.showToast === 'function') this.$root.showToast(msg);
        else console.log(msg);
      }
    },
    async loadUnregistered() {
      try {
        const data = await getAlumnosPendientes();
        this.unregistered = Array.isArray(data) ? data : [];
      } catch (e) {
        console.error(e);
        this.safeNotify('error', 'Error al cargar alumnos pendientes');
      }
    },
    getOpciones(u) {
      if (u.opciones && Array.isArray(u.opciones)) return u.opciones;
      if (u.opciones_nombres) return u.opciones_nombres.split(',');
      return [];
    },
    async fetchFromGoogleSheets() {
      const gSheetUrl = 'https://docs.google.com/spreadsheets/d/1hvi9LM57rV-pa1iP-ynXGMTZETIo_Ug-ElknJGxnO0k/export?format=csv';
      const proxyUrl = `/api/AlumnosPendientes.php?proxy_url=${encodeURIComponent(gSheetUrl)}`;
      try {
        const response = await axios.get(proxyUrl);
        await this.parseGoogleFormsCSV(response.data, 'Google Sheets (Online)');
      } catch (e) {
        this.safeNotify('error', 'Error al conectar con Google Sheets vía Proxy');
        console.error(e);
      }
    },
    triggerCSV() {
      this.$refs.csvInput.click();
    },
    handleCSVUpload(event) {
      const file = event.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = async (e) => {
        await this.parseGoogleFormsCSV(e.target.result);
      };
      reader.readAsText(file);
      event.target.value = '';
    },
    async parseGoogleFormsCSV(text, source = 'CSV Local') {
      const lines = text.split(/\r?\n/).filter(line => line.trim() !== '');
      if (lines.length < 2) return;

      const parseCSVLine = (row) => {
        const result = [];
        let cur = '';
        let inQ = false;
        for (let i = 0; i < row.length; i++) {
          const c = row[i];
          if (c === '"') inQ = !inQ;
          else if (c === ',' && !inQ) {
            result.push(cur.trim().replace(/^"|"$/g, ''));
            cur = '';
          } else cur += c;
        }
        result.push(cur.trim().replace(/^"|"$/g, ''));
        return result;
      };

      const headers = parseCSVLine(lines[0]).map(h => h.toLowerCase());
      const dataRows = lines.slice(1);

      const findIndex = (keywords) => {
        return headers.findIndex(h => keywords.some(k => h.includes(k.toLowerCase())));
      };

      const idxNombre = findIndex(['nombre', 'completo']);
      const idxTel = findIndex(['telefono', 'telefónico', 'telefonico']);
      const idxControl = findIndex(['control']);
      const idxSemestre = findIndex(['semestre']);
      const idxCarrera = findIndex(['carrera']);
      const idxOpt1 = findIndex(['primera opcion', 'opcion 1', 'opción 1']);
      const idxOpt2 = findIndex(['segunda opcion', 'opcion 2', 'opción 2']);
      const idxOpt3 = findIndex(['tercera opcion', 'opcion 3', 'opción 3']);

      const newRegistros = [];
      dataRows.forEach(row => {
        const cleanCells = parseCSVLine(row);
        if (cleanCells.length < 2) return;

        const fullNombre = cleanCells[idxNombre] || '';
        const { nombre, apP, apM } = this.splitNombre(fullNombre);
        
        const carName = cleanCells[idxCarrera] || '';
        const carObj = Array.isArray(this.carreras) ? this.carreras.find(c => c.nombre.toLowerCase() === carName.toLowerCase()) : null;

        newRegistros.push({
          nombre, apellidoP: apP, apellidoM: apM,
          numeroControl: (cleanCells[idxControl] || '').substring(0, 8),
          telefono: (cleanCells[idxTel] || '').substring(0, 10),
          carrera_id: carObj ? carObj.id : null,
          semestre_id: cleanCells[idxSemestre] ? parseInt(cleanCells[idxSemestre]) || null : null,
          opciones: [
            cleanCells[idxOpt1], 
            cleanCells[idxOpt2], 
            cleanCells[idxOpt3]
          ].filter(Boolean),
          estado: 'PENDIENTE'
        });
      });

      try {
        await saveAlumnosPendientes(newRegistros);
        await this.loadUnregistered();
        this.$emit('log', { usuario: 'Usuario Oficina', accion: 'Importar', tipo: 'csv', descripcion: `Importados ${newRegistros.length} registros desde ${source}` });
        this.safeNotify('toast', `Importados ${newRegistros.length} registros`);
      } catch (e) {
        this.safeNotify('error', 'Error al guardar registros importados');
      }
    },
    splitNombre(full) {
      const parts = full.trim().split(/\s+/);
      let nombre = '', apP = '', apM = '';
      if (parts.length >= 4) {
        nombre = parts.slice(0, parts.length - 2).join(' ');
        apP = parts[parts.length - 2];
        apM = parts[parts.length - 1];
      } else if (parts.length === 3) {
        nombre = parts[0]; apP = parts[1]; apM = parts[2];
      } else if (parts.length === 2) {
        nombre = parts[0]; apP = parts[1];
      } else {
        nombre = full;
      }
      return { nombre, apP, apM };
    },
    async assignToClub(alumno, clubName) {
      if (!clubName) return this.safeNotify('error', 'Club inválido');
      try {
        const clubObj = Array.isArray(this.clubs) ? this.clubs.find(c => c.nombre === clubName) : null;
        if (!clubObj || !clubObj.id) throw new Error('No se encontró el ID del club ' + clubName);
        
        await updateEstatusPendiente(alumno.id, { 
          estado: 'APROBADO', 
          club_solicitado_id: clubObj.id 
        });

        // Notificar al padre para log y actualización local
        // El padre espera { alumnoIndex: objetoAlumno, clubNombre: string }
        this.$emit('assign-alumno', { alumnoIndex: alumno, clubNombre: clubName });
        
        // Pedir al padre que recargue la lista de alumnos registrados (ya que el trigger la actualizó)
        this.$emit('request-reload-alumnos');
        
        await this.loadUnregistered();
        this.safeNotify('toast', `Alumno aprobado y registrado en ${clubName}`);
      } catch (e) {
        this.safeNotify('error', e.message || 'Error al asignar alumno');
      }
    },
    async rejectStudent(alumno) {
      try {
        await updateEstatusPendiente(alumno.id, { estado: 'RECHAZADO' });
        await this.loadUnregistered();
        this.safeNotify('toast', `Alumno marcado como RECHAZADO`);
      } catch (e) {
        this.safeNotify('error', 'Error al rechazar alumno');
      }
    },
    async deleteRecord(alumno, index) {
      if (!confirm('¿Seguro?')) return;
      try {
        await deleteAlumnoPendiente(alumno.id);
        this.unregistered.splice(index, 1);
        this.safeNotify('toast', `Registro eliminado`);
      } catch (e) {
        this.safeNotify('error', 'Error al eliminar');
      }
    }
  }
};
</script>
