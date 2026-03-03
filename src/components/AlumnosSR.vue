<template>
  <div>
    <h3 class="text-center mb-4">Alumnos sin registrar</h3>

    <div class="d-flex justify-content-start align-items-center my-3">
      <div>
        <button class="btn btn-info me-2" @click="triggerCSV">Importar CSV</button>
        <input type="file" ref="csvInput" style="display: none" accept=".csv" @change="handleCSVUpload" />
      </div>
    </div>

    <div v-if="!unregistered || unregistered.length === 0" class="text-muted text-center py-4">
      No hay alumnos sin registrar.
    </div>

    <div v-if="unregistered && unregistered.length" class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-primary">
          <tr>
            <th>Nombre</th>
            <th>No. Control</th>
            <th>Teléfono</th>
            <th>Carrera</th>
            <th>Semestre</th>
            <th>Estado</th>
            <th>Opciones de club propuestas</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(u, idx) in unregistered" :key="u.id || (getControl(u) || '') + idx">
            <td>{{ u.nombre }} {{ u.apellidoP }} {{ u.apellidoM }}</td>
            <td>{{ getControl(u) || '—' }}</td>
            <td>{{ u.telefono || '—' }}</td>
            <td>{{ getCarreraName(u) || '—' }}</td>
            <td>{{ u.semestre_id || '—' }}</td>
            <td>
              <span :class="['badge', u.estado === 'APROBADO' ? 'bg-success' : u.estado === 'RECHAZADO' ? 'bg-danger' : 'bg-warning text-dark']">
                {{ u.estado || 'PENDIENTE' }}
              </span>
            </td>
            <!-- Opciones propuestas (solo informativo) -->
            <td>
              <div class="d-flex flex-column gap-1">
                <span
                  v-for="(opt, oIndex) in getOpciones(u)"
                  :key="oIndex"
                  :class="['badge', oIndex < 3 ? 'bg-secondary' : 'bg-info text-dark']"
                >
                  {{ oIndex + 1 }}. {{ opt }}
                </span>
                <span v-if="getOpciones(u).length === 0" class="text-muted">—</span>
              </div>
            </td>
            <!-- Acciones -->
            <td>
              <div class="d-flex flex-wrap gap-1">
                <template v-if="u.estado === 'PENDIENTE' || !u.estado">
                  <button
                    v-for="(opt, oIndex) in getOpcionesPrincipales(u)"
                    :key="'asignar-' + oIndex"
                    class="btn btn-success btn-sm"
                    :title="'Asignar a: ' + opt"
                    @click="assignToClub(u, opt)"
                  >
                    Asignar {{ oIndex + 1 }}
                  </button>
                </template>
                <button class="btn btn-sm btn-outline-danger" @click="deleteRecord(u, idx)">Eliminar</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { getAlumnosPendientes, saveAlumnosPendientes, updateEstatusPendiente, deleteAlumnoPendiente } from '../services/api';

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

    // Normaliza el número de control (DB puede devolver `numeroControl` o `numero_control`)
    getControl(u) {
      return u.numeroControl || u.numero_control || '';
    },

    // Resuelve la abreviatura de la carrera por carrera_id
    getCarreraName(u) {
      if (!u.carrera_id || !Array.isArray(this.carreras)) return '';
      const c = this.carreras.find(c => c.id == u.carrera_id);
      return c ? c.abreviatura : '';
    },

    async loadUnregistered() {
      try {
        const data = await getAlumnosPendientes();
        let list = Array.isArray(data) ? data : [];

        // Auto-eliminar registros de prueba "Test User Dev"
        const testRecords = list.filter(u =>
          (u.nombre || '').toLowerCase().includes('test') &&
          (u.apellidoP || '').toLowerCase().includes('user')
        );
        for (const rec of testRecords) {
          try { await deleteAlumnoPendiente(rec.id); } catch (_) {}
        }
        if (testRecords.length > 0) {
          const fresh = await getAlumnosPendientes();
          list = Array.isArray(fresh) ? fresh : [];
        }

        this.unregistered = list;
      } catch (e) {
        console.error(e);
        this.safeNotify('error', 'Error al cargar alumnos pendientes');
      }
    },

    // Extrae las opciones de club desde el alumno (soporta array o string separado por coma)
    getOpciones(u) {
      if (u.opciones && Array.isArray(u.opciones) && u.opciones.length > 0) return u.opciones;
      if (u.opciones_nombres && u.opciones_nombres.trim()) {
        return u.opciones_nombres.split(',').map(s => s.trim()).filter(Boolean);
      }
      return [];
    },

    // Solo las primeras 3 opciones (para los botones de asignar)
    getOpcionesPrincipales(u) {
      return this.getOpciones(u).slice(0, 3);
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
      reader.readAsText(file, 'UTF-8');
      event.target.value = '';
    },

    async parseGoogleFormsCSV(text) {
      const lines = text.split(/\r?\n/).filter(line => line.trim() !== '');
      if (lines.length < 2) {
        this.safeNotify('error', 'El CSV no tiene datos suficientes');
        return;
      }

      const parseCSVLine = (row) => {
        const result = [];
        let cur = '';
        let inQ = false;
        for (let i = 0; i < row.length; i++) {
          const c = row[i];
          if (c === '"') {
            inQ = !inQ;
          } else if (c === ',' && !inQ) {
            result.push(cur.trim().replace(/^"|"$/g, ''));
            cur = '';
          } else {
            cur += c;
          }
        }
        result.push(cur.trim().replace(/^"|"$/g, ''));
        return result;
      };

      const headers = parseCSVLine(lines[0]).map(h => h.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''));
      const dataRows = lines.slice(1);

      console.log('[CSV] Headers detectados:', headers);

      // Log de carreras disponibles para depuración
      console.log('[DEBUG] Carreras disponibles en DB:', this.carreras.map(c => ({ id: c.id, nombre: c.nombre, abreviatura: c.abreviatura })));

      // Mapeo basado en los encabezados reales del Google Form:
      // [0] Marca temporal  (ignorar)
      // [1] Ingrese su Nombre Completo incluyendo Apellidos
      // [2] Ingrese un Número Telefonico
      // [3] Ingrese su Número de Control
      // [4] Ingrese su semestre (ej.1,2,3,...,9)
      // [5] Seleccione su carrera
      // [6] Seleccione su Primera Opcion a Club
      // [7] Seleccione su Segunda Opcion a Club
      // [8] Seleccione su Tercera Opcion a Club
      // [9] Escriba solo el nombre de algun otro club que le interesaria

      const findIndex = (keywords) =>
        headers.findIndex(h => keywords.some(k => h.includes(k.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''))));

      const idxNombre   = findIndex(['nombre completo', 'nombre', 'apellidos']);
      const idxTel      = findIndex(['telefonico', 'telefono', 'numero telefonico']);
      const idxControl  = findIndex(['numero de control', 'numero control', 'control']);
      const idxSemestre = findIndex(['semestre']);
      const idxCarrera  = findIndex(['carrera']);
      const idxOpt1     = findIndex(['primera opcion']);
      const idxOpt2     = findIndex(['segunda opcion']);
      const idxOpt3     = findIndex(['tercera opcion']);
      const idxOtro     = findIndex(['otro club', 'algun otro club']);

      console.log('[CSV] Índices mapeados:', { idxNombre, idxTel, idxControl, idxSemestre, idxCarrera, idxOpt1, idxOpt2, idxOpt3, idxOtro });

      const newRegistros = [];
      dataRows.forEach((row, rowNum) => {
        const cells = parseCSVLine(row);
        if (cells.length < 3) return;

        const fullNombre = idxNombre >= 0 ? (cells[idxNombre] || '') : '';
        if (!fullNombre.trim()) return; // omitir filas sin nombre

        const { nombre, apP, apM } = this.splitNombre(fullNombre);
        const carName = idxCarrera >= 0 ? (cells[idxCarrera] || '').trim() : '';
        
        // Matching flexible de carrera: exacto, includes, o por abreviatura, ignorando acentos
        let carObj = null;
        if (carName && Array.isArray(this.carreras)) {
          const normalize = (str) => str.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
          const carNormalized = normalize(carName);
          carObj = this.carreras.find(c => normalize(c.nombre) === carNormalized)
            || this.carreras.find(c => normalize(c.nombre).includes(carNormalized) || carNormalized.includes(normalize(c.nombre)))
            || this.carreras.find(c => c.abreviatura && normalize(c.abreviatura) === carNormalized);
          
          // Log detallado del matching
          console.log(`[DEBUG] Matching carrera: "${carName}" -> "${carNormalized}"`);
          if (!carObj) {
            console.log('[DEBUG] No match encontrado. Candidatos probados:');
            this.carreras.forEach(c => {
              console.log(`  - Nombre: "${c.nombre}" (${normalize(c.nombre)}), Abreviatura: "${c.abreviatura}" (${c.abreviatura ? normalize(c.abreviatura) : 'null'})`);
            });
          }
        }

        const rawControl = idxControl >= 0 ? (cells[idxControl] || '') : '';
        const rawTel     = idxTel     >= 0 ? (cells[idxTel]     || '') : '';
        const rawSem     = idxSemestre >= 0 ? (cells[idxSemestre] || '') : '';

        const opciones = [
          idxOpt1 >= 0 ? cells[idxOpt1] : null,
          idxOpt2 >= 0 ? cells[idxOpt2] : null,
          idxOpt3 >= 0 ? cells[idxOpt3] : null,
          idxOtro >= 0 ? cells[idxOtro] : null,
        ].filter(v => v && v.trim());

        console.log(`[CSV] Fila ${rowNum + 2}: nombre="${fullNombre}", control="${rawControl}", tel="${rawTel}", carrera="${carName}" -> ${carObj ? carObj.abreviatura : 'NO MATCH'}, semestre="${rawSem}", opciones=`, opciones);

        newRegistros.push({
          nombre,
          apellidoP: apP,
          apellidoM: apM,
          numeroControl: rawControl.replace(/\D/g, '').substring(0, 8),
          telefono: rawTel.replace(/\D/g, '').substring(0, 10),
          carrera_id: carObj ? carObj.id : null,
          semestre_id: rawSem ? (parseInt(rawSem) || null) : null,
          opciones,
          estado: 'PENDIENTE',
        });
      });

      if (newRegistros.length === 0) {
        this.safeNotify('error', 'No se encontraron registros válidos en el CSV');
        return;
      }

      try {
        await saveAlumnosPendientes(newRegistros);
        await this.loadUnregistered();
        this.$emit('log', {
          usuario: 'Usuario Oficina',
          accion: 'Importar',
          tipo: 'csv',
          descripcion: `Importados ${newRegistros.length} registros desde CSV`,
        });
        this.safeNotify('toast', `✓ Importados ${newRegistros.length} registros correctamente`);
      } catch (e) {
        console.error('[CSV] Error al guardar:', e);
        this.safeNotify('error', 'Error al guardar registros: ' + (e.message || ''));
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
        // Buscar club por nombre aproximado, removiendo "Club de " si existe
        const normalizedClubName = clubName.replace(/^Club de\s+/i, '').toLowerCase().trim();
        let clubObj = null;
        if (Array.isArray(this.clubs)) {
          // Primero buscar coincidencia exacta
          clubObj = this.clubs.find(c => c.nombre.toLowerCase() === clubName.toLowerCase());
          if (!clubObj) {
            // Luego buscar por nombre normalizado (después de "Club de")
            clubObj = this.clubs.find(c => c.nombre.toLowerCase().includes(normalizedClubName) || normalizedClubName.includes(c.nombre.toLowerCase()));
          }
        }
        if (!clubObj || !clubObj.id) throw new Error('No se encontró el ID del club: ' + clubName);

        // Verificar cupo disponible
        const cupoOcupado = clubObj.cupo_ocupado || 0;
        const cupoLimite = clubObj.cupo_limite || 0;
        if (cupoOcupado >= cupoLimite) {
          throw new Error('No hay cupo disponible en este club');
        }

        await updateEstatusPendiente(alumno.id, {
          estado: 'APROBADO',
          club_solicitado_id: clubObj.id,
        });

        this.$emit('assign-alumno', { alumnoIndex: alumno, clubNombre: clubName });
        this.$emit('request-reload-alumnos');
        this.$emit('request-reload-clubs');

        await this.loadUnregistered();
        this.safeNotify('toast', `Alumno aprobado y asignado a ${clubName}`);
      } catch (e) {
        this.safeNotify('error', e.message || 'Error al asignar alumno');
      }
    },

    async rejectStudent(alumno) {
      try {
        await updateEstatusPendiente(alumno.id, { estado: 'RECHAZADO' });
        await this.loadUnregistered();
        this.safeNotify('toast', 'Alumno marcado como RECHAZADO');
      } catch (e) {
        this.safeNotify('error', 'Error al rechazar alumno');
      }
    },

    async deleteRecord(alumno, index) {
      if (!confirm('¿Eliminar este registro permanentemente?')) return;
      try {
        await deleteAlumnoPendiente(alumno.id);
        this.unregistered.splice(index, 1);
        this.safeNotify('toast', 'Registro eliminado');
      } catch (e) {
        this.safeNotify('error', 'Error al eliminar');
      }
    },
  },
};
</script>

<style scoped>
.table th {
  text-align: center;
  font-weight: bold;
}
</style>
