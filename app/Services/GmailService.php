<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use App\Helpers\AirlineHelper;
use App\Models\User;
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
    private $user;
    private $tokenPath;
    private $queriesConfig; // Para cargar queries desde config

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->email = $user->email; // Se asigna el email desde el usuario

        if (empty($this->user->social_provider_refresh_token)) {
            throw new \InvalidArgumentException("El usuario {$this->user->email} no tiene un refresh_token. Debe re-autorizar la aplicación.");
        }

        $this->airlineDomains = AirlineHelper::allAirlineDomains();
        $this->airlineKeywords = AirlineHelper::allAirlineKeywords();
        $this->defaultKeywords = AirlineHelper::allDefaultKeywords();

        $this->client = new Client();
        $this->client->setApplicationName('Extractor de Reservas');
        
        // Usa las credenciales de la aplicación (OAuth) desde config/services.php
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));
        
        $this->client->setScopes(config('services.google.scopes'));
        
        $this->client->setAccessType('offline');

        // Carga los tokens desde el objeto User, no desde un archivo
        $this->client->setAccessToken([
            'access_token' => $this->user->social_provider_token,
            'refresh_token' => $this->user->social_provider_refresh_token,
        ]);

        $this->service = new Gmail($this->client);
    }

    public function authenticate()
    {
        if ($this->client->isAccessTokenExpired()) {
            Log::info("Token de acceso para {$this->user->email} ha expirado. Intentando refrescar.");
            
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                $newAccessToken = $this->client->getAccessToken();

                $this->client->setAccessToken($newAccessToken);

                $this->user->update([
                    'social_provider_token' => $newAccessToken['access_token']
                ]);

                Log::info("Token refrescado y guardado en la base de datos para {$this->user->email}.");
            } else {
                Log::error("El token para {$this->user->email} ha expirado y NO hay refresh_token. El usuario debe re-autenticar.");
                throw new \Exception("El token ha expirado y no hay refresh_token para {$this->user->email}. Por favor, re-autentica.");
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

    public function getReservationEmailsDesde(
        Carbon $desde,
        ?Carbon $hasta = null,
        string $tipo = 'airline')

    {
        $this->authenticate();

        $fechaInicio = $desde->format('Y/m/d');
        $fechaFin = $hasta ? $hasta->addDay()->format('Y/m/d') : null; // Gmail excluye el día exacto en 'before:'

        $queries = [];

        $subjectKeywords = implode(' OR ', $this->airlineKeywords);
        $genericKeywords = implode(' OR ', $this->defaultKeywords);
        $fromDomains = implode(' OR ', array_map(fn($domain) => "from:{$domain}", $this->airlineDomains));

        if ($tipo === 'airline' || $tipo === null) {
            if (!empty($fromDomains)) {
                $queries[] = "({$fromDomains}) subject:({$subjectKeywords}) after:{$fechaInicio}" . ($fechaFin ? " before:{$fechaFin}" : '');
            }

            //$queries[] = 'subject:("reserva de vuelo" OR "flight booking" OR "confirmación de vuelo" OR "e-ticket receipt" OR "itinerario de viaje" OR "boarding pass") after:' . $fechaInicio . ($fechaFin ? " before:{$fechaFin}" : '');
// AHORA (en getReservationEmailsDesde):
            $queries[] = 'subject:("reserva de vuelo" OR "flight booking" OR "confirmación de vuelo" OR "e-ticket receipt" OR "itinerario de viaje" OR "boarding pass" OR "Pase de Abordar") after:' . $fechaInicio . ($fechaFin ? " before:{$fechaFin}" : '');
            $queries[] = "(\"vuelo\" OR \"aerolínea\" OR \"flight\" OR \"airline\" OR \"PNR\") subject:({$genericKeywords}) after:{$fechaInicio}" . ($fechaFin ? " before:{$fechaFin}" : '');
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

    /**
     * Extrae asunto, fecha, remitente, cuerpo y PDF-texto (con soporte recursivo).
     * @param Gmail\Message $message
     * @return array
     */
    public function extractEmailContent(Gmail\Message $message): array
    {
        $messageId = $message->getId();
        Log::info("▶️ Iniciando extracción de contenido para el mensaje ID: {$messageId}");

        $payload = $message->getPayload();
        $headers = $payload->getHeaders();

        $emailData = [
            'subject'            => null,
            'date'               => null,
            'from'               => null,
            'body'               => '',
            'snippet'            => $message->getSnippet(),
            'adjuntos_pdf_texto' => [],
        ];

        /*-----------------------------------------------
        | Cabeceras principales
        |-----------------------------------------------*/
        foreach ($headers as $header) {
            switch ($header->getName()) {
                case 'Subject': $emailData['subject'] = $header->getValue(); break;
                case 'Date':    $emailData['date']    = $header->getValue(); break;
                case 'From':    $emailData['from']    = $header->getValue(); break;
            }
        }
        Log::debug("Cabeceras extraídas para {$messageId}", ['subject' => $emailData['subject'], 'from' => $emailData['from']]);

        /*-----------------------------------------------
        | Procesar partes (recursivo)
        |-----------------------------------------------*/
        $partesRaiz = $payload->getParts() ?: [$payload];
        $this->processPartsRecursive($partesRaiz, $emailData, $messageId);

        /* Limpieza final del cuerpo */
        if (!empty($emailData['body'])) {
            $emailData['body'] = trim(preg_replace('/\s+/', ' ', $emailData['body']));
        }

        Log::info("✅ Finalizada extracción para {$messageId}", [
            'subject'          => $emailData['subject'],
            'body_found'       => !empty($emailData['body']),
            'pdfs_extracted'   => count($emailData['adjuntos_pdf_texto']),
        ]);

        return $emailData;
    }

    /**
     * Recorre todas las partes (y sub-partes) para:
     * 1. Hallar el cuerpo principal
     * 2. Detectar y extraer PDFs (adjuntos o inline)
     * 3. Seguir bajando recursivamente
     * @param Gmail\MessagePart[] $parts
     * @param array $emailData
     * @param string $messageId
     */
    private function processPartsRecursive(array $parts, array &$emailData, string $messageId): void
    {
        foreach ($parts as $part) {
            $mimeType = $part->getMimeType() ?? 'desconocido';
            $filename = $part->getFilename() ?? '';
            $body     = $part->getBody();
            $attachmentId = $body?->getAttachmentId();

            Log::debug("🔎 Analizando parte MIME en mensaje {$messageId}", [
                'mimeType'       => $mimeType,
                'filename'       => $filename ?: '(ninguno)',
                'hasAttachmentId'=> $attachmentId ? 'sí' : 'no',
                'hasInlineData'  => $body?->getData() ? 'sí' : 'no',
            ]);

            /*-------------------------------------------
            | 1) Cuerpo principal (prioriza text/plain)
            |-------------------------------------------*/
            if (empty($emailData['body']) && $body && $body->getData()) {
                if ($mimeType === 'text/plain') {
                    $emailData['body'] = base64_decode(strtr($body->getData(), '-_', '+/'));
                    Log::debug("Cuerpo 'text/plain' encontrado y asignado para {$messageId}.");
                } elseif ($mimeType === 'text/html') {
                    $html = base64_decode(strtr($body->getData(), '-_', '+/'));
                    $emailData['body'] = strip_tags($html); // Asigna como fallback
                    Log::debug("Cuerpo 'text/html' encontrado y asignado como fallback para {$messageId}.");
                }
            }

            /*-------------------------------------------
            | 2) Detección y extracción de PDF
            |-------------------------------------------*/
            $esPdf = (
                ($filename && Str::endsWith(strtolower($filename), '.pdf'))
                || $mimeType === 'application/pdf'
                || ($filename && $mimeType === 'application/octet-stream') // Caso común para adjuntos genéricos
            );

            if ($esPdf && $body) {
                Log::info("📄 Potencial PDF detectado en mensaje {$messageId}. Filename: '{$filename}', MimeType: '{$mimeType}'");
                $pdfRawData = null;

                try {
                    if ($attachmentId) {
                        Log::debug("Intentando obtener PDF desde attachmentId: {$attachmentId}");
                        $attachment = $this->service->users_messages_attachments->get('me', $messageId, $attachmentId);
                        $pdfRawData = base64_decode(strtr($attachment->getData(), '-_', '+/'));
                        Log::info("Datos de PDF adjunto recuperados para '{$filename}'");

                    } elseif ($body->getData()) {
                        Log::debug("Intentando obtener PDF inline (base64) directamente desde la parte.");
                        $pdfRawData = base64_decode(strtr($body->getData(), '-_', '+/'));
                        Log::info("Datos de PDF inline recuperados para '{$filename}'");
                    }

                    if ($pdfRawData) {
                        // Log de validación: los PDF empiezan con "%PDF" (hex: 25 50 44 46)
                        Log::debug("Inicio binario del PDF (hex): " . bin2hex(substr($pdfRawData, 0, 8)));

                        $parser   = new PdfParser();
                        $document = $parser->parseContent($pdfRawData);
                        $pdfText  = $document->getText();

                        if (!empty($pdfText)) {
                            $emailData['adjuntos_pdf_texto'][] = [
                                'filename' => $filename ?: 'adjunto.pdf',
                                'content'  => $pdfText,
                            ];
                            Log::info("✔️ Texto extraído con éxito del PDF '{$filename}' (" . strlen($pdfText) . ' caracteres).');
                        } else {
                            Log::warning("⚠️ El PDF '{$filename}' en mensaje {$messageId} fue procesado pero no contenía texto legible o estaba vacío.");
                        }
                    } else {
                         Log::warning("⚠️ Se detectó un PDF ('{$filename}') pero no se pudo recuperar su contenido binario en mensaje {$messageId}.");
                    }
                } catch (\Exception $e) {
                    Log::error("❌ Error fatal procesando PDF '{$filename}' en {$messageId}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                }
            }

            /*-------------------------------------------
            | 3) Recursividad en sub-partes
            |-------------------------------------------*/
            if ($part->getParts()) {
                Log::debug("Descendiendo a sub-partes en mensaje {$messageId}.");
                $this->processPartsRecursive($part->getParts(), $emailData, $messageId);
            }
        }
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