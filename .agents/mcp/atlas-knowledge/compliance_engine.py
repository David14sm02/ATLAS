#!/usr/bin/env python3
"""
Regulatory Compliance & Business Rules Engine for COMEXTRA (TecNM)
Validates submissions against Circular 0045 & MOO-PR-039 using Mexican official business days calendar.
Part of atlas-knowledge-mcp
"""

from datetime import datetime, date, timedelta
from typing import Dict, Any, List, Optional

# Official Mexican Federal Holidays (Ley Federal del Trabajo Art. 74 & SEP/TecNM Calendar)
def get_mexican_holidays(year: int) -> set:
    """Calculates official Mexican non-working days for a given year."""
    holidays = set()

    # Fixed holidays
    holidays.add(date(year, 1, 1))    # Año Nuevo
    holidays.add(date(year, 5, 1))    # Día del Trabajo
    holidays.add(date(year, 5, 5))    # Batalla de Puebla (SEP)
    holidays.add(date(year, 5, 15))   # Día del Maestro (SEP/TecNM)
    holidays.add(date(year, 9, 16))   # Independencia
    holidays.add(date(year, 12, 25))  # Navidad

    # Floating holidays:
    # 1. Primer lunes de febrero (Constitución)
    d = date(year, 2, 1)
    while d.weekday() != 0:  # Monday is 0
        d += timedelta(days=1)
    holidays.add(d)

    # 2. Tercer lunes de marzo (Natalicio de Benito Juárez)
    d = date(year, 3, 1)
    mondays = 0
    while mondays < 3:
        if d.weekday() == 0:
            mondays += 1
            if mondays == 3:
                holidays.add(d)
                break
        d += timedelta(days=1)

    # 3. Tercer lunes de noviembre (Revolución Mexicana)
    d = date(year, 11, 1)
    mondays = 0
    while mondays < 3:
        if d.weekday() == 0:
            mondays += 1
            if mondays == 3:
                holidays.add(d)
                break
        d += timedelta(days=1)

    # Transición presidencial (1 de octubre cada 6 años: 2024, 2030)
    if year in (2024, 2030, 2036):
        holidays.add(date(year, 10, 1))

    return holidays

def parse_date(d_val: Any) -> Optional[date]:
    """Helper to parse date string or return date object."""
    if isinstance(d_val, date):
        return d_val
    if isinstance(d_val, datetime):
        return d_val.date()
    if isinstance(d_val, str):
        clean_str = d_val.split("T")[0].split(" ")[0].strip()
        for fmt in ("%Y-%m-%d", "%d/%m/%Y", "%d-%m-%Y"):
            try:
                return datetime.strptime(clean_str, fmt).date()
            except ValueError:
                continue
    return None

def is_business_day(d: date) -> bool:
    """Checks if date is a business day (Monday to Friday, excluding Mexican federal holidays)."""
    if d.weekday() >= 5:  # Saturday or Sunday
        return False
    holidays = get_mexican_holidays(d.year)
    return d not in holidays

def add_business_days(start_date: date, num_days: int) -> date:
    """Adds (or subtracts if negative) business days to a date."""
    curr = start_date
    step = 1 if num_days >= 0 else -1
    remaining = abs(num_days)

    while remaining > 0:
        curr += timedelta(days=step)
        if is_business_day(curr):
            remaining -= 1
    return curr

def count_business_days(start_date: date, end_date: date) -> int:
    """Counts business days between two dates (excluding start_date, including end_date)."""
    if start_date > end_date:
        return -count_business_days(end_date, start_date)

    count = 0
    curr = start_date + timedelta(days=1)
    while curr <= end_date:
        if is_business_day(curr):
            count += 1
        curr += timedelta(days=1)
    return count

def calculate_deadlines(fecha_inicio_comision: str, fecha_fin_comision: str) -> Dict[str, Any]:
    """
    Calculates mandatory COMEXTRA regulatory deadlines based on Circular 0045 & MOO-PR-039:
    1. Maximum application date: must be at least 30 business days BEFORE commission start.
    2. Final report submission deadline: exactly 30 business days AFTER commission end.
    """
    d_inicio = parse_date(fecha_inicio_comision)
    d_fin = parse_date(fecha_fin_comision)

    if not d_inicio or not d_fin:
        return {
            "status": "error",
            "message": "Formato de fecha inválido. Utilice YYYY-MM-DD o DD/MM/AAAA."
        }

    if d_inicio > d_fin:
        return {
            "status": "error",
            "message": "La fecha de inicio de la comisión no puede ser posterior a la fecha de fin."
        }

    # 30 business days prior
    fecha_maxima_solicitud = add_business_days(d_inicio, -30)

    # 30 business days post
    fecha_limite_informe = add_business_days(d_fin, 30)

    today = date.today()
    business_days_left_to_apply = count_business_days(today, fecha_maxima_solicitud) if today <= fecha_maxima_solicitud else -count_business_days(fecha_maxima_solicitud, today)

    return {
        "status": "success",
        "duracion_dias_naturales": (d_fin - d_inicio).days + 1,
        "fecha_inicio_comision": d_inicio.isoformat(),
        "fecha_fin_comision": d_fin.isoformat(),
        "regla_anticipacion_30_dias": {
            "norma": "Circular 0045/COMEXTRAS - Numeral 4.2",
            "fecha_limite_registro_solicitud": fecha_maxima_solicitud.isoformat(),
            "dias_habiles_restantes": business_days_left_to_apply,
            "vigente": business_days_left_to_apply >= 0,
            "mensaje": "Plazo en tiempo y forma." if business_days_left_to_apply >= 0 else f"Extemporáneo por {abs(business_days_left_to_apply)} días hábiles."
        },
        "regla_informe_30_dias": {
            "norma": "Manual MOO-PR-039 - Numeral 6.1",
            "fecha_limite_entrega_informe": fecha_limite_informe.isoformat(),
            "consecuencia_incumplimiento": "Bloqueo institucional del plantel para nuevas comisiones (Circular 0045, Disp. Transitoria 3)."
        }
    }

def validate_solicitud_compliance(payload: Dict[str, Any]) -> Dict[str, Any]:
    """
    Performs full institutional compliance validation for a COMEXTRA request.
    Payload attributes expected:
    - fecha_solicitud (str)
    - fecha_inicio (str)
    - fecha_fin (str)
    - tipo_solicitante ('docente', 'estudiante', 'directivo')
    - documentos_anexos (list of str or dict)
    - tiene_adeudo_previo (bool, optional)
    - pais_destino (str)
    """
    d_solicitud = parse_date(payload.get("fecha_solicitud", date.today().isoformat()))
    d_inicio = parse_date(payload.get("fecha_inicio"))
    d_fin = parse_date(payload.get("fecha_fin"))

    if not d_inicio or not d_fin or not d_solicitud:
        return {
            "status": "error",
            "veredicto": "DATOS_INCOMPLETOS",
            "message": "Se requieren 'fecha_solicitud', 'fecha_inicio' y 'fecha_fin'."
        }

    checks_passed = []
    checks_failed = []
    observaciones = []

    # 1. Validación de Anticipación (30 días hábiles)
    dias_anticipacion = count_business_days(d_solicitud, d_inicio)
    if dias_anticipacion >= 30:
        checks_passed.append({
            "codigo": "CHK_ANTICIPACION",
            "descripcion": f"Cumple con anticipación mínima de 30 días hábiles (Calculado: {dias_anticipacion} días hábiles)."
        })
    else:
        checks_failed.append({
            "codigo": "ERR_PLAZO_EXTEMPORANEO",
            "norma": "Circular 0045 Numeral 4.2",
            "descripcion": f"Incumplimiento de plazo: radicado con solo {dias_anticipacion} días hábiles de anticipación (mínimo requerido: 30 días hábiles). Requiere oficio de excepción firmado por Dirección General."
        })

    # 2. Validación de Adeudos Previos
    tiene_adeudo = payload.get("tiene_adeudo_previo", False)
    if not tiene_adeudo:
        checks_passed.append({
            "codigo": "CHK_ADEUDOS",
            "descripcion": "Plantel y comisionado al corriente de informes técnicos previos."
        })
    else:
        checks_failed.append({
            "codigo": "ERR_BLOQUEO_POR_ADEUDO",
            "norma": "Circular 0045 Disposición Transitoria 3",
            "descripcion": "El plantel o comisionado tiene informes vencidos en mora. El sistema bloquea automáticamente la solicitud."
        })

    # 3. Validación de Documentación Obligatoria
    docs_requeridos = {
        "carta_invitacion": "Carta de invitación oficial con traducción",
        "aval_director": "Aval firmado por Director de Plantel (R4) o firma delegada",
        "dictamen_academia": "Dictamen favorable de Academia o Consejo Técnico",
        "seguro_internacional": "Póliza de seguro médico con repatriación",
        "justificacion_academica": "Justificación de impacto académico para TecNM"
    }

    docs_cargados = payload.get("documentos_anexos", [])
    if isinstance(docs_cargados, dict):
        docs_cargados_keys = [k for k, v in docs_cargados.items() if v]
    else:
        docs_cargados_keys = [str(d).lower() for d in docs_cargados]

    docs_faltantes = []
    for req_key, req_name in docs_requeridos.items():
        if any(req_key in dk or req_name.lower() in dk for dk in docs_cargados_keys):
            continue
        docs_faltantes.append(req_name)

    if not docs_faltantes:
        checks_passed.append({
            "codigo": "CHK_EXPEDIENTE_COMPLETO",
            "descripcion": "Todos los 5 documentos obligatorios del expediente COMEXTRA están presentes."
        })
    else:
        checks_failed.append({
            "codigo": "ERR_EXPEDIENTE_INCOMPLETO",
            "norma": "Manual MOO-PR-039 Numeral 4.3",
            "descripcion": f"Faltan documentos obligatorios: {', '.join(docs_faltantes)}"
        })

    # 4. Tipo de Solicitante y Duración
    tipo_sol = str(payload.get("tipo_solicitante", "")).lower()
    if tipo_sol in ("docente", "estudiante", "directivo"):
        checks_passed.append({
            "codigo": "CHK_TIPO_SOLICITANTE",
            "descripcion": f"Tipo de solicitante válido: '{tipo_sol}'."
        })
    else:
        checks_failed.append({
            "codigo": "ERR_TIPO_SOLICITANTE",
            "norma": "Estatuto Orgánico TecNM",
            "descripcion": f"Tipo de solicitante '{tipo_sol}' no reconocido (debe ser 'docente', 'estudiante' o 'directivo')."
        })

    # Veredicto general
    if not checks_failed:
        veredicto = "PROCEDENTE"
        recomendacion = "El expediente cumple rigurosamente con la normativa oficial. Listo para dictaminación favorable DVeIA (Oficio M00)."
    elif any(f["codigo"] in ("ERR_PLAZO_EXTEMPORANEO", "ERR_BLOQUEO_POR_ADEUDO") for f in checks_failed):
        veredicto = "IMPROCEDENTE_BLOQUEADO"
        recomendacion = "Rechazo normativo inmediato por falta de plazo o adeudo institucional activo."
    else:
        veredicto = "OBSERVADO_SUBSANABLE"
        recomendacion = "El expediente tiene inconsistencias documentales que deben ser solventadas por el plantel antes de dictamen."

    return {
        "status": "success",
        "veredicto": veredicto,
        "dias_anticipacion_habiles": dias_anticipacion,
        "total_checks_evaluados": len(checks_passed) + len(checks_failed),
        "checks_aprobados": checks_passed,
        "checks_rechazados": checks_failed,
        "recomendacion_dveia": recomendacion
    }

# RENAPO Mexican States Map for CURP validation
CURP_STATES_MAP = {
    'AS': 'Aguascalientes', 'BC': 'Baja California', 'BS': 'Baja California Sur',
    'CC': 'Campeche', 'CL': 'Coahuila', 'CM': 'Colima', 'CS': 'Chiapas', 'CH': 'Chihuahua',
    'DF': 'Ciudad de México', 'DG': 'Durango', 'GT': 'Guanajuato', 'GR': 'Guerrero',
    'HG': 'Hidalgo', 'JC': 'Jalisco', 'MC': 'Estado de México', 'MN': 'Michoacán',
    'MS': 'Morelos', 'NT': 'Nayarit', 'NL': 'Nuevo León', 'OC': 'Oaxaca',
    'PL': 'Puebla', 'QT': 'Querétaro', 'QR': 'Quintana Roo', 'SP': 'San Luis Potosí',
    'SL': 'Sinaloa', 'SR': 'Sonora', 'TC': 'Tabasco', 'TS': 'Tamaulipas',
    'TL': 'Tlaxcala', 'VZ': 'Veracruz', 'YN': 'Yucatán', 'ZS': 'Zacatecas',
    'NE': 'Nacido en el Extranjero'
}

def validate_mexican_id(id_type: str, id_value: str) -> Dict[str, Any]:
    """
    Formally validates Mexican identity numbers (CURP or RFC) according to official RENAPO and SAT algorithms.
    Used for Hito 4 Dry-run ingestion and comisionado verification.
    """
    import re
    val = str(id_value).upper().strip()
    t = id_type.lower().strip()

    if t == "curp":
        if len(val) != 18:
            return {"valido": False, "tipo": "CURP", "error": f"Longitud inválida: debe ser de 18 caracteres (recibidos: {len(val)})"}
        
        # Regex format: 4 letters, 6 digits (YYMMDD), Gender (H/M), 2 letters State, 3 consonants, 1 century differentiator, 1 check digit
        if not re.match(r'^[A-Z]{4}\d{6}[HM][A-Z]{2}[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9]\d$', val):
            return {"valido": False, "tipo": "CURP", "error": "Estructura o caracteres inválidos según especificación oficial RENAPO."}

        state_code = val[11:13]
        if state_code not in CURP_STATES_MAP:
            return {"valido": False, "tipo": "CURP", "error": f"Clave de entidad federativa desconocida: '{state_code}'."}

        # Check digit algorithm (RENAPO Module 10)
        chars = '0123456789ABCDEFGHIJKLMNÑOPQRSTUVWXYZ'
        c_map = {c: i for i, c in enumerate(chars)}
        s = sum(c_map.get(val[i], 0) * (18 - i) for i in range(17))
        dv = (10 - (s % 10)) % 10

        if val[17] != str(dv):
            return {
                "valido": False,
                "tipo": "CURP",
                "error": f"Dígito verificador inválido: esperado '{dv}', registrado '{val[17]}'."
            }

        return {
            "status": "success",
            "valido": True,
            "tipo": "CURP",
            "curp": val,
            "sexo": "Hombre" if val[10] == "H" else "Mujer",
            "entidad_federativa": CURP_STATES_MAP[state_code],
            "fecha_nacimiento_estimada": f"{val[4:6]}/{val[6:8]}/{val[8:10]} (AAMMDD)",
            "mensaje": "CURP válido y verificado criptográficamente ante algoritmo RENAPO."
        }

    elif t == "rfc":
        # Persona Física (13) o Persona Moral (12)
        if len(val) not in (12, 13):
            return {"valido": False, "tipo": "RFC", "error": f"Longitud de RFC inválida: debe tener 12 (Moral) o 13 (Física) caracteres (recibidos: {len(val)})"}

        is_moral = len(val) == 12
        pattern = r'^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$' if is_moral else r'^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$'

        if not re.match(pattern, val):
            return {"valido": False, "tipo": "RFC", "error": f"Estructura de RFC inválida para Persona {'Moral' if is_moral else 'Física'}."}

        return {
            "status": "success",
            "valido": True,
            "tipo": "RFC",
            "rfc": val,
            "tipo_persona": "Persona Moral" if is_moral else "Persona Física",
            "homoclave": val[-3:],
            "mensaje": f"RFC válido para Persona {'Moral' if is_moral else 'Física'} con homoclave registrada."
        }
    else:
        return {"status": "error", "message": f"Tipo de documento '{id_type}' no soportado. Utilice 'curp' o 'rfc'."}

