<?php
$jsonPath = __DIR__ . '/storage/app/analisis_laboratorio.json';
$data = json_decode(file_get_contents($jsonPath), true);

$data['analisis'][] = [
    "id" => "proteinuria_24_horas",
    "nombre_oficial" => "Proteinuria de 24 horas",
    "alias" => ["Proteinuria", "Proteínas en orina de 24 horas"],
    "palabras_clave" => ["proteinuria", "proteinas en orina", "orina de 24 horas", "24 horas"],
    "ayuno" => ["requiere" => false, "horas" => null, "aclaracion" => ""],
    "requiere_insumo_externo" => [
        "requiere" => true,
        "descripcion" => "Recipiente limpio para orina de 24 hs (por ejemplo, bidón de agua destilada vacío)."
    ],
    "horario_extraccion" => "Lunes a viernes de 7:30 a 9:30 hs",
    "dias_disponibles" => ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes"],
    "duracion_estudio" => "24 horas",
    "tipo_muestra" => "Orina de 24 horas",
    "indicaciones_generales" => "Recolectar la orina de 24 hs en un recipiente perfectamente limpio. Eliminar la primera orina de la mañana y juntar todas las demás, incluida la primera del día siguiente.",
    "indicaciones_especificas" => "Mantené el recipiente que contiene la muestra en un lugar fresco (preferentemente en heladera).",
    "instrucciones_por_paciente" => [],
    "tiempo_entrega" => null
];

$data['analisis'][] = [
    "id" => "coproparasitologico",
    "nombre_oficial" => "Coproparasitológico",
    "alias" => ["Parasitológico", "Estudio de parásitos en heces"],
    "palabras_clave" => ["coproparasitologico", "parasitologico", "parasitos", "materia fecal", "heces"],
    "ayuno" => ["requiere" => false, "horas" => null, "aclaracion" => ""],
    "requiere_insumo_externo" => [
        "requiere" => true,
        "descripcion" => "Frasco estéril para materia fecal."
    ],
    "horario_extraccion" => "Lunes a viernes de 7:30 a 9:30 hs",
    "dias_disponibles" => ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes"],
    "duracion_estudio" => "Varía según prescripción (puede requerir recolección seriada de varios días)",
    "tipo_muestra" => "Materia fecal",
    "indicaciones_generales" => "Recolectar una muestra de materia fecal en un frasco estéril.",
    "indicaciones_especificas" => "Evitar que la muestra se contamine con orina.",
    "instrucciones_por_paciente" => [],
    "tiempo_entrega" => null
];

file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Added new analyses to JSON.";
