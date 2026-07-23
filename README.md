# Chatbot Laboratorio - Hospital Universitario UNCuyo

Este proyecto es un **Asistente Virtual con Inteligencia Artificial** diseñado específicamente para el Servicio de Análisis Clínicos del Hospital Universitario de la UNCuyo. Su función principal es orientar a los pacientes sobre las preparaciones previas y requisitos (ayuno, horarios, muestras) necesarios para realizarse sus estudios.

El sistema es capaz de entender mensajes de texto naturales e **interpretar imágenes de órdenes médicas** para brindar respuestas precisas y oficiales, y finalmente ofrecer un resumen estructurado por correo electrónico.

---

## 🏗 Arquitectura del Sistema

El sistema utiliza un enfoque de **"Dos pasos de IA"** para garantizar que las respuestas médicas sean precisas y evitar alucinaciones (inventos) de la IA:

1. **Paso 1 - El Identificador:** Lee el mensaje o la foto de la receta médica del paciente, lo compara contra nuestro catálogo oficial, y extrae **únicamente** los IDs exactos de los análisis solicitados.
2. **Paso 2 - El Chatbot (Conversacional):** Toma los IDs identificados, busca la información médica exacta de nuestra base de datos, y le redacta al paciente una respuesta amable, clara y estructurada.

---

## 📂 Explicación del Código (Estructura de Archivos)

A continuación se detalla qué hace cada archivo importante dentro de este proyecto de Laravel:

### 1. La Base de Conocimientos (El Cerebro de los Datos)
* **`storage/app/analisis_laboratorio.json`**
  Este es el archivo más importante de datos. Es un catálogo completo en formato JSON donde se definen todos los análisis del laboratorio, sus alias, palabras clave, horarios, requerimientos de ayuno e indicaciones. **Cualquier cambio en las indicaciones médicas debe hacerse modificando este archivo.**

### 2. Los Servicios (La Lógica del Negocio)
Se encuentran en la carpeta `app/Services/`.
* **`KnowledgeService.php`** (Servicio de Conocimiento):
  Se encarga de leer el archivo JSON y procesarlo. Tiene funciones para buscar un análisis específico, extraer sus alias/palabras clave, y armar un "texto compacto" con las instrucciones oficiales para entregárselo a la IA.
* **`IdentifierService.php`** (Servicio Identificador):
  Se encarga del "Paso 1". Toma el mensaje del usuario (y la imagen si existe) junto con un resumen de todos los nombres de análisis del catálogo, y le pide a la Inteligencia Artificial (Gemini) que devuelva un código JSON puro con los IDs encontrados.
* **`ChatbotService.php`** (Servicio Chatbot):
  Se encarga del "Paso 2". Le inyecta a la Inteligencia Artificial las "Reglas de comportamiento" (ser amable, no inventar, ofrecer email al final) junto con la información oficial obtenida por el `KnowledgeService`. Aquí se construyen los "Prompts" (instrucciones secretas) que guían el comportamiento del asistente.

### 3. Conexión con la Inteligencia Artificial
* **`app/Services/Providers/GeminiProvider.php`**:
  Es el puente de comunicación con los servidores de Google. Aquí se escriben las peticiones web (HTTP) que envían los textos y las imágenes codificadas a la API de **Gemini** (utilizando la llave secreta `GEMINI_API_KEY`) y se reciben sus respuestas.

### 4. Los Controladores (Los directores de orquesta)
Se encuentran en la carpeta `app/Http/Controllers/`.
* **`ChatController.php`**:
  Es la puerta de entrada. Cuando el usuario envía un mensaje o foto desde la pantalla, llega aquí. Este controlador:
  1. Recibe la imagen y el texto.
  2. Llama al `IdentifierService` para saber de qué estudios se habla.
  3. Llama al `ChatbotService` pasándole los estudios identificados para generar la respuesta.
  4. Devuelve la respuesta final hacia la pantalla (frontend).
* **`EmailController.php`**:
  Se encarga exclusivamente de tomar el resumen final generado por el bot y enviarlo por correo electrónico al paciente utilizando el sistema de mails de Laravel.

### 5. Interfaz de Usuario y Vistas
* **`frontend_test.html`** (Ubicado en la raíz del proyecto):
  Es la pantalla visual del chat. Está construida con HTML, CSS (estilos) y JavaScript puro. Contiene la lógica visual para mostrar burbujas de chat, cargar imágenes, mostrar el indicador de "Escribiendo..." y solicitar el correo al finalizar.
* **`resources/views/emails/analisis_resumen.blade.php`**:
  Es la plantilla visual (HTML) del correo electrónico que le llega al paciente. Transforma el formato "Markdown" de la IA en listas con viñetas, cajas de colores y letras grandes para que sea fácil de leer por pacientes adultos mayores.

### 6. Archivos de Configuración
* **`.env`** (Ubicado en la raíz):
  El archivo de configuración de entorno. Es privado y no se sube a repositorios públicos. Contiene:
  - `GEMINI_API_KEY`: La llave maestra para usar la inteligencia artificial.
  - Credenciales SMTP (`MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`): Los datos para poder enviar correos electrónicos (ej: la cuenta de Gmail del laboratorio).

---

## 🚀 Cómo ejecutar el proyecto

1. **Encender el servidor backend de Laravel:**
   Abre una terminal en la carpeta del proyecto y ejecuta:
   ```bash
   php artisan serve
   ```
   Esto encenderá el motor interno en `http://127.0.0.1:8000`.

2. **Abrir la pantalla de chat:**
   Simplemente haz doble clic en el archivo **`frontend_test.html`** para abrirlo en tu navegador favorito, o ábrelo mediante un servidor local (Live Server). ¡Ya puedes comenzar a chatear!

---

## 🛠 Cómo agregar un nuevo análisis

Para enseñar al bot un nuevo estudio médico:
1. Abre `storage/app/analisis_laboratorio.json`.
2. Agrega un nuevo bloque respetando la estructura existente.
3. Asegúrate de incluir palabras que los pacientes usen habitualmente en el apartado `"palabras_clave"`, para que el bot pueda relacionarlas con este estudio.
4. ¡Guarda el archivo! El bot aprenderá la nueva información instantáneamente, sin necesidad de reiniciar nada.
