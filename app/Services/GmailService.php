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
     * MIN-CAMBIO: añade `in:anywhere` y cubre asuntos típicos (recibo/boarding pass/itinerario),
     * y contempla el dominio relay `entsvcs.com`.
     */
    private function getAirlineSpecificQueries(string $fechaInicio): array
    {
        $airlineQueries = [];

        // Palabras clave ya configuradas globalmente
        $subjectKeywords = implode(' OR ', $this->airlineKeywords);
        $genericKeywords = implode(' OR ', $this->defaultKeywords);

        // Incluimos explícitamente entsvcs.com (relay que aparece en copias de Copa)
        $fromDomainList = $this->airlineDomains ?: [];
        $fromDomainList[] = 'entsvcs.com';
        $fromDomainList = array_values(array_unique($fromDomainList));

        $fromDomains = implode(' OR ', array_map(fn($domain) => "from:{$domain}", $fromDomainList));

        // Q0: remitentes de aerolíneas conocidos + keywords de aerolíneas
        if (!empty($fromDomains)) {
            $airlineQueries[] = "in:anywhere ({$fromDomains}) subject:({$subjectKeywords}) after:{$fechaInicio}";
        }

        // Q1: recibo / confirmación de boleto (Copa) + adjunto PDF
        $airlineQueries[] =
            'in:anywhere subject:("recibo y confirmación de boleto" OR "confirmacion de boleto" OR "e-ticket receipt") ' .
            'has:attachment filename:pdf after:' . $fechaInicio;

        // Q2: pases de abordar + adjunto PDF
        $airlineQueries[] =
            'in:anywhere subject:("pase de abordar" OR "pases de abordar" OR "boarding pass") ' .
            'has:attachment filename:pdf after:' . $fechaInicio;

        // Q3: itinerarios / reservas + adjunto PDF
        $airlineQueries[] =
            'in:anywhere subject:("itinerario" OR "itinerario de viaje" OR "reserva de vuelo" OR "flight booking" OR "confirmación de vuelo") ' .
            'has:attachment filename:pdf after:' . $fechaInicio;

        // Q4: fallback genérico con palabras de vuelo en cuerpo/asunto + tus keywords genéricas en asunto
        $airlineQueries[] =
            'in:anywhere ("vuelo" OR "aerolínea" OR "flight" OR "airline" OR "PNR") subject:(' . $genericKeywords . ') after:' . $fechaInicio;

        return $airlineQueries;
    }

    public function getReservationEmails($months = 6, $specificType = null)
    {
        $this->authenticate();
        $fechaInicio = Carbon::now()->subMonths($months)->format('Y/m/d');
        $dateRange = $this->makeDateRange($fechaInicio, null);

        $queries = $this->composeQueries($dateRange, $specificType);
        $emails  = $this->runQueries($queries);

        \Log::info(count($emails) . " emails únicos encontrados y pre-procesados para {$this->email} (tipo: {$specificType}).");
        return $emails;
    }

    public function getReservationEmailsDesde(
        Carbon $desde,
        ?Carbon $hasta = null,
        string $tipo = 'airline'
    ) {
        $this->authenticate();

        $fechaInicio = $desde->format('Y/m/d');
        $fechaFin    = $hasta ? $hasta->copy()->addDay()->format('Y/m/d') : null; // before: exclusivo
        $dateRange   = $this->makeDateRange($fechaInicio, $fechaFin);

        $queries = $this->composeQueries($dateRange, $tipo);
        $emails  = $this->runQueries($queries);

        \Log::info(count($emails) . " emails únicos encontrados desde {$fechaInicio} para {$this->email}.");
        return $emails;
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
     * 2. Detectar y extraer PDFs (adjuntos o inline) con validaciones reales
     * 3. Seguir bajando recursivamente
     * @param Gmail\MessagePart[] $parts
     * @param array $emailData
     * @param string $messageId
     */
    private function processPartsRecursive(array $parts, array &$emailData, string $messageId): void
    {
        foreach ($parts as $part) {
            $mimeType     = $part->getMimeType() ?? 'desconocido';
            $filename     = $part->getFilename() ?? '';
            $body         = $part->getBody();
            $attachmentId = $body?->getAttachmentId();

            Log::debug("🔎 Analizando parte MIME en mensaje {$messageId}", [
                'mimeType'        => $mimeType,
                'filename'        => $filename ?: '(ninguno)',
                'hasAttachmentId' => $attachmentId ? 'sí' : 'no',
                'hasInlineData'   => $body?->getData() ? 'sí' : 'no',
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
            | 2) Detección y extracción de PDF (robusta)
            |-------------------------------------------*/
            $esPdf = $this->isRealPdfCandidate($mimeType, $filename);

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

                    if (!$pdfRawData) {
                        Log::warning("⚠️ Se detectó un PDF ('{$filename}') pero no se pudo recuperar su contenido binario en mensaje {$messageId}.");
                        // No hay nada que parsear; continúa con la siguiente parte
                        goto descend;
                    }

                    // 2.1 Denylist por extensión común mal etiquetada
                    $lowerName = strtolower($filename ?? '');
                    $denyByExt = ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.zip', '.rar', '.7z', '.xlsx', '.xls', '.csv', '.doc', '.docx'];
                    $skipByExt = false;
                    foreach ($denyByExt as $ext) {
                        if ($lowerName && str_ends_with($lowerName, $ext)) {
                            $skipByExt = true;
                            break;
                        }
                    }
                    if ($skipByExt) {
                        Log::warning("Adjunto omitido por extensión no-PDF aunque venga como octet-stream: '{$filename}' en {$messageId}");
                        goto descend;
                    }

                    // 2.2 Límite de tamaño (defensa)
                    $bytes = strlen($pdfRawData);
                    if ($bytes > 20 * 1024 * 1024) { // 10MB
                        Log::notice("PDF omitido por tamaño ({$bytes} bytes): '{$filename}' en {$messageId}");
                        goto descend;
                    }

                    // 2.3 Validación de firma %PDF-
                    if (!$this->hasPdfSignature($pdfRawData)) {
                        Log::warning("Adjunto con nombre/MIME de PDF pero sin firma %PDF-, se omite: '{$filename}' en {$messageId}");
                        goto descend;
                    }

                    // 2.4 Parseo con Smalot (manejo de cifrados)
                    try {
                        $parser   = new \Smalot\PdfParser\Parser();
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
                    } catch (\Exception $e) {
                        $msg = $e->getMessage() ?? '';
                        // Smalot no soporta PDFs protegidos/encifrados
                        if (stripos($msg, 'Secured pdf file') !== false || stripos($msg, 'Encrypted') !== false) {
                            $emailData['adjuntos_pdf_texto'][] = [
                                'filename'       => $filename ?: 'adjunto.pdf',
                                'content'        => null,
                                'skipped_reason' => 'pdf_protegido',
                            ];
                            Log::notice("PDF protegido omitido: '{$filename}' en {$messageId}");
                        } else {
                            Log::warning("No se pudo leer PDF '{$filename}' en {$messageId}: {$msg}");
                        }
                    }

                } catch (\Exception $e) {
                    Log::warning("No se pudo procesar un adjunto marcado como PDF '{$filename}' en {$messageId}: " . $e->getMessage());
                }
            }

            descend:

            /*-------------------------------------------
            | 3) Recursividad en sub-partes
            |-------------------------------------------*/
            if ($part->getParts()) {
                Log::debug("Descendiendo a sub-partes en mensaje {$messageId}.");
                $this->processPartsRecursive($part->getParts(), $emailData, $messageId);
            }
        }
    }

    /**
     * Determina si una parte es candidata real a PDF basándose en MIME + nombre.
     * - Acepta application/pdf
     * - Acepta application/octet-stream SOLO si el nombre termina en .pdf
     * - Acepta otros MIME mal etiquetados si el nombre termina en .pdf
     */
    private function isRealPdfCandidate(string $mimeType, ?string $filename): bool
    {
        $lowerName = strtolower($filename ?? '');

        $looksLikePdfByName = $lowerName !== '' && str_ends_with($lowerName, '.pdf');

        if ($mimeType === 'application/pdf') {
            return true;
        }

        if ($mimeType === 'application/octet-stream' && $looksLikePdfByName) {
            return true;
        }

        // Algunos proveedores ponen text/plain o application/* raros, confía en el nombre .pdf
        if ($looksLikePdfByName) {
            return true;
        }

        return false;
    }

    /**
     * Comprueba la firma PDF al inicio del binario (debe empezar por "%PDF-").
     */
    private function hasPdfSignature(string $data): bool
    {
        // Quita BOM UTF-8 y espacios/control al principio
        // 0xEFBBBF (BOM), espacios, tabs, CR, LF, FF, NUL
        $data = preg_replace('/^\xEF\xBB\xBF/', '', $data); 
        $data = ltrim($data, "\x00\x09\x0A\x0C\x0D\x20");

        // Acepta "%PDF-" en los primeros bytes (por si quedó algo mínimo)
        // Para mayor robustez: buscar "%PDF-" en los primeros 8 bytes
        $head = substr($data, 0, 8);
        return str_contains($head, '%PDF-');
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

    /**
     * Normaliza queries procedentes de config/aerolineas.php (gmail_query_tags):
     * - Prefija in:anywhere si falta
     * - Añade rango de fechas si el tag no trae after:/before:/newer_than:/older_than:
     */
    private function buildQueriesFromAirlineConfig(string $dateRange): array
    {
        $queries = [];
        $airlinesCfg = config('aerolineas') ?? [];

        foreach ($airlinesCfg as $key => $info) {
            if (empty($info['gmail_query_tags']) || !is_array($info['gmail_query_tags'])) continue;

            foreach ($info['gmail_query_tags'] as $raw) {
                $q = trim($raw);

                if (stripos($q, 'in:anywhere') === false) {
                    $q = 'in:anywhere ' . $q;
                }

                $hasDate = (stripos($q, ' after:') !== false)
                        || (stripos($q, ' before:') !== false)
                        || (stripos($q, ' newer_than:') !== false)
                        || (stripos($q, ' older_than:') !== false);

                if (!$hasDate && $dateRange) {
                    $q .= ' ' . $dateRange;
                }

                $queries[] = $q;
            }
        }
        return array_values(array_unique($queries));
    }

    /** Devuelve "after:YYYY/MM/DD [before:YYYY/MM/DD]" según haya fin o no */
    private function makeDateRange(?string $desdeYmd = null, ?string $hastaYmd = null): string
    {
        $range = '';
        if ($desdeYmd) $range .= "after:{$desdeYmd}";
        if ($hastaYmd) $range .= (empty($range) ? '' : ' ') . "before:{$hastaYmd}";
        return $range;
    }

    /**
     * Construye TODAS las queries: las de config + tus fallbacks genéricos.
     * Añade también el dominio relay 'entsvcs.com' (Copa), sin romper los demás.
     */
    private function composeQueries(string $dateRange, ?string $specificType): array
    {
        $queries = [];

        // 1) queries definidas en config/aerolineas.php
        $queries = array_merge($queries, $this->buildQueriesFromAirlineConfig($dateRange));

        // 2) fallbacks genéricos (solo para aerolíneas)
        if ($specificType === 'airline' || $specificType === null) {
            // ⚠️ si no hay keywords, evita subject:()
            $subjectKeywords = implode(' OR ', array_filter($this->airlineKeywords ?? []));
            $genericKeywords = implode(' OR ', array_filter($this->defaultKeywords ?? []));

            $fromDomainList = $this->airlineDomains ?: [];
            $fromDomainList[] = 'entsvcs.com'; // relay que usa Copa
            $fromDomainList = array_values(array_unique($fromDomainList));
            $fromDomains = implode(' OR ', array_map(fn($d) => "from:{$d}", $fromDomainList));

            if (!empty($fromDomains) && $subjectKeywords !== '') {
                $queries[] = "in:anywhere ({$fromDomains}) subject:({$subjectKeywords}) {$dateRange}";
            }

            // Frases típicas (robusto, sin depender de listas globales)
            $queries[] = 'in:anywhere subject:("recibo y confirmación de boleto" OR "confirmacion de boleto" OR "e-ticket receipt" OR "pase de abordar" OR "pases de abordar" OR "itinerario" OR "itinerario de viaje" OR "reserva de vuelo" OR "flight booking" OR "confirmación de vuelo" OR "boarding pass") has:attachment ' . $dateRange;

            if ($genericKeywords !== '') {
                $queries[] = 'in:anywhere ("vuelo" OR "aerolínea" OR "flight" OR "airline" OR "PNR") subject:(' . $genericKeywords . ') ' . $dateRange;
            }

            // 🎯 Rescate muy específico para tu caso real (sin filtrar por adjunto)
            $queries[] = 'in:anywhere from:call_center_services@css.copaair.com subject:("recibo y confirmación de boleto" OR "confirmacion de boleto") ' . $dateRange;
        }

        return array_values(array_unique(array_filter($queries)));
    }

    /**
     * Ejecuta el bucle de búsqueda en Gmail (paginación, includeSpamTrash, dedupe),
     * y devuelve el array de emails ya con contenido extraído.
     */
    private function runQueries(array $queries): array
    {
        $emailsOutput = [];
        $processedMessageIds = [];
        $includeSpamTrash = (bool) config('services.google.include_spam_trash', true);

        foreach ($queries as $query) {
            try {
                \Log::debug("Ejecutando query para {$this->email}: {$query}");
                $optParams = [
                    'q' => $query,
                    'maxResults' => 50,
                    'includeSpamTrash' => $includeSpamTrash,
                ];

                $pageToken = null;
                do {
                    if ($pageToken) $optParams['pageToken'] = $pageToken;

                    $response = $this->service->users_messages->listUsersMessages('me', $optParams);

                    if ($response->getMessages()) {
                        foreach ($response->getMessages() as $message) {
                            $messageId = $message->getId();
                            if (isset($processedMessageIds[$messageId])) continue;
                            $processedMessageIds[$messageId] = true;

                            try {
                                $fullMessage = $this->service->users_messages->get('me', $messageId, ['format' => 'FULL']);
                                $extractedContentArray = $this->extractEmailContent($fullMessage);
                                $emailsOutput[] = [
                                    'id'           => $messageId,
                                    'content'      => $extractedContentArray,
                                    'email_origen' => $this->email,
                                ];
                            } catch (\Exception $e) {
                                \Log::error("Error procesando mensaje ID {$messageId}: " . $e->getMessage());
                            }
                        }
                    }

                    $pageToken = $response->getNextPageToken();
                } while ($pageToken);

            } catch (\Google\Service\Exception $e) {
                $errorDetails = json_decode($e->getMessage(), true);
                \Log::error("Error de Google API para query '{$query}' / {$this->email}: " . ($errorDetails['error']['message'] ?? $e->getMessage()));
                if ($e->getCode() == 401 || $e->getCode() == 403) throw $e;
            } catch (\Exception $e) {
                \Log::error("Error general para query '{$query}' / {$this->email}: " . $e->getMessage());
            }
        }

        return $emailsOutput;
    }

}