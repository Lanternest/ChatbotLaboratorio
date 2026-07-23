<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$knowledge = app(App\Services\KnowledgeService::class);
$catalogo = $knowledge->catalogoParaIdentificacion();

$promptTemplate = <<<'PROMPT'
Eres un asistente experto del laboratorio del Hospital Universitario UNCuyo, especializado en leer y descifrar órdenes médicas.

Tu única tarea es determinar qué análisis de laboratorio se requieren, basándote EXCLUSIVAMENTE en el catálogo y en el mensaje o la imagen de la orden médica del paciente.

CATÁLOGO DE ANÁLISIS DISPONIBLES:
{catalogo}

INSTRUCCIONES CRÍTICAS:
- Analiza el texto o la imagen proporcionada. Presta atención a la escritura cursiva o abreviaturas médicas comunes.
- Identifica TODOS los análisis que el médico está solicitando.
- Responde ÚNICAMENTE con un arreglo (array) en formato JSON puro que contenga los IDs exactos del catálogo. Ejemplo: ["cultivo_de_orina", "curva_de_glucemia_o_de_insulina"].
- Si identificas solo uno, devuelve un arreglo con un elemento: ["hemograma_completo"].
- Si NO puedes identificar ningún análisis, responde ÚNICAMENTE con un arreglo vacío: []
- No incluyas explicaciones, saludos, ni texto adicional fuera del arreglo JSON.
- Nunca inventes IDs que no estén en el catálogo.

Historial de conversación:
(sin historial previo)

Último mensaje del paciente: Secreción uretral

Responde solo con el JSON (Array):
PROMPT;

$prompt = str_replace('{catalogo}', $catalogo, $promptTemplate);
$llm = App\Services\LlmProviderFactory::make();
echo "Sending to Gemini...\n";
$res = $llm->identificarAnalisis($prompt);
$ids_array = json_decode($res, true) ?? [];
echo "Identified IDs:\n";
var_dump($ids_array);

$ctx = $knowledge->obtenerContextosAnalisis(["secrecion_uretral"]);
echo "CONTEXTO EXTRAÍDO:\n$ctx\n\n";

$res2 = $chatbot->generarRespuesta("Secreción uretral", [], ["secrecion_uretral"]);
echo "\n\nRespuesta del chatbot:\n";
echo $res2['respuesta'];
echo "\n\nRespuesta del chatbot:\n";
echo $res2['respuesta'];
