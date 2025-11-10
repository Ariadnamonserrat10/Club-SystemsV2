<template>
  <div>
    <h3>Constancias</h3>

    <div v-for="(club, index) in clubs" :key="club.nombre + index" class="card mb-4 shadow-sm">
      <div class="card-header bg-primary text-white">
        {{ club.nombre }} — Monitor: {{ club.monitor || 'Sin asignar' }}
      </div>
      <div class="card-body">
        <p>{{ club.descripcion }}</p>

        <div v-if="filteredAlumnos(club.nombre).length === 0" class="text-muted">No hay alumnos en este club.</div>

        <table v-else class="table table-bordered align-middle">
          <thead class="table-secondary">
            <tr>
              <th>Alumno</th>
              <th v-for="fecha in fechas" :key="fecha">{{ fecha }}</th>
              <th>Constancia</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(alumno, i) in filteredAlumnos(club.nombre)" :key="'c-'+i">
              <td>{{ alumno.nombre }} {{ alumno.apellidoP }}</td>
              <td v-for="fecha in fechas" :key="fecha">
                <span v-if="alumno.asistencias?.[fecha]" class="text-success fw-bold">✔</span>
                <span v-else class="text-danger fw-bold">✖</span>
              </td>
              <td>
                <span :class="alumno.faltas <= 2 ? 'text-success' : 'text-danger'">
                  {{ alumno.faltas <= 2 ? 'Sí' : 'No' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>

      </div>
    </div>

    <button class="btn btn-outline-primary" @click="downloadAll">Descargar todas las constancias</button>
  </div>
</template>

<script>
export default {
  name: 'Constancias',
  props: ['clubs','alumnos','fechas'],
  methods: {
    filteredAlumnos(clubName) {
      return (this.alumnos || []).filter(a => a.club === clubName);
    },
    downloadAll() {
      // simple CSV generator for demo (puede mejorarse)
      const rows = [];
      for (const club of this.clubs) {
        const alumnos = this.filteredAlumnos(club.nombre);
        for (const a of alumnos) {
          rows.push([club.nombre, a.nombre + ' ' + a.apellidoP + ' ' + (a.apellidoM || ''), a.control, a.faltas]);
        }
      }
      const csv = rows.map(r => r.map(cell => `"${String(cell).replace(/"/g,'""')}"`).join(',')).join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.setAttribute('href', url);
      link.setAttribute('download', 'constancias.csv');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      this.$emit('log', { usuario: 'Usuario Oficina', accion: 'Exportar', tipo: 'constancias', descripcion: 'Descarga de constancias' });
    }
  }
};
</script>
