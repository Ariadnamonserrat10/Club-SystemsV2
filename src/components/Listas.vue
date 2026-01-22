<template>
  <div>
    <h4 class="text-primary mb-3">Listas de Clubs</h4>

    <div class="mb-3">
      <label class="form-label">Selecciona un club:</label>
      <select v-model="clubSeleccionado" class="form-select">
        <option disabled value="">-- Seleccionar --</option>
        <option v-for="(club, i) in clubs" :key="i" :value="club.nombre">
          {{ club.nombre }}
        </option>
      </select>
    </div>

    <div v-if="clubSeleccionado">
      <h5 class="mt-3 text-secondary">Alumnos del {{ clubSeleccionado }}</h5>
      <table class="table table-bordered table-hover mt-3">
        <thead class="table-primary">
          <tr>
            <th>Nombre</th>
            <th v-for="fecha in fechasCols" :key="fecha">{{ fecha }}</th>
            <th>Acreditado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(alumno, index) in alumnosClub" :key="index">
            <td>{{ alumno.nombre }} {{ alumno.apellidoP }} {{ alumno.apellidoM }}</td>
            <td v-for="fecha in fechasCols" :key="fecha" class="text-center">
              <span v-if="alumno.asistencias && alumno.asistencias[fecha]" class="text-success fw-bold" style="font-size: 1.5rem;">✔</span>
              <span v-else class="text-danger fw-bold" style="font-size: 1.5rem;">✖</span>
            </td>
            <td>
              <span
                class="badge"
                :class="alumno.faltas < 3 ? 'bg-success' : 'bg-danger'"
              >
                {{ alumno.faltas < 3 ? 'Acreditado' : 'No acreditado' }}
              </span>
            </td>
            <td>
              <button
                class="btn btn-sm"
                :class="alumno.faltas < 3 ? 'btn-outline-success' : 'btn-outline-secondary'"
                :disabled="alumno.faltas >= 3"
                @click="imprimirConstancia(alumno)"
              >
                Imprimir
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="text-end mt-3">
        <button class="btn btn-primary" @click="imprimirTodas">
          Imprimir todas las constancias
        </button>
      </div>
    </div>

    <div v-else class="text-muted mt-4 text-center">
      Selecciona un club para mostrar su lista.
    </div>

    <!-- Vista previa / Plantilla imprimible -->
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
import { getAsistenciasPorClub } from '../services/api';
export default {
  name: 'Listas',
  props: ['clubs', 'alumnos', 'fechas'],
  data() {
    return {
      clubSeleccionado: '',
      previewData: null,
      periodoActual: this.getPeriodoActual(),
      alumnosData: [],
      fechasData: [],
    };
  },
  computed: {
    fechasCols() {
      return (this.fechasData && this.fechasData.length) ? this.fechasData : (this.fechas || []);
    },
    alumnosClub() {
      // Preferir alumnos desde backend; si no hay, usar fallback desde props
      let base = (this.alumnosData && this.alumnosData.length)
        ? this.alumnosData.slice()
        : (this.alumnos || []).filter(a => a.club === this.clubSeleccionado).map(a => {
            const asist = a.asistencias || {};
            const faltas = Object.values(asist).filter(v => v === false).length;
            return { ...a, asistencias: asist, faltas };
          });
      base.sort((a,b)=> (a.apellidoP||'').localeCompare(b.apellidoP||'') || (a.apellidoM||'').localeCompare(b.apellidoM||'') || (a.nombre||'').localeCompare(b.nombre||''));
      return base;
    },
    fechaHoy() {
      const ahora = new Date();
      const meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
      return { dia: ahora.getDate(), mes: meses[ahora.getMonth()], anio: ahora.getFullYear() };
    }
  },
  methods: {
    async loadAsistencias() {
      const club = (this.clubs || []).find(c => c.nombre === this.clubSeleccionado);
      if (!club || !club.id) { 
        this.alumnosData = []; 
        // Si no hay club, cargar desde props
        if (this.alumnos && this.alumnos.length) {
          const alumnosDelClub = this.alumnos.filter(a => a.club === this.clubSeleccionado);
          this.alumnosData = alumnosDelClub.map(a => ({
            ...a,
            asistencias: a.asistencias || {},
            faltas: Object.values(a.asistencias || {}).filter(v => v === false).length
          }));
        }
        return; 
      }
      try {
        const data = await getAsistenciasPorClub(club.id);
        this.fechasData = Array.isArray(data.fechas) ? data.fechas : [];
        const alumnos = Array.isArray(data.alumnos) ? data.alumnos : [];
        
        // Si el backend no devuelve alumnos, usar props
        if (!alumnos.length && this.alumnos && this.alumnos.length) {
          const alumnosDelClub = this.alumnos.filter(a => a.club === this.clubSeleccionado);
          this.alumnosData = alumnosDelClub.map(a => ({
            ...a,
            asistencias: a.asistencias || {},
            faltas: Object.values(a.asistencias || {}).filter(v => v === false).length
          }));
        } else {
          const asist = data.asistencias || {};
          this.alumnosData = alumnos.map(al => {
            const map = { ...(asist[al.id] || {}) };
            const faltas = Object.values(map).filter(v => v === false).length;
            return { ...al, asistencias: map, faltas };
          });
        }
      } catch (e) {
        console.error('Error cargando asistencias:', e);
        // Si hay error, cargar desde props
        if (this.alumnos && this.alumnos.length) {
          const alumnosDelClub = this.alumnos.filter(a => a.club === this.clubSeleccionado);
          this.alumnosData = alumnosDelClub.map(a => ({
            ...a,
            asistencias: a.asistencias || {},
            faltas: Object.values(a.asistencias || {}).filter(v => v === false).length
          }));
        } else {
          this.alumnosData = [];
        }
        this.fechasData = [];
      }
    },
    actualizarFaltas(alumno) {
      const asist = alumno.asistencias || {};
      const totalFaltas = Object.values(asist).filter(v => v === false).length;
      this.$set ? this.$set(alumno, 'faltas', totalFaltas) : (alumno.faltas = totalFaltas);
    },
    toUpper(v) {
      return (v == null ? '' : String(v)).toUpperCase();
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
    imprimirConstancia(alumno) {
      const club = alumno.club || this.clubSeleccionado || '';
      const data = {
        estudianteNombre: `${alumno.nombre || ''} ${alumno.apellidoP || ''} ${alumno.apellidoM || ''}`.trim(),
        numeroControl: alumno.control || '',
        carrera: alumno.carrera || 'INGENIERÍA EN SISTEMAS COMPUTACIONALES',
        club: club.toLowerCase(),
        desempeno: (alumno.desempeno || (alumno.faltas <= 1 ? 'EXCELENTE' : alumno.faltas === 2 ? 'BUENO' : 'REGULAR')).toString(),
        periodo: alumno.periodo || this.periodoActual,
        tipoActividad: this.tipoActividad(club)
      };
      this.previewData = { ...data };
      this.$nextTick(() => this.doPrint());
    },
    imprimirTodas() {
      const lista = this.alumnosClub.filter(a => (a.faltas || 0) < 3);
      if (!lista.length) { alert('No hay alumnos acreditados para imprimir constancias.'); return; }
      // Secuencia simple: imprime una por una con pequeños retrasos
      let i = 0;
      const next = () => {
        if (i >= lista.length) { this.previewData = null; return; }
        this.imprimirConstancia(lista[i]);
        i += 1;
        setTimeout(next, 1200);
      };
      next();
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
  },
  watch: {
    clubSeleccionado() {
      this.loadAsistencias();
    }
  },
  mounted() {
    // si ya hay un club seleccionado inicial, cargar
    if (this.clubSeleccionado) this.loadAsistencias();
  }
};
</script>

<style scoped>
.table { font-size: 0.95rem; }

.print-preview { position: fixed; inset: 0; background: rgba(0,0,0,.5); display: flex; align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
.print-preview .A4 { background: #fff; max-width: 210mm; padding: 20mm; box-shadow: 0 10px 30px rgba(0,0,0,.3); }
.print-preview .acciones { text-align: center; margin-top: 12px; }
</style>
