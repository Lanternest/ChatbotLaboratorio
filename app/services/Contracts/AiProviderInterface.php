interface AiProviderInterface{
    
    //Genera una respuesta de texto a partir de un system prompt y un historial de mensajes.
    
    @param string $system   /*Instrucciones de sistema / contexto para el modelo.
                            Puede ser una cadena vacía si no aplica.*/
    @param array<int, array{role: string, content: string}> $messages
                            /*Historial + mensaje actual. `role` admite únicamente
                            'user' o 'assistant'; cada implementación traduce esto
                            al formato que espere su proveedor (p. ej. Gemini usa 'model').*/
    @param int $maxTokens   //Límite de tokens de salida.
    @return string   //Texto plano de la respuesta del modelo.
    
    public function complete(string $system, array $messages, int $maxTokens): string;
}