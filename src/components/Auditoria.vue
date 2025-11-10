<template>
  <div>
    <h3>Auditoría</h3>
    <p class="text-muted">Registros de acciones: insertar, editar, eliminar, exportar...</p>

    <table class="table table-striped table-bordered">
      <thead class="table-primary">
        <tr>
          <th>Fecha</th>
          <th>Usuario</th>
          <th>Acción</th>
          <th>Tipo</th>
          <th>Descripción</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(r, idx) in auditoria" :key="idx">
          <td>{{ r.fecha }}</td>
          <td>{{ r.usuario }}</td>
          <td>{{ r.accion }}</td>
          <td>{{ r.tipo }}</td>
          <td>{{ r.descripcion }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  name: 'Auditoria',
  props: ['auditoria']
};
</script>
