<template>
  <div class="d-flex">
    <!-- Sidebar -->
    <div
      class="sidebar text-white p-3 d-flex flex-column justify-content-between"
    >
      <div>
        <div class="text-center mb-4">
          <img
            src="https://cdn-icons-png.flaticon.com/512/847/847969.png"
            alt="Usuario"
            class="rounded-circle mb-2"
            width="80"
            height="80"
          />
          <h5 class="mb-0">Usuario Oficina</h5>
          <small class="text-light">Perfil: Oficina</small>
        </div>

        <!-- Menú -->
        <ul class="nav flex-column mt-4">
          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('Gestion')"
              >Gestionar usuarios</a
            >
          </li>

          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('ClubsR')"
              >Clubs registrados</a
            >
          </li>

          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('AlumnosSR')"
              >Alumnos sin registrar</a
            >
          </li>

          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('AlumnosR')"
              >Alumnos registrados</a
            >
          </li>

          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('Listas')"
              >Listas de clubs</a
            >
          </li>

          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('Constancias')"
              >Constancias</a
            >
          </li>

          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('Auditoria')"
              >Auditoría</a
            >
          </li>
        </ul>
      </div>

      <div class="text-center mt-auto">
        <button class="btn btn-outline-light w-100" @click="cerrarSesion">
          Cerrar sesión
        </button>
      </div>
    </div>

    <!-- Contenido dinámico -->
    <div class="content flex-grow-1 p-4 bg-light overflow-auto">
      <component
        :is="currentView"
        :clubs="clubs"
        :alumnos="alumnos"
        :usuarios="usuarios"
        :fechas="fechas"
        :auditoria="auditoria"
        @add-club="handleAddClub"
        @edit-club="handleEditClub"
        @delete-club="handleDeleteClub"
        @add-alumno="handleAddAlumno"
        @delete-alumno="handleDeleteAlumno"
        @assign-alumno="handleAssignAlumno"
        @log="handleLog"
        @import-unregistered="handleImportUnregistered"
        @filter-users="handleFilterUsers"
      />
    </div>

    <!-- Toast container for messages (global) -->
    <div
      class="toast-container position-fixed bottom-0 end-0 p-3"
      style="z-index: 2000"
    >
      <div class="toast text-bg-success" id="toastSuccess">
        <div class="toast-body">{{ toastMsg }}</div>
      </div>
      <div class="toast text-bg-danger" id="toastError">
        <div class="toast-body">{{ errorMsg }}</div>
      </div>
    </div>
  </div>
</template>

<script>
/*
  Oficina.vue: componente contenedor que almacena y comparte datos con los hijos.
  Componentes hijos importados desde la misma carpeta 'components'.
*/

import Gestion from "../components/Gestion.vue";
import ClubsR from "../components/ClubsR.vue";
import AlumnosSR from "../components/AlumnosSR.vue";
import AlumnosR from "../components/AlumnosR.vue";
import Constancias from "../components/Constancias.vue";
import Auditoria from "../components/Auditoria.vue";
import Listas from "../components/Listas.vue";

export default {
  name: "Oficina",
  components: {
    Gestion,
    ClubsR,
    AlumnosSR,
    AlumnosR,
    Constancias,
    Auditoria,
    Listas,
  },
  data() {
    return {
      // vista actual (nombre del componente)
      currentView: "ClubsR",

      // datos compartidos
      clubs: [
        {
          nombre: "Club de Robótica",
          descripcion: "Innovación y tecnología",
          cupo: 20,
          ocupados: 15,
          monitor: "Carlos Pérez",
        },
        {
          nombre: "Club de Arte",
          descripcion: "Creatividad y expresión",
          cupo: 15,
          ocupados: 10,
          monitor: "Laura Gómez",
        },
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
          asistencias: { "01/11": true, "08/11": true, "15/11": false },
        },
      ],

      // usuarios para Gestion (ejemplo)
      usuarios: [
        {
          id: 1,
          nombre: "admin",
          tipo: "oficina",
          correo: "admin@ejemplo.com",
        },
        {
          id: 2,
          nombre: "monitor1",
          tipo: "monitor",
          correo: "mon1@ejemplo.com",
        },
      ],

      fechas: ["01/11", "08/11", "15/11"],

      // auditoría: {fecha, usuario, accion, tipo, descripcion}
      auditoria: [
        {
          fecha: new Date().toLocaleString(),
          usuario: "admin",
          accion: "Inicializar",
          tipo: "sistema",
          descripcion: "Datos iniciales cargados",
        },
      ],

      toastMsg: "",
      errorMsg: "",
    };
  },
  methods: {
    setView(view) {
      this.currentView = view;
    },

    cerrarSesion() {
      this.$router.push("/");
    },

    // ------------- Handlers emitidos por hijos -------------
    handleAddClub(club, actor = "Usuario Oficina") {
      this.clubs.push({ ...club, ocupados: 0 });
      this.logAction(
        actor,
        "Insertar",
        "club",
        `Se creó el club "${club.nombre}"`
      );
      this.showToast("Club agregado correctamente");
    },

    handleEditClub({ index, club }, actor = "Usuario Oficina") {
      const old = this.clubs[index]?.nombre || "";
      this.$set(this.clubs, index, { ...this.clubs[index], ...club });
      this.logAction(
        actor,
        "Editar",
        "club",
        `Se editó el club "${old}" -> "${club.nombre || old}"`
      );
      this.showToast("Club actualizado");
    },

    handleDeleteClub(index, actor = "Usuario Oficina") {
      const name = this.clubs[index]?.nombre || "";
      this.clubs.splice(index, 1);
      this.logAction(actor, "Eliminar", "club", `Se eliminó el club "${name}"`);
      this.showToast("Club eliminado");
    },

    handleAddAlumno(alumno, actor = "Usuario Oficina") {
      // validaciones ya hechas en componente hijo; aquí solo inserta y loggea
      this.alumnos.push({
        ...alumno,
        faltas: alumno.faltas || 0,
        asistencias: alumno.asistencias || {},
      });
      this.logAction(
        actor,
        "Insertar",
        "alumno",
        `Se registró al alumno "${alumno.nombre} ${alumno.apellidoP}"`
      );
      this.showToast("Alumno registrado correctamente");
    },

    handleDeleteAlumno(index, actor = "Usuario Oficina") {
      const name = `${this.alumnos[index]?.nombre || ""} ${
        this.alumnos[index]?.apellidoP || ""
      }`;
      this.alumnos.splice(index, 1);
      this.logAction(
        actor,
        "Eliminar",
        "alumno",
        `Se eliminó al alumno "${name}"`
      );
      this.showToast("Alumno eliminado");
    },

    // Asignar un alumno (desde AlumnosSR) a un club (respeta cupo)
    handleAssignAlumno({ alumnoIndex, clubNombre }, actor = "Usuario Oficina") {
      const unregistered = this.unregisteredList || []; // handled in component hijo (emit)
      const alumno = this.unregisteredList
        ? this.unregisteredList[alumnoIndex]
        : null;
      // The AlumnosSR emits assign-alumno with alumno object; for simplicity expect child to pass alumno object
      // But parent will accept both forms: object or index + alumno provided in payload
      if (
        typeof alumnoIndex === "number" &&
        this.unregisteredList &&
        this.unregisteredList[alumnoIndex]
      ) {
        const al = this.unregisteredList.splice(alumnoIndex, 1)[0];
        // find club and increment ocupados if available
        const club = this.clubs.find((c) => c.nombre === clubNombre);
        if (club && club.ocupados < club.cupo) {
          club.ocupados++;
          // push to registered alumnos
          this.alumnos.push({
            ...al,
            club: clubNombre,
            faltas: 0,
            asistencias: {},
          });
          this.logAction(
            actor,
            "Insertar",
            "alumno",
            `Alumno "${al.nombre} ${al.apellidoP}" asignado a "${clubNombre}"`
          );
          this.showToast(`Alumno asignado a ${clubNombre}`);
        } else {
          this.showError("No hay cupo disponible en ese club");
        }
      } else if (alumnoIndex && alumnoIndex.nombre) {
        // payload is object {alumno, clubNombre}
        const al = alumnoIndex;
        const club = this.clubs.find((c) => c.nombre === clubNombre);
        if (club && club.ocupados < club.cupo) {
          club.ocupados++;
          this.alumnos.push({
            ...al,
            club: clubNombre,
            faltas: 0,
            asistencias: {},
          });
          this.logAction(
            actor,
            "Insertar",
            "alumno",
            `Alumno "${al.nombre} ${al.apellidoP}" asignado a "${clubNombre}"`
          );
          this.showToast(`Alumno asignado a ${clubNombre}`);
        } else {
          this.showError("No hay cupo disponible en ese club");
        }
      } else {
        this.showError("Asignación inválida");
      }
    },

    handleImportUnregistered(list) {
      // almacena temporalmente en memoria local la lista de sin registrar
      // para que AlumnosSR puede administrarla; se la pasa como prop en emisión inversa si se necesitara
      this.unregisteredList = Array.isArray(list) ? list : [];
      this.logAction(
        "Sistema",
        "Insertar",
        "alumnos_sin_registrar",
        `Se importaron ${this.unregisteredList.length} registros`
      );
      this.showToast("Registros importados (sin registrar)");
    },

    handleEditClubConfirm(payload) {
      // wrapper placeholder if child wants direct parent edit call
      this.handleEditClub(payload);
    },

    handleEditAlumno() {
      // placeholder - se puede implementar si se agrega editar alumnos
    },

    handleDeleteClub(index) {
      this.handleDeleteClub(index);
    },

    handleDeleteAlumno(index) {
      this.handleDeleteAlumno(index);
    },

    handleLog(entry) {
      this.logAction(
        entry.usuario || "Usuario Oficina",
        entry.accion,
        entry.tipo || "-",
        entry.descripcion || ""
      );
    },

    handleFilterUsers(filter) {
      // filter is object {tipo: 'oficina'|'monitor'|''}
      // devuelve lista filtrada (hijo puede solicitar)
      return this.usuarios.filter((u) =>
        filter.tipo ? u.tipo === filter.tipo : true
      );
    },

    // auditoría helper
    logAction(usuario, accion, tipo, descripcion) {
      this.auditoria.unshift({
        fecha: new Date().toLocaleString(),
        usuario,
        accion,
        tipo,
        descripcion,
      });
    },

    // mensajes
    showToast(msg) {
      this.toastMsg = msg;
      const t = new bootstrap.Toast(document.getElementById("toastSuccess"));
      t.show();
    },

    showError(msg) {
      this.errorMsg = msg;
      const t = new bootstrap.Toast(document.getElementById("toastError"));
      t.show();
    },
  },
};
</script>

<style scoped>
.sidebar {
  width: 250px;
  background-color: #12343b;
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
}

.nav-link:hover {
  background-color: rgba(255, 255, 255, 0.15);
  border-radius: 5px;
}

.content {
  margin-left: 250px;
  height: 100vh;
  overflow-y: auto;
}

.scrollable {
  max-height: calc(100vh - 100px);
  overflow-y: auto;
}
</style>
