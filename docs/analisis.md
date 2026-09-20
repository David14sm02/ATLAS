# Especificación Técnica y Arquitectura del Proyecto: PINT (TecNM)

**Proyecto:** Plataforma Integral Nacional del TecNM (PINT)[cite: 2]  
**Equipo Responsable:** Fábrica de Software  
**Fecha:** Septiembre de 2026[cite: 2]  
**Estatus:** Especificación de Arquitectura y Plan de Desarrollo para MVP[cite: 2]  

---

## 1. Alcance y Contexto Institucional

El proyecto **PINT** surge para modernizar la recolección, consolidación y explotación de información de los **263 institutos y centros del TecNM**[cite: 2]. El objetivo es reemplazar la circulación dispersa de archivos en hoja de cálculo (formatos como el `F.37` y `F.32`) por una plataforma web segura y analítica con tableros interactivos, mapas coropléjicos de la República Mexicana y semáforos de cumplimiento trimestral[cite: 1, 2].

### Alcance del MVP (Ventana de 7 Semanas)
Para mitigar riesgos y asegurar la entrega funcional hacia noviembre, el MVP se restringe al área de **Vinculación**[cite: 2, 3], implementando la infraestructura global del sistema y dos submódulos operativos[cite: 2]:
1. **MTE**[cite: 2]
2. **Intercambio y COMEXTRAS**[cite: 2]

El resto de los módulos y submódulos quedarán estructurados en código y base de datos en modo *placeholder* o listos para fases posteriores[cite: 2].

---

## 2. Diagnóstico de Datos y Modelo de Negocio

A partir del análisis de las plantillas de Excel operativas:
* **Datos consolidados por plantel:** Los formatos no procesan listas nominales de alumnos con CURP o datos sensibles individuales[cite: 1]. Se reportan conteos numéricos agregados por instituto[cite: 1].
* **Dimensiones clave:** Entidad federativa, nombre oficial del plantel y tipo de sostenimiento (Federal vs. Descentralizado)[cite: 1].
* **Métricas de captura:** Cuadrantes de participación divididos por rol y género:
  * Docentes Participantes: Mujeres / Hombres[cite: 1].
  * Estudiantes Participantes: Mujeres / Hombres[cite: 1].
* **Periodicidad:** Cortes trimestrales uniformes (Q1 a Q4)[cite: 1].
* **Volumen ligero:** 263 planteles reportando en cortes trimestrales generan pocos miles de filas al año[cite: 1, 2], lo que hace innecesario el uso de motores NoSQL como MongoDB y posiciona a una base relacional como la solución ideal.

---

## 3. Arquitectura del Ecosistema de Vinculación

El sistema se estructura jerárquicamente a partir de los **4 Ejes Estratégicos** institucionales y sus respectivos submódulos[cite: 3, 4]:


┌──────────────────────────────────────────────────────────────────────────┐
│                              [ FACTOR CRÍTICO ]                          │
│          Fortalecimiento del Ecosistema de Vinculación del TecNM         │
└──────────────────────────────────────────────────────────────────────────┘
                                          │
                     ┌────────────────────┼────────────────────┐
                     ▼                    ▼                    ▼
                     │                    │                    │
┌──────────────────────┐   ┌──────────────────────┐   ┌──────────────────────┐
│1. VINCULACIÓN        │   │2. INNOVACIÓN Y       │   │3. INTERCAMBIO        │
│   ESTRATÉGICA        │   │   EMPRENDIMIENTO     │   │   ACADÉMICO, CULTURAL│
│                      │   │   COMUNITARIO        │   │   Y DEPORTIVO        │
├──────────────────────┤   └──────────────────────┘   ├──────────────────────┤
│1.1 Consejos Vinc.    │                              │• Intercambio Nacional│
│1.2 Convenios         │                              │• Intercambio Inter.  │
│1.3 Seg. Egresados/BT │                              │• COMEXTRAS (18 acts.)│
│1.4 Proy. Estratégicos│                              │• MTE                 │
│1.5 Semiconductores   │                              └──────────────────────┘
│1.6 Vinc. Sectores    │                                          │
│1.7 Indicadores Cal.  │                                          ▼
│1.8 Marco Normativo   │                              ┌──────────────────────┐
└──────────────────────┘                              │4. EXTENSIÓN          │
                                                      └──────────────────────┘


---

## 4. Diseño de Base de Datos (PostgreSQL en Neon Serverless)

La base de datos se alojará temporalmente en **Neon** (PostgreSQL 16) bajo un esquema estrella administrado íntegramente mediante **Laravel Migrations**.

┌──────────────────────┐          ┌──────────────────────┐
│    cat_entidades     │          │     cat_periodos     │
├──────────────────────┤          ├──────────────────────┤
│ id (PK)              │          │ id (PK)              │
│ clave_inegi          │          │ anio, trimestre (UQ) │
│ nombre               │          │ bloqueado (bool)     │
└──────────┬───────────┘          └──────────┬───────────┘
           │ 1:N                             │ 1:N
           ▼                                 │
┌──────────────────────┐                     │
│    cat_planteles     │                     │
├──────────────────────┤                     │
│ id (PK)              │                     │
│ clave_tecnm (UQ)     │                     │
│ nombre, sostenimiento│                     │
│ latitud, longitud    │                     │
└──────────┬───────────┘                     │
           │ 1:N                             │
           └───────────────-- ┌──────────────--┘
           ▼                ▼
┌─────────────────────────────────┐
│    rep_intercambio_nacional     │
├─────────────────────────────────┤
│ id (BIGPK)                      │
│ plantel_id (FK), periodo_id (FK)│
│ docentes_mujeres, hombres       │
│ estudiantes_mujeres, hombres    │
│ total_docentes (GENERATED)      │
│ total_estudiantes (GENERATED)   │
│ total_general (GENERATED)       │
│ detalles_adicionales (JSONB)    │
│ estatus_reporte (ENUM)          │
└─────────────────────────────────┘


### Reglas de Diseño del Modelo
1. **Catálogos Maestros:**
   * `cat_planteles`: Contiene los 263 institutos con clave oficial, municipio, sostenimiento y coordenadas para georreferenciación[cite: 1, 2].
   * `cat_periodos`: Controla las ventanas trimestrales de captura y bandera de congelamiento/bloqueo de datos.
2. **Columnas Generadas (`STORED`):**
   * Las sumas cruzadas por género y totales por fila se delegan al motor (`GENERATED ALWAYS AS (hombres + mujeres) STORED`), eliminando inconsistencias matemáticas provenientes de los Excels[cite: 1].
3. **Columna `detalles_adicionales` (JSONB):**
   * Almacena particularidades de submódulos variables (como los 18 tipos de actividades de COMEXTRAS o datos de semiconductores) indexados con GIN, sin requerir migraciones estructurales adicionales[cite: 2, 4].

---

## 5. Arquitectura de Software y Roles (Laravel + Filament v3)

* **Multi-Tenancy por Plantel:** Cada uno de los 263 planteles opera como un Tenant independiente[cite: 2]. El usuario local solo puede interactuar con los registros de su instituto[cite: 2]. La Dirección General opera sin restricciones de tenant para visualizar el consolidado nacional[cite: 2].
* **Clusters de Filament:** El código se organiza en `app/Filament/Clusters/` replicando los 4 ejes[cite: 3]:
  * `VinculacionEstrategica/`[cite: 3]
  * `InnovacionEmprendimiento/`[cite: 3]
  * `IntercambioAcademico/` (alojará los recursos funcionales del MVP: `MteResource` y `ComextrasResource`)[cite: 2, 3]
  * `Extension/`[cite: 3]
* **Visualización Geográfica:** Uso de **Apache ECharts** integrado en un Custom Widget de Filament para renderizar el mapa vectorial de México con respuesta inmediata a eventos de clic por estado.

### Matriz de Roles (4 Niveles / 6 Perfiles)
* **Nivel 1 (Nacional):** `R1 Super Administrador` (acceso absoluto) y `R2 Analista Nacional` (solo lectura global y reportes)[cite: 2].
* **Nivel 2 (Regional):** `R3 Coordinador Regional` (alcance por zona geográfica)[cite: 2].
* **Nivel 3 (Plantel):** `R4 Administrador de Plantel` (enlace del instituto) y `R5 Operador de Módulo` (capturista asignado a MTE o COMEXTRAS)[cite: 2].
* **Nivel 4 (Soporte):** `R6 Soporte Técnico` (DevOps/Fábrica de Software)[cite: 2].

> **Estrategia MVP:** Para no demorar la entrega de 7 semanas en aprobaciones burocráticas complejas, se inicia con un flujo de dos estados (`Borrador` $\rightarrow$ `Publicado`)[cite: 2]. El ciclo formal de 4 etapas (`Borrador` $\rightarrow$ `Enviado` $\rightarrow$ `En Revisión` $\rightarrow$ `Validado`) se activará en la Fase 2[cite: 2].

---

## 6. Entorno de Ingeniería, Estandarización y Herramientas

Para garantizar que el equipo de 4 desarrolladores avance coordinado y sin conflictos de integración:

1. **Alojamiento y Gobernanza:**
   * Repositorio bajo una **Organización Privada de GitHub**[cite: 2].
   * Modelo de ramas: `main` (producción) $\leftarrow$ `develop` (integración) $\leftarrow$ `feature/[modulo]` (desarrollo individual)[cite: 2].
   * Ramas protegidas: Prohibido el push directo; integración obligatoria por Pull Request con revisión de código[cite: 2].
2. **Entorno Docker Homogéneo:**
   * Uso estricto de **Laravel Sail** (PHP 8.3, PostgreSQL 16, Redis y Mailpit)[cite: 2].
3. **Pipeline de Integración Continua (CI/CD):**
   * Configuración de `.github/workflows/ci.yml` para validar estilo de código con **Laravel Pint** y correr la suite de pruebas unitarias/integración (**Pest/PHPUnit**) en cada PR[cite: 2].
4. **Aceleradores de Desarrollo e IA:**
   * Uso de **MCP (Model Context Protocol)** conectado a Neon/PostgreSQL local para validación asistida de esquemas.
   * Archivo de reglas de contexto (`.cursorrules` / prompts estandarizados) para generar recursos y validadores bajo los mismos patrones de diseño.

---

## 7. Plan de Trabajo por Semanas (Hacia Noviembre)

* **Semana 1:** Repositorio base, Laravel Sail, conexión a Neon y *seeders* con los 263 planteles y periodos[cite: 2].
* **Semanas 2 y 3:** Migraciones, modelos y recursos de Filament para `MTE` y `COMEXTRAS` con Multi-Tenancy y Shield[cite: 2].
* **Semana 4:** Ingesta asíncrona de archivos Excel con validación en segundo plano (*dry-run*) y manejo de errores[cite: 2].
* **Semana 5:** Construcción del tablero directivo nacional: tarjetas KPI y widget del mapa interactivo con Apache ECharts[cite: 2].
* **Semana 6:** Pruebas de integración, estabilización y ajustes de permisos[cite: 2].
* **Semana 7:** Despliegue en entorno productivo y entrega de documentación operativa[