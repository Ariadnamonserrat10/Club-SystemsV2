<template>
  <div>
    <h3>Constancias</h3>

    <div v-for="(club, index) in clubs" :key="club.nombre + index" class="card mb-4 shadow-sm">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <div>
          {{ club.nombre }} — Monitor: {{ club.monitor || 'Sin asignar' }}
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-light" @click="openConstanciaPreview(club)">Vista previa</button>
        </div>
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
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(alumno, i) in filteredAlumnos(club.nombre)" :key="'c-'+i">
              <td>{{ alumno.nombre }} {{ alumno.apellidoP }} {{ alumno.apellidoM }}</td>
              <td v-for="fecha in fechas" :key="fecha">
                <span v-if="alumno.asistencias?.[fecha]" class="text-success fw-bold">✔</span>
                <span v-else class="text-danger fw-bold">✖</span>
              </td>
              <td>
                <span :class="alumno.faltas <= 2 ? 'text-success' : 'text-danger'">
                  {{ alumno.faltas <= 2 ? 'Sí' : 'No' }}
                </span>
              </td>
              <td>
                <button class="btn btn-sm btn-outline-primary" @click="printConstancia({
                  estudianteNombre: `${alumno.nombre} ${alumno.apellidoP} ${alumno.apellidoM || ''}`.trim(),
                  numeroControl: alumno.control,
                  carrera: alumno.carrera || 'INGENIERÍA EN SISTEMAS COMPUTACIONALES',
                  club: (alumno.club || club.nombre || '').toLowerCase(),
                  desempeno: (alumno.desempeno || (alumno.faltas <= 1 ? 'EXCELENTE' : alumno.faltas === 2 ? 'BUENO' : 'REGULAR')).toString(),
                  periodo: alumno.periodo || periodoActual
                })">Imprimir</button>
              </td>
            </tr>
          </tbody>
        </table>

      </div>
    </div>

    <button class="btn btn-outline-primary" @click="downloadAll">Descargar todas las constancias</button>

    <!-- Modal/Vista previa imprimible -->
    <div v-if="previewData" class="print-preview">
      <div class="constancia A4" id="constancia">
        <div class="header">
          <div class="logos">
            <div class="logo izq"></div>
            <div class="titulos">
              <div class="top-row">
                <span>TECNOLÓGICO NACIONAL DE MÉXICO</span>
                <span>INSTITUTO TECNOLÓGICO DE TLAXIACO</span>
              </div>
              <div class="meta">
                <div>Referencia a la Norma ISO 9001:2015 8.1</div>
                <div>Código:TecNM-VI-PO-003-05</div>
                <div>TecNM-VI-PO-003-05 Rev. 0</div>
              </div>
            </div>
            <div class="logo der"></div>
          </div>
          <div class="separador"></div>
          <h2>CONSTANCIA DE CUMPLIMIENTO DE ACTIVIDAD CULTURAL Y/O DEPORTIVA</h2>
        </div>

        <div class="cuerpo">
          <p class="destinatario">
            C. BLANCA ANSELMA CASTRO CASTRO<br/>
            JEFA DEL DEPARTAMENTO DE SERVICIOS ESCOLARES<br/>
            PRESENTE
          </p>

          <p class="texto justificado">
            La que suscribe Olimpia Cruz Reyes, Jefa del Departamento de Actividades Extraescolares, por este medio se permite hacer de su conocimiento que la estudiante
            <strong>{{ toUpper(previewData.estudianteNombre) }}</strong> con número de control <strong>{{ toUpper(previewData.numeroControl) }}</strong> de la carrera de <strong>{{ toUpper(previewData.carrera) }}</strong>, ha cumplido su actividad extraescolar en el club de <strong>{{ toUpper(previewData.club) }}</strong> con el nivel de desempeño <strong>{{ toUpper(previewData.desempeno) }}</strong> y un valor numérico de <strong>{{ desempenoValor(previewData.desempeno) }}</strong> durante el periodo escolar <strong>{{ toUpper(previewData.periodo) }}</strong>, como actividad <strong>{{ toUpper(previewData.tipoActividad || tipoActividad(previewData.club)) }}</strong>, con un valor curricular de 1 crédito.
          </p>

          <p class="lugar-fecha">
            Se extiende la presente en la Heroica ciudad de Tlaxiaco a los {{ fechaHoy.dia }} días del mes de {{ fechaHoy.mes }} de {{ fechaHoy.anio }}.
          </p>

          <div class="firmas">
            <div class="col">
              <div class="linea"></div>
              <div class="nombre">FERNANDO JAIR MENDOZA JIMENEZ</div>
              <div class="cargo">JEFE DE LA OFICINA DE PROMOCIÓN DEPORTIVA</div>
            </div>
            <div class="col">
              <div class="linea"></div>
              <div class="nombre">OLIMPIA CRUZ REYES</div>
              <div class="cargo">JEFA DEL DEPTO. DE ACTIVIDADES EXTRAESCOLARES</div>
            </div>
          </div>
        </div>
      </div>

      <div class="acciones d-flex align-items-center gap-2 justify-content-center">
        <label class="me-1">Tipo de club:</label>
        <select v-model="previewData.tipoActividad" class="form-select form-select-sm" style="width:auto;">
          <option value="CULTURAL">CULTURAL</option>
          <option value="DEPORTIVA">DEPORTIVA</option>
        </select>
        <button class="btn btn-secondary" @click="previewData = null">Cerrar</button>
        <button class="btn btn-primary" @click="doPrint()">Imprimir</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Constancias',
  props: ['clubs','alumnos','fechas'],
  data() {
    return {
      previewData: null,
      periodoActual: this.getPeriodoActual()
    };
  },
  computed: {
    fechaHoy() {
      const ahora = new Date();
      const meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
      return { dia: ahora.getDate(), mes: meses[ahora.getMonth()], anio: ahora.getFullYear() };
    }
  },
  methods: {
    filteredAlumnos(clubName) {
      return (this.alumnos || []).filter(a => a.club === clubName);
    },
    downloadAll() {
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
    },
    desempenoValor(desempeno) {
      const mapa = { 'EXCELENTE': 4, 'BUENO': 3, 'REGULAR': 2, 'DEFICIENTE': 1 };
      return mapa[(desempeno || '').toUpperCase()] || 0;
    },
    getPeriodoActual() {
      const f = new Date();
      const mes = f.getMonth();
      const anio = f.getFullYear();
      return (mes >= 0 && mes <= 5) ? `Enero-Junio ${anio}` : `Agosto-Diciembre ${anio}`;
    },
    isCulturalName(nombre) {
      const n = (nombre || '').toString().trim().toLowerCase();
      const culturales = ['danza', 'rondalla', 'banda de musica', 'banda de música'];
      return culturales.includes(n);
    },
    tipoActividad(nombreClub) {
      return this.isCulturalName(nombreClub) ? 'CULTURAL' : 'DEPORTIVA';
    },
    openConstanciaPreview(club) {
      this.previewData = {
        estudianteNombre: 'NOMBRE DEL ESTUDIANTE',
        numeroControl: '00000000',
        carrera: 'INGENIERÍA EN SISTEMAS COMPUTACIONALES',
        club: (club?.nombre || 'club').toLowerCase(),
        desempeno: 'EXCELENTE',
        periodo: this.periodoActual,
        tipoActividad: this.tipoActividad(club?.nombre)
      };
    },
    printConstancia(data) {
      this.previewData = { tipoActividad: this.tipoActividad(data.club), ...data };
      this.$nextTick(() => this.doPrint());
    },
    doPrint() {
      const nodo = document.getElementById('constancia');
      if (!nodo) return;
      const frame = document.createElement('iframe');
      frame.style.position = 'fixed';
      frame.style.right = '0';
      frame.style.bottom = '0';
      frame.style.width = '0';
      frame.style.height = '0';
      frame.style.border = '0';
      document.body.appendChild(frame);
      const win = frame.contentWindow;
      const doc = win.document;
      const html = `<!DOCTYPE html>
      <html>
        <head>
          <meta charset="utf-8" />
          <title>Constancia</title>
          <style>${this.printStyles()}</style>
        </head>
        <body>${nodo.outerHTML}</body>
      </html>`;
      doc.open();
      doc.write(html);
      doc.close();
      const tryPrint = () => {
        try { win.focus(); win.print(); } finally {
          setTimeout(() => document.body.removeChild(frame), 600);
        }
      };
      if (doc.readyState === 'complete') {
        tryPrint();
      } else {
        doc.addEventListener('readystatechange', () => {
          if (doc.readyState === 'complete') tryPrint();
        });
      }
    },
    toUpper(v) {
      return (v == null ? '' : String(v)).toUpperCase();
    },
    printStyles() {
      return `
        @page { size: A4; margin: 20mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #000; }
        .A4 { width: 190mm; margin: 0 auto; }
        .header { text-align: center; }
        .logos { display: grid; grid-template-columns: 80px 1fr 80px; align-items: center; gap: 10px; }
        .logo.izq, .logo.der { width: 70px; height: 70px; background: #eee; border-radius: 6px; }
        .titulos .top-row { display: flex; justify-content: space-between; font-size: 12px; font-weight: bold; }
        .titulos .meta { display: flex; justify-content: space-between; font-size: 10px; margin-top: 4px; }
        .separador { height: 2px; background: #000; margin: 10px 0; }
        h2 { font-size: 16px; margin: 10px 0 20px; text-align: center; }
        .cuerpo { font-size: 12px; line-height: 1.6; }
        .destinatario { font-weight: bold; text-align: left; }
        .texto.justificado { text-align: justify; }
        .lugar-fecha { margin-top: 16px; }
        .firmas { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 36px; text-align: center; }
        .firmas .linea { border-top: 1px solid #000; margin: 36px 40px 6px; }
        .firmas .nombre { font-weight: bold; }
        .firmas .cargo { font-size: 11px; }
      `;
    }
  }
};
</script>

<style scoped>
.print-preview {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  z-index: 1000;
}
.print-preview .A4 {
  background: #fff;
  max-width: 210mm;
  padding: 20mm;
  box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}
.print-preview .acciones {
  text-align: center;
  margin-top: 12px;
}
</style>
