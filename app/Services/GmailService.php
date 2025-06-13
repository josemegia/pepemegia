<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use App\Helpers\AirlineHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;

class GmailService
{
    private $client;
    private $service;
    private $email;
    private $tokenPath;
    private $queriesConfig; // Para cargar queries desde config

    public function __construct(string $email)
    {
        $this->email = $email;
        $this->tokenPath = storage_path('app/private/token-' . $email . '.json');

        $this->airlineDomains = AirlineHelper::allAirlineDomains();
        $this->airlineKeywords = AirlineHelper::allAirlineKeywords();
        $this->defaultKeywords = AirlineHelper::allDefaultKeywords();

        $this->client = new Client();
        $this->client->setApplicationName('Extractor de Reservas');
        $this->client->setScopes([Gmail::GMAIL_READONLY]);
        $this->client->setAuthConfig(Storage::disk('local')->path('credentials.json'));
        $this->client->setAccessType('offline');
        $this->service = new Gmail($this->client);
    }

    public function authenticate()
    {
        // ... (método authenticate se mantiene igual que en la versión anterior robusta) ...
        Log::info("Intentando autenticar para {$this->email}. Buscando token en: {$this->tokenPath}");
        if (!file_exists($this->tokenPath)) {
            Log::error("No se encontró token para {$this->email} en la ruta: {$this->tokenPath}");
            throw new \Exception("No se encontró token para {$this->email} en {$this->tokenPath}. Por favor, re-autentica.");
        }

        $accessTokenJson = file_get_contents($this->tokenPath);
        $accessToken = json_decode($accessTokenJson, true);

        if ($accessToken === null && json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Error decodificando el token JSON para {$this->email} desde {$this->tokenPath}. Error JSON: " . json_last_error_msg());
            throw new \Exception("Error al decodificar el token JSON para {$this->email}.");
        }
        
        $this->client->setAccessToken($accessToken);

        if ($this->client->isAccessTokenExpired()) {
            Log::info("Token de acceso para {$this->email} ha expirado. Intentando refrescar.");
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken()));
                Log::info("Token refrescado y guardado para {$this->email} en {$this->tokenPath}.");
            } else {
                Log::error("El token para {$this->email} ha expirado y NO hay refresh_token. El usuario debe re-autenticar.");
                throw new \Exception("El token ha expirado y no hay refresh_token para {$this->email}. Por favor, re-autentica.");
            }
        }
    }

    /**
     * Construye queries de Gmail más específicas para aerolíneas.
     */
    private function getAirlineSpecificQueries(string $fechaInicio): array
    {
        $airlineQueries = [];
        $subjectKeywords = implode(' OR ', $this->airlineKeywords);
        $genericKeywords = implode(' OR ', $this->defaultKeywords);

        // Query 1: De dominios de aerolíneas conocidos con palabras clave de aerolíneas en el asunto
        $fromDomains = implode(' OR ', array_map(function($domain) { return "from:{$domain}"; }, $this->airlineDomains));
        if (!empty($fromDomains)) {
            $airlineQueries[] = "({$fromDomains}) subject:({$subjectKeywords}) after:{$fechaInicio}";
        }

        // Query 2: Asuntos muy específicos de vuelos
        $airlineQueries[] = 'subject:("reserva de vuelo" OR "flight booking" OR "confirmación de vuelo" OR "e-ticket receipt" OR "itinerario de viaje" OR "boarding pass") after:' . $fechaInicio;
        
        // Query 3: Palabras clave genéricas pero con "vuelo" o "aerolínea" en el cuerpo o asunto para más cobertura
        $airlineQueries[] = "(\"vuelo\" OR \"aerolínea\" OR \"flight\" OR \"airline\" OR \"PNR\") subject:({$genericKeywords}) after:{$fechaInicio}";

        return $airlineQueries;
    }


    public function getReservationEmails($months = 6, $specificType = null) // Añadido $specificType
    {
        $this->authenticate();
        $fechaInicio = Carbon::now()->subMonths($months)->format('Y/m/d');
        $queries = [];

        if ($specificType === 'airline' || $specificType === null) { // Si se piden aerolíneas o todos
             $queries = array_merge($queries, $this->getAirlineSpecificQueries($fechaInicio));
        }
        
        // Aquí podrías añadir lógica para otros tipos si $specificType lo indica
        // if ($specificType === 'hotel' || $specificType === null) { ... }

        if (empty($queries)) { // Si no se especificó un tipo válido o no hay queries
             Log::warning("No se generaron queries para getReservationEmails con tipo: {$specificType}");
             // Query genérica si no se especifica tipo o si se quieren todos los tipos (si no se ha añadido ya)
            if($specificType === null && !in_array('subject:('.implode(' OR ', $this->queriesConfig['default_keywords']).') after:' . $fechaInicio, $queries) ){
                 $queries[] = 'subject:('.implode(' OR ', $this->queriesConfig['default_keywords']).') after:' . $fechaInicio;
            }
        }
        
        // El resto del método (bucles, paginación, extracción de contenido) se mantiene igual
        // que la versión robusta anterior que te proporcioné, asegurándose de devolver:
        // ['id' => ..., 'content' => ['subject'=>..., 'body'=>..., 'from'=>...], 'email_origen' => ...]
        // ... (código de bucle y extracción omitido por brevedad, usar el de la respuesta anterior) ...
        $emailsOutput = [];
        $processedMessageIds = [];

        foreach ($queries as $query) {
            try {
                Log::debug("Ejecutando query para {$this->email} (tipo: {$specificType}): {$query}");
                $optParams = [
                    'q' => $query,
                    'maxResults' => 50 
                ];
                
                $pageToken = null;
                do {
                    if ($pageToken) {
                        $optParams['pageToken'] = $pageToken;
                    }
                    $response = $this->service->users_messages->listUsersMessages('me', $optParams);

                    if ($response->getMessages()) {
                        foreach ($response->getMessages() as $message) {
                            $messageId = $message->getId();
                            if (in_array($messageId, $processedMessageIds)) {
                                continue;
                            }
                            $processedMessageIds[] = $messageId;

                            try {
                                $fullMessage = $this->service->users_messages->get('me', $messageId, ['format' => 'FULL']);
                                $extractedContentArray = $this->extractEmailContent($fullMessage);
                                $emailsOutput[] = [
                                    'id' => $messageId,
                                    'content' => $extractedContentArray,
                                    'email_origen' => $this->email
                                ];
                            } catch (\Exception $e) {
                                Log::error("Error obteniendo o procesando mensaje completo ID {$messageId} para {$this->email}: " . $e->getMessage());
                            }
                        }
                    }
                    $pageToken = $response->getNextPageToken();
                } while ($pageToken);

            } catch (\Google\Service\Exception $e) {
                $errorDetails = json_decode($e->getMessage(), true);
                Log::error("Error de Google API para query '{$query}' / {$this->email}: " . ($errorDetails['error']['message'] ?? $e->getMessage()));
                if ($e->getCode() == 401 || $e->getCode() == 403) throw $e;
            } catch (\Exception $e) {
                Log::error("Error general para query '{$query}' / {$this->email}: " . $e->getMessage());
            }
        }
        Log::info(count($emailsOutput) . " emails únicos encontrados y pre-procesados para {$this->email} (tipo: {$specificType}).");
        return $emailsOutput;
    }

    public function getReservationEmailsDesde(Carbon $desde, string $tipo = 'airline')
    {
        $this->authenticate();
        $fechaInicio = $desde->format('Y/m/d');
        $queries = [];

        if ($tipo === 'airline' || $tipo === null) {
            $queries = array_merge($queries, $this->getAirlineSpecificQueries($fechaInicio));
        }

        // Lógica de búsqueda igual que en getReservationEmails()
        $emailsOutput = [];
        $processedMessageIds = [];

        foreach ($queries as $query) {
            try {
                Log::debug("Ejecutando query para {$this->email} (tipo: {$tipo}): {$query}");
                $optParams = ['q' => $query, 'maxResults' => 50];
                $pageToken = null;

                do {
                    if ($pageToken) {
                        $optParams['pageToken'] = $pageToken;
                    }

                    $response = $this->service->users_messages->listUsersMessages('me', $optParams);

                    if ($response->getMessages()) {
                        foreach ($response->getMessages() as $message) {
                            $messageId = $message->getId();
                            if (in_array($messageId, $processedMessageIds)) continue;
                            $processedMessageIds[] = $messageId;

                            try {
                                $fullMessage = $this->service->users_messages->get('me', $messageId, ['format' => 'FULL']);
                                $extractedContentArray = $this->extractEmailContent($fullMessage);
                                $emailsOutput[] = [
                                    'id' => $messageId,
                                    'content' => $extractedContentArray,
                                    'email_origen' => $this->email,
                                ];
                            } catch (\Exception $e) {
                                Log::error("Error procesando mensaje ID {$messageId}: " . $e->getMessage());
                            }
                        }
                    }

                    $pageToken = $response->getNextPageToken();

                } while ($pageToken);

            } catch (\Exception $e) {
                Log::error("Error en query '{$query}' / {$this->email}: " . $e->getMessage());
            }
        }

        Log::info(count($emailsOutput) . " emails únicos encontrados desde {$fechaInicio} para {$this->email}.");
        return $emailsOutput;
    }

    // extractEmailContent y getMessageBody se mantienen igual que en la última versión robusta que te di

    public function extractEmailContent(Gmail\Message $message): array
    {
        $messageId = $message->getId();
        $payload = $message->getPayload();
        $headers = $payload->getHeaders();
        $emailData = [
            'subject' => null,
            'date' => null,
            'from' => null,
            'body' => '', // Cuerpo principal del email (texto plano o HTML convertido)
            'snippet' => $message->getSnippet(),
            'adjuntos_pdf_texto' => [], // Aquí guardaremos el texto de los PDFs
        ];

        foreach ($headers as $header) {
            if ($header->getName() === 'Subject') {
                $emailData['subject'] = $header->getValue();
            }
            if ($header->getName() === 'Date') {
                $emailData['date'] = $header->getValue();
            }
            if ($header->getName() === 'From') {
                $emailData['from'] = $header->getValue();
            }
        }

        $parts = $payload->getParts() ? $payload->getParts() : [$payload]; // Si no hay parts, el payload mismo es el cuerpo
        $foundBody = false;

        foreach ($parts as $part) {
            // Intenta obtener el cuerpo principal del email (prioriza text/plain)
            if (!$foundBody && $part->getMimeType() === 'text/plain' && $part->getBody() && $part->getBody()->getData()) {
                $emailData['body'] = base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
                $foundBody = true;
            }
            // Si no hay text/plain, busca text/html y conviértelo (strip_tags es simple, considera una librería si necesitas mejor conversión)
            if (!$foundBody && $part->getMimeType() === 'text/html' && $part->getBody() && $part->getBody()->getData()) {
                $htmlBody = base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
                $emailData['body'] = strip_tags($htmlBody); // Conversión simple
                $foundBody = true;
            }

            // --- NUEVA LÓGICA PARA PROCESAR ADJUNTOS PDF ---
            if (!empty($part->getFilename()) && $part->getBody() && $part->getBody()->getAttachmentId()) {
                $filename = $part->getFilename();
                $mimeType = $part->getMimeType();

                // Verifica si es un PDF por nombre de archivo o tipo MIME
                if (Str::endsWith(strtolower($filename), '.pdf') || $mimeType === 'application/pdf') {
                    Log::info("GmailService: PDF adjunto encontrado '{$filename}' en mensaje ID {$messageId}. Intentando parsear.");
                    try {
                        $attachment = $this->service->users_messages_attachments->get('me', $messageId, $part->getBody()->getAttachmentId());
                        $pdfRawData = base64_decode(strtr($attachment->getData(), '-_', '+/'));
                        
                        $parser = new PdfParser(); // Instancia el parser de PDF
                        $pdf = $parser->parseContent($pdfRawData);
                        $pdfTextContent = $pdf->getText(); // Extrae todo el texto del PDF
                        
                        if (!empty($pdfTextContent)) {
                            $emailData['adjuntos_pdf_texto'][] = [
                                'filename' => $filename,
                                'content' => $pdfTextContent,
                            ];
                            Log::info("GmailService: Texto extraído exitosamente del PDF '{$filename}'. Longitud: " . strlen($pdfTextContent) . " caracteres.");
                        } else {
                            Log::warning("GmailService: El PDF '{$filename}' no contenía texto o no se pudo extraer.");
                        }
                    } catch (\Exception $e) {
                        Log::error("GmailService: Error procesando PDF adjunto '{$filename}' para mensaje ID {$messageId}: " . $e->getMessage());
                    }
                }
            }
            // Si el email es multipart/alternative, las partes podrían estar anidadas.
            // Podrías necesitar una función recursiva para buscar en $part->getParts() también.
            // Por simplicidad, este ejemplo asume una estructura de partes no muy profunda o que los PDFs están en el nivel principal de 'parts'.
        }
        
        // Si después de revisar las partes principales no se encontró cuerpo y hay una única parte (payload es el cuerpo)
        if (!$foundBody && $payload->getMimeType() === 'text/plain' && $payload->getBody() && $payload->getBody()->getData()) {
             $emailData['body'] = base64_decode(strtr($payload->getBody()->getData(), '-_', '+/'));
        } elseif (!$foundBody && $payload->getMimeType() === 'text/html' && $payload->getBody() && $payload->getBody()->getData()) {
            $htmlBody = base64_decode(strtr($payload->getBody()->getData(), '-_', '+/'));
            $emailData['body'] = strip_tags($htmlBody);
        }


        // Limpiar el cuerpo del email antes de devolverlo
        if (!empty($emailData['body'])) {
            $emailData['body'] = preg_replace('/\s+/', ' ', $emailData['body']); // Reemplaza múltiples espacios/saltos
            $emailData['body'] = trim($emailData['body']);
        }

        return $emailData;
    }

    private function getMessageBody(\Google\Service\Gmail\MessagePart $payload): string
    {
        $body = ''; $parts = $payload->getParts();
        if ($parts) {
            foreach ($parts as $part) {
                if ($part->getMimeType() === 'text/plain' && $part->getBody() && $part->getBody()->getData()) {
                    return base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
                }
                if ($part->getMimeType() === 'multipart/alternative' && $part->getParts()) {
                    foreach ($part->getParts() as $altPart) {
                        if ($altPart->getMimeType() === 'text/plain' && $altPart->getBody() && $altPart->getBody()->getData()) {
                            return base64_decode(strtr($altPart->getBody()->getData(), '-_', '+/'));
                        }
                    }
                }
            }
            foreach ($parts as $part) { // Fallback a HTML o recursión
                if ($part->getMimeType() === 'text/html' && $part->getBody() && $part->getBody()->getData()) {
                    if (empty($body)) $body = base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
                }
                if ($part->getParts() && empty($body)) {
                     $nestedBody = $this->getMessageBody($part);
                     if (!empty($nestedBody)) $body = $nestedBody;
                }
            }
        } elseif ($payload->getBody() && $payload->getBody()->getData()) {
            $body = base64_decode(strtr($payload->getBody()->getData(), '-_', '+/'));
        }
        return $body;
    }
}