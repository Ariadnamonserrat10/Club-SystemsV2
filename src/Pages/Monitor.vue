<template>
  <div class="bg-light vh-100 p-3">
    <MonitorComponent
      :clubs="clubs"
      :alumnos="alumnos"
      :fechas="fechas"
    />
  </div>
</template>

<script>
import MonitorComponent from '../components/Monitor.vue';

export default {
  name: 'MonitorPage',
  components: { MonitorComponent },
  data() {
    return {
      clubs: [
        { nombre: "Club de Robótica", descripcion: "Innovación y tecnología", cupo: 20, ocupados: 15 },
        { nombre: "Club de Arte", descripcion: "Creatividad y expresión", cupo: 15, ocupados: 10 },
      ],
      alumnos: [
        {
          nombre: "Ana",
          apellidoP: "Torres",
          apellidoM: "López",
          carrera: "Ingeniería en Sistemas",
          semestre: 4,
          control: "20201122",
          telefono: "5544332211",
          club: "Club de Robótica",
          faltas: 1,
          asistencias: { "01/11": true, "08/11": true, "15/11": false }
        },
        {
          nombre: "Pedro",
          apellidoP: "Martínez",
          apellidoM: "Gómez",
          carrera: "Arquitectura",
          semestre: 3,
          control: "20202211",
          telefono: "5544332244",
          club: "Club de Robótica",
          faltas: 2,
          asistencias: { "01/11": true, "08/11": false, "15/11": true }
        }
      ],
      fechas: ["01/11", "08/11", "15/11"],
    };
  },
};
</script>
