<template>
  <div>
    <h3>Gestionar usuarios</h3>

    <div class="row mb-3">
      <div class="col-md-4">
        <select v-model="filterTipo" class="form-select">
          <option value="">Todos</option>
          <option value="oficina">Oficina</option>
          <option value="monitor">Monitor</option>
        </select>
      </div>
      <div class="col-md-8 text-end">
        <button class="btn btn-primary" @click="refresh">Refrescar</button>
      </div>
    </div>

    <table class="table table-striped table-bordered">
      <thead class="table-primary">
        <tr>
          <th>Nombre</th>
          <th>Tipo</th>
          <th>Correo</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="u in filteredUsers" :key="u.id">
          <td>{{ u.nombre }}</td>
          <td>{{ u.tipo }}</td>
          <td>{{ u.correo }}</td>
          <td>
            <button class="btn btn-warning btn-sm me-2" @click="editUser(u)">Editar</button>
            <button class="btn btn-danger btn-sm" @click="deleteUser(u)">Eliminar</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Simple modals could añadirse para editar/agregar usuarios si se desea -->
  </div>
</template>

<script>
export default {
  name: 'Gestion',
  props: ['usuarios'],
  data() {
    return {
      filterTipo: '',
    };
  },
  computed: {
    filteredUsers() {
      if (!this.filterTipo) return this.usuarios || [];
      return (this.usuarios || []).filter(u => u.tipo === this.filterTipo);
    }
  },
  methods: {
    refresh() {
      this.$emit('log', { usuario: 'Usuario Oficina', accion: 'Consultar', tipo: 'usuarios', descripcion: 'Refrescar lista de usuarios' });
    },
    editUser(u) {
      this.$emit('log', { usuario: 'Usuario Oficina', accion: 'Editar', tipo: 'usuario', descripcion: `Editar usuario ${u.nombre}` });
      this.$root.showToast ? this.$root.showToast('Función editar (pendiente implementar)') : null;
    },
    deleteUser(u) {
      this.$emit('log', { usuario: 'Usuario Oficina', accion: 'Eliminar', tipo: 'usuario', descripcion: `Eliminar usuario ${u.nombre}` });
      this.$root.showToast ? this.$root.showToast('Usuario eliminado (simulado)') : null;
    }
  }
};
</script>
