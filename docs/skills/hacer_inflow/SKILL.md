# Skill: Hacer Inflow (Registro de Ingresos)

## Descripción

Esta skill permite registrar un ingreso de dinero en el sistema, distribuyendo el monto entre uno o varios depósitos según los porcentajes que el usuario defina.

## Flujo Principal

### 1. Obtener Tipos de Ingreso

Usa la tool `get_inflow_types` para obtener los tipos de ingreso disponibles.

```php
get_inflow_types(idUser: 1)
```

Muestra los resultados al usuario y solicita que seleccione el tipo de ingreso.

### 2. Obtener Depósitos Disponibles

Usa la tool `get_available_by_deposits` para obtener los depósitos activos.

```php
get_available_by_deposits(idUser: 1)
```

De esta lista, solo necesitas el `id_porcent` y el `name` para mostrar al usuario.

### 3. Escenarios Predefinidos

Pregunta al usuario qué escenario desea usar:

**Escenario 1: Salario sin bono de alimentación**
El usuario recibe su salario mensual. Por defecto, propone:
- idInflowType: 1 (Salario)
- Porcentajes predefinidos:
  - Diversión: 10%
  - Ahorro: 10%
  - Gastos en general: 20%
  - Inversión: 56%
  - Donativos: 1%
  - Educación: 1%
  - Comida con Grays & Mercado: 1%
  - Iniciativa finca: 1%

El usuario puede ajustar estos porcentajes si lo desea.

**Escenario 2: Bono de alimentación**
El usuario recibe un bono de alimentación que va 100% a "Comida con Grays & Mercado".

### 4. Solicitar Datos al Usuario

Pregunta al usuario de forma explícita:

1. **Tipo de ingreso**: Seleccionado del paso 1
2. **Monto total**: Cantidad de dinero recibida
3. **Descripción**: Descripción del ingreso (OBLIGATORIO preguntar explícitamente)
4. **Fecha**: Fecha del ingreso
   - Colombia está en zona horaria America/Bogota (UTC-5)
   - Formato: YYYY-MM-DD
   - Por defecto, usar la fecha actual
5. **Depósitos y porcentajes**:
   - Mostrar lista de depósitos disponibles
   - Preguntar cuáles desea elegir
   - Para cada uno, preguntar qué porcentaje desea asignar
   - La suma DEBE ser igual a 100%
   - Validar que la suma sea 100% antes de proceder

### 5. Ejecutar el Ingreso

Usa la tool `inflow_money` para registrar el ingreso.

```php
inflow_money(
  idInflowType: <id seleccionado>,
  total: <monto>,
  porcents: [
    ['idPorcent' => <id>, 'porcent' => <porcentaje>],
    // ... más depósitos si es necesario
  ],
  setDate: '<fecha>',
  description: '<descripción>',
  idUser: 1,
  dryRun: false
)
```

## Reglas de Negocio

| # | Regla | Descripción |
|---|-------|-------------|
| 1 | Suma de porcentajes | La suma de todos los porcentajes debe ser exactamente 100% |
| 2 | Al menos un depósito | Se debe seleccionar al menos un depósito |
| 3 | Monto mayor a 0 | El monto total debe ser mayor a 0 |
| 4 | Descripción requerida | Siempre preguntar explícitamente la descripción |
| 5 | Fecha en Colombia | Usar zona horaria America/Bogota |
| 6 | Transacción atómica | Si falla algo, se revierte todo el ingreso |

## Ejemplo de Conversación

```
Usuario: Quiero registrar un ingreso

Asistente: Perfecto, voy a ayudarte a registrar un ingreso. Primero, necesito algunos datos.

¿Qué tipo de ingreso deseas registrar? Aquí están los disponibles:
1. Salario
2. Inversión
3. Freelance
... (mostrar resultados de get_inflow_types)

Usuario: Es un salario

Asistente: ¿Qué escenario deseas usar?
1. Salario (con distribución predefinida de porcentajes)
2. Bono de alimentación (100% a Comida con Grays & Mercado)
3. Personalizado (tú eliges los depósitos y porcentajes)

Usuario: Opción 1

Asistente: Perfecto, tienes estos depósitos disponibles:
1. Ahorro
2. Gastos Generales
3. Inversión
4. Donativos
5. Educación
6. Comida con Grays & Mercado
7. Iniciativa Finca
8. Diversión

Para el escenario de Salario, estos son los porcentajes sugeridos:
- Diversión: 10%
- Ahorro: 10%
- Gastos en general: 20%
- Inversión: 56%
- Donativos: 1%
- Educación: 1%
- Comida con Grays & Mercado: 1%
- Iniciativa finca: 1%

¿Deseas ajustar estos porcentajes o usar los que te sugiero?

Usuario: Está bien así

Asistente: ¿Cuál es el monto total del ingreso?

Usuario: 5000000

Asistente: Agrega una descripción por favor

Usuario: Salario febrero 2026

Asistente: ¿Cuál es la fecha del ingreso? (formato YYYY-MM-DD)

Usuario: 2026-03-15

Asistente: Voy a registrar el ingreso con estos datos:
- Tipo: Salario
- Monto: 5,000,000
- Fecha: 2026-03-15
- Descripción: Salario febrero 2026
- Distribución:
  - Ahorro: 10% ($500,000)
  - Gastos Generales: 20% ($1,000,000)
  - Inversión: 56% ($2,800,000)
  - Donativos: 1% ($50,000)
  - Educación: 1% ($50,000)
  - Comida con Grays & Mercado: 1% ($50,000)
  - Iniciativa Finca: 1% ($50,000)
  - Diversión: 10% ($500,000)

Procedo a registrarlo...

(llama a inflow_money con los datos)
```

## Código de Ejemplo

```php
// 1. Obtener tipos de ingreso
$tipos = get_inflow_types(idUser: 1);

// 2. Obtener depósitos
$depositos = get_available_by_deposits(idUser: 1);

// 3. Escenarios predefinidos
$escenarioSalario = [
    'Diversión' => 10,
    'Ahorro' => 10,
    'Gastos en general' => 20,
    'Inversión' => 56,
    'Donativos' => 1,
    'Educación' => 1,
    'Comida con Grays & Mercado' => 1,
    'Iniciativa finca' => 1,
];

// 4. Validar porcentajes
$sumaPorcentajes = array_sum($escenarioSalario);
// Debe ser 100

// 5. Registrar ingreso
inflow_money(
    idInflowType: 1, // Salario
    total: 5000000,
    porcents: [
        ['idPorcent' => 1, 'porcent' => 10],
        ['idPorcent' => 2, 'porcent' => 10],
        // ...
    ],
    setDate: '2026-03-15',
    description: 'Salario febrero 2026',
    idUser: 1,
    dryRun: false
);
```

## Notas

- Siempre confirmar los datos antes de ejecutar el ingreso
- Si el usuario cambia los porcentajes, validar que sumen 100%
- La descripción es OBLIGATORIA y debe preguntarse explícitamente
- Usar fecha actual si el usuario no especifica otra
- Colombia está en UTC-5 (America/Bogota)
