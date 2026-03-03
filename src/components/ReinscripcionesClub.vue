<template>
  <div>
    <div class="d-flex align-items-center mb-3">
      <button class="btn btn-link" @click="$emit('back')">← Volver</button>
      <h4 class="ms-2">Alumnos - {{ clubNombre }}</h4>
      <div class="ms-auto small text-muted">Fechas: {{ fechas.length }}</div>
    </div>

    <div v-if="loading" class="text-muted">Cargando...</div>
    <div v-else>
      <table class="table table-sm">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Control</th>
            <th>Asistencia</th>
            <th>Faltas</th>
            <th>Evaluación</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="al in alumnos" :key="al.id">
            <td>{{ al.nombre }} {{ al.apellidoP }} {{ al.apellidoM }}</td>
            <td>{{ al.numeroControl || '' }}</td>
            <td>{{ attendancePercent(al) }}%</td>
            <td>{{ faltasCount(al) }}</td>
            <td>{{ evalLabel(al) }}</td>
            <td>
              <button v-if="cumplio(al)" class="btn btn-sm btn-success" @click="reinscribir(al)">Reinscribir</button>
              <button v-else class="btn btn-sm btn-danger" @click="darDeBaja(al)">Dar de baja</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { getAsistenciasPorClub, batchReinscripciones, getEvaluacion } from '../services/api';

export default {
  name: 'ReinscripcionesClub',
  props: { clubId: { type: Number, required: true }, clubs: { type: Array, default: () => [] } },
  data() {
    return {
      loading: true,
      alumnos: [],
      fechas: [],
      asistencias: {},
      evaluaciones: {},
      clubNombre: '',
      // thresholds (sensible por defecto)
      attendanceThreshold: 0.6, // 60%
      valorNumericoThreshold: 60,
      nivelDesempenoThreshold: 3,
    };
  },
  async created() {
    const club = (this.clubs || []).find(c => Number(c.id) === Number(this.clubId));
    this.clubNombre = club ? club.nombre : '';
    await this.loadData();
  },
  methods: {
    async loadData() {
      this.loading = true;
      try {
        const data = await getAsistenciasPorClub(this.clubId);
        this.fechas = data.fechas || [];
        this.asistencias = data.asistencias || {};
        this.alumnos = Array.isArray(data.alumnos) ? data.alumnos : [];

        // Cargar evaluaciones por alumno (última evaluación)
        for (const al of this.alumnos) {
          try {
            const resp = await getEvaluacion({ nombre_estudiante: `${al.nombre} ${al.apellidoP} ${al.apellidoM}`, nombre_club: this.clubNombre });
            this.evaluaciones[al.id] = resp || null;
          } catch (e) {
            this.evaluaciones[al.id] = null;
          }
        }
      } catch (e) {
        this.$emit('notify-error', { message: 'Error cargando alumnos: ' + (e.message || e) });
      } finally {
        this.loading = false;
      }
    },
    attendancePercent(al) {
      const fechas = this.fechas || [];
      if (!fechas.length) return 0;
      const map = this.asistencias[al.id] || {};
      let pres = 0;
      for (const f of fechas) if (map[f]) pres++;
      return Math.round((pres / fechas.length) * 100);
    },
    evalLabel(al) {
      const ev = this.evaluaciones[al.id];
      if (!ev) return 'Sin evaluación';
      if (ev.nivel_desempeno) return `Nivel ${ev.nivel_desempeno}`;
      if (ev.valor_numerico) return `${ev.valor_numerico}`;
      return 'Evaluado';
    },
    faltasCount(al) {
      const fechas = this.fechas || [];
      if (!fechas.length) return 0;
      const map = this.asistencias[al.id] || {};
      let pres = 0;
      for (const f of fechas) if (map[f]) pres++;
      return Math.max(0, fechas.length - pres);
    },

    cumplio(al) {
      // Reglas actualizadas:
      // - Si tiene 3 o más faltas => NO cumplió (dar de baja)
      // - Si tiene 2 o menos faltas => puede reinscribirse
      //   * Si existe evaluación, ésta debe cumplir los umbrales
      //   * Si NO existe evaluación, se permite reinscribir (por asistencia)
      const faltas = this.faltasCount(al);
      if (faltas >= 3) return false;

      // faltas <= 2 -> comprobar evaluación si existe
      const ev = this.evaluaciones[al.id];
      if (!ev) return true;

      let evalOk = false;
      if (ev.valor_numerico && Number(ev.valor_numerico) >= this.valorNumericoThreshold) evalOk = true;
      if (ev.nivel_desempeno && Number(ev.nivel_desempeno) >= this.nivelDesempenoThreshold) evalOk = true;
      return evalOk;
    },
    async darDeBaja(al) {
      try {
        await batchReinscripciones({ tipo: 'baja', alumnos: [al.id] });
        this.$emit('notify', { message: 'Alumno dado de baja.' });
        await this.loadData();
        this.$emit('request-reload-alumnos');
        this.$emit('request-reload-clubs');
      } catch (e) {
        this.$emit('notify-error', { message: e.message || e });
      }
    },
    async reinscribir(al) {
      try {
        await batchReinscripciones({ tipo: 'inscribir_acreditar', alumnos: [al.id], club_id: this.clubId, fechas: this.fechas });
        this.$emit('notify', { message: 'Alumno reinscrito y acreditado.' });
        await this.loadData();
        this.$emit('request-reload-alumnos');
        this.$emit('request-reload-clubs');
      } catch (e) {
        this.$emit('notify-error', { message: e.message || e });
      }
    }
  }
};
</script>

<style scoped>
.table td { vertical-align: middle; }
</style>
