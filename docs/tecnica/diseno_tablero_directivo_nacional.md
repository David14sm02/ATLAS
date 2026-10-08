# Especificación de Diseño y Arquitectura UX: Tablero Directivo Nacional (Storytelling)

**Proyecto:** ATLAS TecNM  
**Módulo:** Dashboard Principal de Dirección General (Nivel 1)  
**Enfoque de Diseño:** Propuesta C - Narrativa Ejecutiva por Bloques (Storytelling UX)  
**Fecha:** Septiembre de 2026  
**Estatus:** Aprobado para Implementación  

---

## 1. Filosofía de Diseño: "Narrativa Ejecutiva (Storytelling)"

A diferencia de los tableros tradicionales que presentan una cuadrícula plana y estática de tarjetas numéricas, el **Tablero Directivo de ATLAS TecNM** conduce a las autoridades a través de una secuencia lógica de tres preguntas fundamentales:

```
┌────────────────────────────────────────────────────────────────────────┐
│  BLOQUE 1: ¿DÓNDE ESTAMOS PARADOS HOY? (Pulso y Semáforo Trimestral)   │
│  - Estatus del corte activo, días restantes y semáforo de los planteles│
├────────────────────────────────────────────────────────────────────────┤
│  BLOQUE 2: ¿DÓNDE SE CONCENTRA EL IMPACTO? (Cartografía Leaflet.js)    │
│  - Mapa coropléjico de México con interacción y filtros territoriales │
├────────────────────────────────────────────────────────────────────────┤
│  BLOQUE 3: ¿QUIÉNES Y EN QUÉ PARTICIPAN? (Inclusión y Sectores)        │
│  - Paridad de género (M/H), sectores estratégicos y modalidades        │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Compatibilidad Técnica de Herramientas y Mapas

Para garantizar que el mapa y los componentes visuales funcionen fluidamente dentro de **Filament v3**, **Livewire 3** y **Alpine.js**:

| Herramienta | Evaluación de Compatibilidad | Razón de Selección |
| :--- | :--- | :--- |
| **Leaflet.js + ESRI ArcGIS** | **100% Compatible (Oficial)** | Biblioteca estándar para cartografía interactiva en web. Integrada localmente sin dependencias externas. Capa base geográfica real de alta fidelidad vía ESRI ArcGIS Dark Gray Canvas (gratuita, limpia y sin marcas de agua). Permite a futuro geolocalizar con pines los 263 institutos tecnológicos con coordenadas exactas. |
| **GeoJSON de México** | **100% Compatible con INEGI** | Archivo vectorial optimizado de las 32 entidades federativas. Las propiedades `clave_inegi` del GeoJSON mapean de forma exacta con la columna `clave_inegi` de nuestra tabla `cat_entidades` (`01` a `32`). |
| **Tailwind CSS** | **Nativo en Filament v3** | Utiliza las clases de utilidad y la paleta oficial del Manual de Identidad Gráfica TecNM 2026. |

---

## 3. Desglose Estructural de los 3 Bloques

### BLOQUE 1: Pulso Nacional y Semáforo de Cierre Trimestral
* **Banner de Corte Activo:** Muestra el año y trimestre en curso (ej. *2026 - Trimestre 3*), la fecha límite de carga y un distintivo con la cuenta regresiva de días antes del bloqueo automático de la plataforma.
* **Semáforo de Cumplimiento (263 Planteles):**
  * **Al Día / Publicado (`#1E5B4F`):** Planteles que ya cerraron y validaron su reporte en el trimestre (Verde institucional).
  * **En Proceso / Borrador (`#A57F2C`):** Planteles que iniciaron captura pero aún no publican (Dorado institucional).
  * **Sin Reporte / Rezagado (`#9B2247`):** Planteles sin actividad en el corte actual (Guinda institucional).
* **Métricas Clave de Cobertura:**
  * Porcentaje nacional de cumplimiento ($N^\circ \text{ planteles publicados} / 263$).
  * Total consolidado de población participante (Docentes + Alumnos).

### BLOQUE 2: Cartografía y Despliegue Territorial
* **Widget Central del Mapa:** Mapa vectorial de México renderizado con **Leaflet.js** sobre capa base **ESRI ArcGIS Dark Gray Canvas** con escala de calor coropléjica en tonos Azul TecNM (`#1B396A`) y bordes dorados (`#A57F2C`).
* **Interacción:** Al hacer clic en cualquier estado de la República, un evento reactivo Livewire actualiza un panel lateral con:
  * Nombre oficial de la entidad federativa.
  * Número de institutos en ese estado (Federales vs. Descentralizados).
  * Semáforo local de esa entidad.
  * Lista Top 5 interactiva con sincronización bidireccional hacia el mapa.
* **Barra de Filtros Superiores:** Selector de Eje (Todos / MTE / COMEXTRAS) y filtro por tipo de sostenimiento.

### BLOQUE 3: Inclusión, Paridad y Sectores Estratégicos
* **Widget de Equidad de Género:** Visualización comparativa de participación femenina y masculina:
  * En Estudiantes (% Mujeres vs % Hombres).
  * En Docentes (% Mujeres vs % Hombres).
* **Gráfica de Sectores Estratégicos:** Distribución de proyectos reportados en MTE (Agroindustria, Software, Aeroespacial, Energía, etc.).
* **Distribución de COMEXTRAS:** Proporción de participantes en actividades Deportivas, Culturales, Cívicas y Movilidad Académica Internacional.

---

## 4. Tokens de Diseño Institucional Aplicados

* **Fuente:** `Noto Sans` (Google Fonts).
* **Azul TecNM Principal:** `#1B396A` (Pantone 294 C).
* **Dorado TecNM:** `#A57F2C` (Pantone 1255 C).
* **Verde Oficial:** `#1E5B4F` (Pantone 626 C).
* **Guinda Institucional:** `#9B2247` (Pantone 7420 C).
* **Gris de Apoyo:** `#807E82` (Cool Gray 10 C).
