# 🗺️ Documentación de Estructuras JSON para Endpoints de la API Geo-Recolección

Este documento describe la estructura del payload (`JSON`) requerido para interactuar correctamente con los principales endpoints de la API.

---

## 1. Vehículos (`POST /api/vehiculos`)

Se utiliza para registrar un nuevo vehículo en el sistema.

| Campo | Tipo | Requerido | Descripción |
|-------|------|------------|-------------|
| `placa` | string | ✅ Sí | Placa única del vehículo (ej., `CAB-999`). |
| `marca` | string | ✅ Sí | Marca del vehículo. |
| `modelo` | string | ✅ Sí | Año o descripción del modelo. |
| `activo` | boolean | ✅ Sí | Indica si el vehículo está operativo. |
| `perfil_id` | UUID | ✅ Sí | ID del perfil que registra el vehículo. |

### Ejemplo JSON

```json
{
  "placa": "CAB-999",
  "marca": "SUZUKI",
  "modelo": "1990",
  "activo": true,
  "perfil_id": "18851282-1a08-42b7-9384-243cc2ead349"
}
```

---

## 2. Rutas (`POST /api/rutas`)

Permite crear una ruta geográfica.  
Solo se debe enviar **`shape` (geometría directa)** o **`calles_ids` (unión de segmentos)**.

| Campo | Tipo | Requerido | Descripción |
|-------|------|------------|-------------|
| `nombre_ruta` | string | ✅ Sí | Nombre asignado a la nueva ruta. |
| `perfil_id` | UUID | ✅ Sí | ID del perfil creador de la ruta. |
| `shape` | object | ⚪ No (si `calles_ids` es nulo) | Geometría de la ruta en formato `GeoJSON` (`LineString`). |
| `calles_ids` | array de UUID | ⚪ No (si `shape` es nulo) | Lista ordenada de `UUIDs` de calles a unir (usando `ST_Union`). |

---

### 🅰️ Caso A: Crear Ruta por Unión de Calles

```json
{
  "nombre_ruta": "Ruta ejemplo 1 (Unión)",
  "perfil_id": "18851282-1a08-42b7-9384-243cc2ead349",
  "calles_ids": [
    "e814077c-b38b-442d-ae63-b1be256a1c03",
    "7dcf5142-f4b2-46e2-9518-5bd7e9bd2a27"
  ]
}
```

---

### 🅱️ Caso B: Crear Ruta con Geometría Directa  

> **Nota:** El valor de `shape` debe ser un objeto `GeoJSON` válido.

```json
{
  "nombre_ruta": "Ruta Solo Geometría",
  "perfil_id": "18851282-1a08-42b7-9384-243cc2ead349",
  "shape": {
    "type": "LineString",
    "coordinates": [
      [-77.0782, 3.8898],
      [-77.0605, 3.8828]
    ]
  }
}
```

---

## 3. Recorridos y Posiciones 📍

### 3.1. Iniciar Recorrido (`POST /api/recorridos/iniciar`)

Registra el inicio de una operación.

| Campo | Tipo | Requerido | Descripción |
|-------|------|------------|-------------|
| `ruta_id` | UUID | ✅ Sí | ID de la ruta planificada. |
| `vehiculo_id` | UUID | ✅ Sí | ID del vehículo asignado. |
| `perfil_id` | UUID | ✅ Sí | ID del conductor que inicia el recorrido. |

#### Ejemplo JSON

```json
{
  "ruta_id": "XXXXX-XXXX-XXXX-XXXX-XXXXXXXX",
  "vehiculo_id": "XXXXX-XXXX-XXXX-XXXX-XXXXXXXX",
  "perfil_id": "18851282-1a08-42b7-9384-243cc2ead349"
}
```

---

### 3.2. Registrar Posición (`POST /api/recorridos/{recorrido_id}/posiciones`)

Registra una coordenada de GPS en un recorrido activo.  
El `UUID` del recorrido se pasa en la **URL**.

| Campo | Tipo | Requerido | Descripción |
|-------|------|------------|-------------|
| `lat` | decimal | ✅ Sí | Latitud de la posición. |
| `lon` | decimal | ✅ Sí | Longitud de la posición. |
| `perfil_id` | UUID | ✅ Sí | ID del perfil que envía la coordenada. |

#### Ejemplo JSON

```json
{
  "lat": 3.42158,
  "lon": -76.5205,
  "perfil_id": "3fa85f64-5717-4562-b3fc-2c963f66afa6"
}
```

---

📘 **Autor:** Equipo de Desarrollo Geo-Recolección  
📅 **Última actualización:** Octubre 2025
