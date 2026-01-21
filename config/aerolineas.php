<?php
//config aerolineas.php
// --- AEROLÍNEAS ---

return [

    'aeromexico' => [
        'friendly_name'  => 'Aeroméxico',
        'senders'       => ['notificaciones@aeromexico.com', 'eticket@aeromexico.com.mx'],
        'domains'       => ['aeromexico.com'],
        'gmail_query_tags' => 'subject:(itinerario OR boleto) has:attachment filename:pdf',
        'keywords'      => ['Localizador:', 'PNR:']
    ],

    'air_asia' => [
        'friendly_name'  => 'AirAsia',
        'senders'       => ['noreply@airasia.com', 'bookings@airasia.com', 'eticket@airasia.com'],
        'domains'       => ['airasia.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'air_baltic' => [
        'friendly_name'  => 'Air Baltic',
        'senders'       => ['noreply@airbaltic.com', 'eticket@airbaltic.com', 'info@airbaltic.com'],
        'domains'       => ['airbaltic.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'air_canada' => [
        'friendly_name'  => 'Air Canada',
        'senders'       => ['noreply@aircanada.com', 'eticket@aircanada.com', 'customerservice@aircanada.com'],
        'domains'       => ['aircanada.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'air_europa' => [
        'friendly_name'  => 'Air Europa',
        'senders'       => ['notificaciones@aireuropa.com', 'eticket@aireuropa.com', 'clientes@aireuropa.com', 'noreply@aireuropa.com'],
        'domains'       => ['aireuropa.com'],
        'gmail_query_tags' => 'subject:(reserva OR "billete electrónico" OR confirmación) has:attachment filename:pdf',
        'keywords'      => ['Número de reserva:', 'PNR:', 'Booking ref:']
    ],

    'air_france' => [
        'friendly_name'         => 'Air France',
        'senders'               => ['noreply@airfrance.fr', 'eticket@airfrance.com', 'service.client@airfrance.fr', 'info@airfrance.com', 'boardingpass@airfrance.com'],
        'domains'               => ['airfrance.com', 'airfrance.fr'],
        'gmail_query_tags'      => [
            'subject:(réservation OR e-billet OR "confirmation" OR "carte d\'embarquement")',
            'has:attachment filename:pdf',
            'from:(@airfrance.com OR @airfrance.fr)'
        ],
        'keywords'              => ['Numéro de réservation:', 'Locator:', 'PNR:']
    ],

    'air_india' => [
        'friendly_name'  => 'Air India',
        'senders'       => ['noreply@airindia.in', 'eticket@airindia.in', 'customersupport@airindia.in'],
        'domains'       => ['airindia.in'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'air_nz' => [
        'friendly_name'  => 'Air New Zealand',
        'senders'       => ['no-reply@airnewzealand.co.nz', 'eticket@airnz.com', 'bookings@airnz.co.nz'],
        'domains'       => ['airnewzealand.com'],
        'gmail_query_tags' => 'subject:(itinerary OR ticket) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'air_serbia' => [
        'friendly_name'  => 'Air Serbia',
        'senders'       => ['noreply@airserbia.com', 'eticket@airserbia.com', 'contact@airserbia.com'],
        'domains'       => ['airserbia.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'alaska' => [
        'friendly_name'  => 'Alaska Airlines',
        'senders'       => ['noreply@alaskaair.com', 'eticket@alaskaair.com', 'customer.care@alaskaair.com'],
        'domains'       => ['alaskaair.com'],
        'gmail_query_tags' => 'subject:(ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Confirmation #:', 'PNR:']
    ],

    'alitalia' => [
        'friendly_name'         => 'Alitalia',
        'senders'               => ['noreply@alitalia.com', 'eticket@alitalia.it', 'servizioclienti@alitalia.it', 'info@alitalia.it'],
        'domains'               => ['alitalia.com', 'alitalia.it'],
        'gmail_query_tags'      => [
            'subject:(prenotazione OR e-ticket OR "conferma")',
            'has:attachment filename:pdf',
            'from:(@alitalia.com OR @alitalia.it)'
        ],
        'keywords'              => ['Codice prenotazione:', 'Locator:']
    ],

    'american_airlines' => [
        'friendly_name'         => 'American Airlines',
        'function'              => true,

        'senders'               => [
            'noreply@aa.com',
            'eticket@americanairlines.com',
            'customer.service@aa.com',
            'preflight@aa.com',
            'no-reply@info.email.aa.com',
        ],

        'domains'               => [
            'aa.com',
            'americanairlines.com',
            'info.email.aa.com',
        ],

        'gmail_query_tags'      => [
            'subject:(itinerary OR e-ticket OR "booking confirmation" OR "trip confirmation" OR "record locator" OR "confirmation code")',
            'from:(@aa.com OR @americanairlines.com OR @info.email.aa.com)',
        ],

        'specific_keywords'     => [
            'Record locator:',
            'Confirmation code:',
            'Booking confirmation',
            'Trip confirmation',
        ],
    ],

    'austrian_airlines' => [
        'friendly_name'  => 'Austrian Airlines',
        'senders'       => ['noreply@austrian.com', 'eticket@austrian.com', 'service@austrian.com'],
        'domains'       => ['austrian.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'avianca' => [
        'friendly_name'  => 'Avianca',
        'senders'       => ['noreply@avianca.com', 'eticket@avianca.com', 'servicioalcliente@avianca.com'],
        'domains'       => ['avianca.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'azul' => [
        'friendly_name'  => 'Azul Airlines',
        'senders'       => ['naoresponda@voeazul.com.br', 'ticket@azul.com.br', 'atendimento@azul.com.br'],
        'domains'       => ['voeazul.com.br'],
        'gmail_query_tags' => 'subject:(bilhete OR passagem) has:attachment filename:pdf',
        'keywords'      => ['Código:', 'Reserva:']
    ],

    'blue_air' => [
        'friendly_name'  => 'Blue Air (Rumanía)',
        'senders'       => ['noreply@blueairweb.com', 'tickets@blueair.com', 'contact@blueair.com'],
        'domains'       => ['blueair.com'],
        'gmail_query_tags' => 'subject:(bilet OR rezervare) has:attachment filename:pdf',
        'keywords'      => ['Cod rezervare:', 'PNR:']
    ],

    'brussels_airlines' => [
        'friendly_name'  => 'Brussels Airlines',
        'senders'       => ['noreply@brusselsairlines.com', 'eticket@brusselsairlines.com', 'customer.relations@brusselsairlines.com'],
        'domains'       => ['brusselsairlines.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'breeze' => [
        'friendly_name'  => 'Breeze Airways',
        'senders'       => ['noreply@flybreeze.com', 'tickets@flybreeze.com', 'support@flybreeze.com'],
        'domains'       => ['flybreeze.com'],
        'gmail_query_tags' => 'subject:(itinerary OR ticket) has:attachment filename:pdf',
        'keywords'      => ['Booking code:', 'PNR:']
    ],

    'cathay_pacific' => [
        'friendly_name'  => 'Cathay Pacific',
        'senders'       => ['noreply@cathaypacific.com', 'eticket@cathaypacific.com.hk', 'customer.services@cathaypacific.com'],
        'domains'       => ['cathaypacific.com'],
        'gmail_query_tags' => 'subject:(e-ticket OR itinerary) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'cayman_airways' => [
        'friendly_name'  => 'Cayman Airways',
        'senders'       => ['noreply@caymanairways.com', 'eticket@caymanairways.ky', 'customerservice@caymanairways.com'],
        'domains'       => ['caymanairways.com'],
        'gmail_query_tags' => 'subject:(itinerary OR ticket) has:attachment filename:pdf',
        'keywords'      => ['Booking code:', 'PNR:']
    ],

    'cebupacific' => [
        'friendly_name'  => 'Cebu Pacific (Filipinas)',
        'senders'       => ['noreply@cebupacificair.com', 'eticket@cebupacific.com.ph', 'customercare@cebupacificair.com'],
        'domains'       => ['cebupacificair.com'],
        'gmail_query_tags' => 'subject:(itinerary OR ticket) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'china_eastern' => [
        'friendly_name'  => 'China Eastern',
        'senders'       => ['noreply@ceair.com', 'eticket@ceair.com', 'customer.service@ceair.com'],
        'domains'       => ['ceair.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'china_southern' => [
        'friendly_name'  => 'China Southern',
        'senders'       => ['noreply@csair.com', 'eticket@csair.com', 'customer.service@csair.com'],
        'domains'       => ['csair.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'copa_airlines' => [
        'friendly_name' => 'Copa',
        'function'      => true,

        'senders' => [
            'call_center_services@css.copaair.com',
            'noreply@css.copaair.com',
            'e-ticket@copaair.com',
            'noreply@selfcheckin.copaair.com',
            'upgrades@copaair.com',
            'pedro4life09@yahoo.com',
        ],

        // Añadimos también el relay y el subdominio del self-checkin
        'domains' => [
            'css.copaair.com',
            'copaair.com',
            'copa.com',
            'selfcheckin.copaair.com',
            'entsvcs.com',
        ],

        // Estas queries se fusionan con las genéricas del servicio
        'gmail_query_tags' => [
            // 🔥 Caso real tuyo (SIN has:attachment por si Gmail no indexa bien el adjunto)
            'from:call_center_services@css.copaair.com subject:("recibo y confirmación de boleto" OR "confirmacion de boleto")',

            // Recibo/confirmación con adjunto PDF (cuando Gmail sí lo detecta)
            'subject:("recibo y confirmación de boleto" OR "confirmacion de boleto" OR "e-ticket receipt") from:(css.copaair.com OR copaair.com OR copa.com OR entsvcs.com OR call_center_services@css.copaair.com) has:attachment filename:pdf',

            // Pases de abordar
            'subject:("pase de abordar" OR "pases de abordar" OR "boarding pass") from:(selfcheckin.copaair.com OR noreply@selfcheckin.copaair.com OR css.copaair.com OR copaair.com OR copa.com) has:attachment filename:pdf',

            // Itinerario / e-ticket
            'subject:("itinerario" OR "itinerario de viaje" OR "e-ticket" OR "eticket" OR "confirmación de vuelo" OR "reserva de vuelo") from:(css.copaair.com OR copaair.com OR e-ticket@copaair.com OR copa.com) has:attachment filename:pdf',
        ],

        'specific_keywords' => [
            'Record Locator:',
            'Número de reserva:',
            'Purchase Receipt',
            'EMD',
            'Recibo y confirmación de boleto',
            'Confirmación de boleto',
            'Pase de Abordar',
        ],
    ],

    'croatia_airlines' => [
        'friendly_name'  => 'Croatia Airlines',
        'senders'       => ['noreply@croatiaairlines.hr', 'eticket@croatiaairlines.hr', 'customer.service@croatiaairlines.hr'],
        'domains'       => ['croatiaairlines.hr'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'czech_airlines' => [
        'friendly_name'  => 'Czech Airlines',
        'senders'       => ['noreply@csa.cz', 'eticket@csa.cz', 'customer.service@csa.cz'],
        'domains'       => ['csa.cz'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'delta' => [
        'friendly_name'         => 'Delta Air Lines',
        'senders'               => ['noreply@delta.com', 'eticket@delta.com', 'customer.service@delta.com', 'preflight.delta@delta.com'],
        'domains'               => ['delta.com'],
        'gmail_query_tags'      => [
            'subject:(itinerary OR e-ticket OR "booking receipt")',
            'has:attachment filename:pdf',
            'from:(@delta.com)'
        ],
        'keywords'              => ['Confirmation number:', 'PNR:']
    ],

    'easyjet' => [
        'friendly_name'         => 'EasyJet (Bajo Coste)',
        'senders'               => ['confirmation@easyjet.com', 'customerservices@easyjet.com', 'no-reply@easyjet.com', 'boardingpass@easyjet.com'],
        'domains'               => ['easyjet.com'],
        'gmail_query_tags'      => [
            'subject:(booking OR e-ticket OR "flight receipt" OR "boarding pass")',
            'has:attachment filename:pdf',
            'from:(@easyjet.com)'
        ],
        'keywords'              => ['Booking reference:', 'PNR:']
    ],

    'emirates' => [
        'friendly_name'         => 'Emirates',
        'senders'               => ['noreply@emirates.com', 'eticket@emirates.com', 'customer.service@emirates.com', 'boardingpass@emirates.com'],
        'domains'               => ['emirates.com'],
        'gmail_query_tags'      => [
            'subject:(itinerary OR e-ticket OR "booking confirmation" OR "boarding pass")',
            'has:attachment filename:pdf',
            'from:(@emirates.com)'
        ],
        'keywords'              => ['Booking reference:', 'PNR:']
    ],

    'ethiopian' => [
        'friendly_name'  => 'Ethiopian Airlines',
        'senders'       => ['noreply@ethiopianairlines.com', 'eticket@ethiopian.com', 'customer.service@ethiopianairlines.com'],
        'domains'       => ['ethiopianairlines.com'],
        'gmail_query_tags' => 'subject:(itinerary OR e-ticket) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'etihad' => [
        'friendly_name'  => 'Etihad Airways',
        'senders'       => ['noreply@etihad.ae', 'eticket@etihad.com', 'customer.service@etihad.ae'],
        'domains'       => ['etihad.com'],
        'gmail_query_tags' => 'subject:(itinerary OR e-ticket) has:attachment filename:pdf',
        'keywords'      => ['Booking reference:', 'PNR:']
    ],

    'fastjet' => [
        'friendly_name'  => 'Fastjet (África)',
        'senders'       => ['noreply@fastjet.com', 'tickets@fastjet.co.tz', 'customer.service@fastjet.com'],
        'domains'       => ['fastjet.com'],
        'gmail_query_tags' => 'subject:(itinerary OR ticket) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'finnair' => [
        'friendly_name'  => 'Finnair',
        'senders'       => ['noreply@finnair.com', 'eticket@finnair.com', 'customer.service@finnair.com'],
        'domains'       => ['finnair.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'flydubai' => [
        'friendly_name'  => 'flydubai',
        'senders'       => ['noreply@flydubai.com', 'eticket@flydubai.ae', 'customer.service@flydubai.com'],
        'domains'       => ['flydubai.com'],
        'gmail_query_tags' => 'subject:(itinerary OR ticket) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'flynas' => [
        'friendly_name'  => 'Flynas (Arabia Saudita)',
        'senders'       => ['noreply@flynas.com', 'eticket@flynas.com.sa', 'customer.service@flynas.com'],
        'domains'       => ['flynas.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket) has:attachment filename:pdf',
        'keywords'      => ['Reservation #:', 'PNR:']
    ],

    'frontier' => [
        'friendly_name'  => 'Frontier Airlines',
        'senders'       => ['noreply@flyfrontier.com', 'eticket@flyfrontier.com', 'customer.service@flyfrontier.com'],
        'domains'       => ['flyfrontier.com'],
        'gmail_query_tags' => 'subject:(itinerary OR ticket) has:attachment filename:pdf',
        'keywords'      => ['Confirmation #:', 'PNR:']
    ],

    'gol' => [
        'friendly_name'  => 'GOL Linhas Aéreas',
        'senders'       => ['naoresponda@voegol.com.br', 'bilhete@voegol.com.br', 'atendimento@voegol.com.br'],
        'domains'       => ['voegol.com.br'],
        'gmail_query_tags' => 'subject:(bilhete OR reserva) has:attachment filename:pdf',
        'keywords'      => ['Código:', 'Localizador:']
    ],

    'iberia' => [
        'friendly_name'   => 'Iberia',
        'function'        => true,
        'senders'         => ['ETServer@iberia.es', 'notificaciones@iberia.es', /* ...otros... */],
        'domains'         => ['iberia.com', 'iberia.es'],
        //'gmail_query_tags'=> ['subject:(reserva OR confirmación OR billete OR itinerario) from:(iberia.es OR iberia.com) has:attachment filename:pdf'],
        'gmail_query_tags'=> ['subject:(reserva OR confirmación OR billete OR itinerario) (from:iberia.es OR from:iberia.com) has:attachment filename:pdf',
],

        'specific_keywords'=> ['Localizador:', 'PNR:', 'Código de reserva:']
    ],

    'indigo' => [
        'friendly_name'  => 'IndiGo (India)',
        'senders'       => ['noreply@goindigo.in', 'eticket@indigoair.com', 'customer.support@goindigo.in'],
        'domains'       => ['goindigo.in'],
        'gmail_query_tags' => 'subject:(booking OR ticket) has:attachment filename:pdf',
        'keywords'      => ['PNR:', 'Booking ref:']
    ],

    'intercaribbean' => [
        'friendly_name'  => 'InterCaribbean',
        'senders'       => ['noreply@intercaribbean.com', 'eticket@intercaribbean.com', 'customer.service@intercaribbean.com'],
        'domains'       => ['intercaribbean.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'interjet' => [
        'friendly_name'  => 'Interjet',
        'senders'       => ['noreply@interjet.com', 'eticket@interjet.com.mx', 'servicioalcliente@interjet.com.mx'],
        'domains'       => ['interjet.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'ita_airways' => [
        'friendly_name'  => 'ITA Airways',
        'senders'       => ['noreply@ita-airways.com', 'eticket@ita-airways.com', 'customer.service@ita-airways.com'],
        'domains'       => ['ita-airways.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'jet2' => [
        'friendly_name'  => 'Jet2.com',
        'senders'       => ['noreply@jet2.com', 'eticket@jet2.com', 'customer.service@jet2.com'],
        'domains'       => ['jet2.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'jetblue' => [
        'friendly_name'  => 'JetBlue',
        'senders'       => ['notifications@jetblue.com', 'receipts@jetblue.com', 'customer.service@jetblue.com'],
        'domains'       => ['jetblue.com'],
        'gmail_query_tags' => 'subject:(itinerary OR booking) has:attachment filename:pdf',
        'keywords'      => ['Confirmation:', 'Record locator:']
    ],

    'jetsmart' => [
        'friendly_name'  => 'JetSMART (Chile/Perú)',
        'senders'       => ['notificaciones@jetsmart.com', 'tickets@jetsmart.com.pe', 'servicioalcliente@jetsmart.com'],
        'domains'       => ['jetsmart.com'],
        'gmail_query_tags' => 'subject:(reserva OR pasaje) has:attachment filename:pdf',
        'keywords'      => ['Booking ID:', 'Código:']
    ],

    'kenya_airways' => [
        'friendly_name'  => 'Kenya Airways',
        'senders'       => ['noreply@kenya-airways.com', 'eticket@kenyaairways.com', 'customer.service@kenya-airways.com'],
        'domains'       => ['kenya-airways.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket) has:attachment filename:pdf',
        'keywords'      => ['Booking code:', 'PNR:']
    ],

    'klm' => [
        'friendly_name'         => 'KLM',
        'senders'               => ['noreply@klm.com', 'tickets@klm.com', 'service@klm.com', 'boardingpass@klm.com'],
        'domains'               => ['klm.com'],
        'gmail_query_tags'      => [
            'subject:(booking OR e-ticket OR "flight itinerary" OR "boarding pass")',
            'has:attachment filename:pdf',
            'from:(@klm.com)'
        ],
        'keywords'              => ['Reservation code:', 'Booking number:']
    ],

    'latam' => [
        'friendly_name'         => 'LATAM (España/Colombia)',
        'senders'               => ['notificaciones@latam.com', 'eticket@latam.com', 'atencionalcliente@latam.com', 'info@latam.com', 'no-reply@latam.com'],
        'domains'               => ['latam.com'],
        'gmail_query_tags'      => [
            'subject:(reserva OR "billete electrónico" OR "itinerario" OR "tarjeta de embarque")',
            'has:attachment filename:(.pdf OR .PDF)',
            'from:(@latam.com)'
        ],
        'keywords'              => ['Código de reserva:', 'Localizador:', 'PNR:']
    ],

    'level' => [
        'friendly_name'  => 'Level',
        'senders'       => ['noreply@flylevel.com', 'eticket@flylevel.com', 'customer.service@flylevel.com'],
        'domains'       => ['flylevel.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'lufthansa' => [
        'friendly_name'         => 'Lufthansa',
        'senders'               => ['noreply@lufthansa.com', 'eticket@lufthansa.com', 'service@lufthansa.com', 'boardingpass@lufthansa.com', 'info@lufthansa.com'],
        'domains'               => ['lufthansa.com'],
        'gmail_query_tags'      => [
            'subject:(booking OR e-ticket OR "flight confirmation" OR "boarding pass")',
            'has:attachment filename:pdf',
            'from:(@lufthansa.com)'
        ],
        'keywords'              => ['Booking code:', 'PNR:', 'Reservation number:']
    ],

    'lot' => [
        'friendly_name'  => 'LOT Polish Airlines',
        'senders'       => ['noreply@lot.com', 'eticket@lot.com', 'customer.service@lot.com'],
        'domains'       => ['lot.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'luxair' => [
        'friendly_name'  => 'Luxair',
        'senders'       => ['noreply@luxair.lu', 'eticket@luxair.lu', 'customer.service@luxair.lu'],
        'domains'       => ['luxair.lu'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'mango' => [
        'friendly_name'  => 'Mango (Sudáfrica)',
        'senders'       => ['noreply@flymango.com', 'eticket@mango.co.za', 'customer.service@flymango.com'],
        'domains'       => ['flymango.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket) has:attachment filename:pdf',
        'keywords'      => ['Booking code:', 'PNR:']
    ],

    'norwegian' => [
        'friendly_name'         => 'Norwegian (Bajo Coste)',
        'senders'               => ['noreply@norwegian.com', 'bookings@norwegian.com', 'customer.service@norwegian.com', 'boardingpass@norwegian.com'],
        'domains'               => ['norwegian.com'],
        'gmail_query_tags'      => [
            'subject:(booking confirmation OR itinerary OR "boarding pass")',
            'has:attachment filename:pdf',
            'from:(@norwegian.com)'
        ],
        'keywords'              => ['Booking reference:', 'PNR:']
    ],

    'pegasus' => [
        'friendly_name'  => 'Pegasus Airlines',
        'senders'       => ['noreply@flypgs.com', 'eticket@flypgs.com', 'customer.service@flypgs.com'],
        'domains'       => ['flypgs.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'qantas' => [
        'friendly_name'  => 'Qantas',
        'senders'       => ['noreply@qantas.com.au', 'eticket@qantas.com', 'customer.service@qantas.com.au'],
        'domains'       => ['qantas.com'],
        'gmail_query_tags' => 'subject:(itinerary OR e-ticket) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'qatar_airways' => [
        'friendly_name'         => 'Qatar Airways',
        'senders'               => ['noreply@qatarairways.com', 'eticket@qatarairways.com', 'customer.service@qatarairways.com', 'boardingpass@qatarairways.com'],
        'domains'               => ['qatarairways.com'],
        'gmail_query_tags'      => [
            'subject:(itinerary OR e-ticket OR "booking confirmation" OR "boarding pass")',
            'has:attachment filename:pdf',
            'from:(@qatarairways.com)'
        ],
        'keywords'              => ['PNR:', 'Booking reference:']
    ],

    'ryanair' => [
        'friendly_name'         => 'Ryanair (Bajo Coste)',
        'senders'               => ['no-reply@ryanair.com', 'bookings@ryanair.com', 'customer.service@ryanair.com', 'flightinfo@ryanair.com', 'boardingpass@ryanair.com'],
        'domains'               => ['ryanair.com'],
        'gmail_query_tags'      => [
            'subject:(booking confirmation OR itinerary OR boarding pass)',
            'has:attachment filename:pdf',
            'from:(@ryanair.com)'
        ],
        'keywords'              => ['Reservation number:', 'Booking ref:', 'PNR:']
    ],

    'sas' => [
        'friendly_name'  => 'Scandinavian Airlines (SAS)',
        'senders'       => ['no-reply@sas.se', 'eticket@sas.no', 'booking@sas.dk', 'customer.service@sas.se'],
        'domains'       => ['sas.se', 'sas.no', 'sas.dk'],
        'gmail_query_tags' => 'subject:(booking OR e-ticket) has:attachment filename:pdf from:(@sas.se OR @sas.no)',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'saudia' => [
        'friendly_name'  => 'Saudia',
        'senders'       => ['noreply@saudia.com', 'eticket@sv.com.sa', 'customer.service@saudia.com'],
        'domains'       => ['saudia.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket) has:attachment filename:pdf',
        'keywords'      => ['Reservation #:', 'PNR:']
    ],

    'singapore_air' => [
        'friendly_name'  => 'Singapore Airlines',
        'senders'       => ['noreply@singaporeair.com', 'eticket@singaporeair.com.sg', 'customer.service@singaporeair.com.sg'],
        'domains'       => ['singaporeair.com'],
        'gmail_query_tags' => 'subject:(itinerary OR e-ticket) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'sky_airline' => [
        'friendly_name'  => 'Sky Airline (Chile)',
        'senders'       => ['notificaciones@skyairline.com', 'boletos@skyairline.cl', 'servicioalcliente@skyairline.com'],
        'domains'       => ['skyairline.com'],
        'gmail_query_tags' => 'subject:(reserva OR itinerario) has:attachment filename:pdf',
        'keywords'      => ['Código:', 'Localizador:']
    ],

    'spirit' => [
        'friendly_name'  => 'Spirit Airlines',
        'senders'       => ['noreply@spirit.com', 'eticket@spirit.com', 'customer.service@spirit.com'],
        'domains'       => ['spirit.com'],
        'gmail_query_tags' => 'subject:(itinerary OR booking) has:attachment filename:pdf',
        'keywords'      => ['Confirmation #:', 'PNR:']
    ],

    'sun_country' => [
        'friendly_name'  => 'Sun Country Airlines',
        'senders'       => ['noreply@suncountry.com', 'eticket@suncountry.com', 'customer.service@suncountry.com'],
        'domains'       => ['suncountry.com'],
        'gmail_query_tags' => 'subject:(itinerary OR booking) has:attachment filename:pdf',
        'keywords'      => ['Confirmation #:', 'PNR:']
    ],

    'swiss' => [
        'friendly_name'  => 'SWISS',
        'senders'       => ['noreply@swiss.com', 'eticket@swiss.com', 'customer.service@swiss.com'],
        'domains'       => ['swiss.com'],
        'gmail_query_tags' => 'subject:(ticket OR booking) has:attachment filename:pdf',
        'keywords'      => ['Locator:', 'Order number:']
    ],

    'tap' => [
        'friendly_name'  => 'TAP Air Portugal',
        'senders'       => ['noreply@flytap.com', 'eticket@flytap.com', 'customer.service@flytap.com'],
        'domains'       => ['flytap.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],
        
    'transavia' => [
        'friendly_name'  => 'Transavia',
        'senders' => ['noreply@transavia.com', 'eticket@transavia.com', 'customer.service@transavia.com'],
        'domains' => ['transavia.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords' => ['Booking ref:', 'PNR:']
    ],

    'turkish_airlines' => [
        'friendly_name'  => 'Turkish Airlines',
        'senders'       => ['noreply@thy.com', 'eticket@turkishairlines.com', 'customer.service@turkishairlines.com'],
        'domains'       => ['turkishairlines.com', 'thy.com'],
        'gmail_query_tags' => 'subject:(e-bilet OR "flight ticket") has:attachment filename:pdf',
        'keywords'      => ['Booking code:', 'PNR:']
    ],

    'united' => [
        'friendly_name'  => 'United Airlines',
        'senders'       => ['no-reply@united.com', 'eticket@united.com', 'customer.service@united.com'],
        'domains'       => ['united.com'],
        'gmail_query_tags' => 'subject:(itinerary OR receipt) has:attachment filename:pdf',
        'keywords'      => ['Confirmation #:', 'PNR:']
    ],

    'volaris' => [
        'friendly_name'  => 'Volaris (México)',
        'senders'       => ['notificaciones@volaris.com', 'boletos@volaris.com.mx', 'servicioalcliente@volaris.com.mx'],
        'domains'       => ['volaris.com'],
        'gmail_query_tags' => 'subject:(confirmación OR boleto) has:attachment filename:pdf',
        'keywords'      => ['Folio:', 'PNR:']
    ],

    'volotea' => [
        'friendly_name'  => 'Volotea',
        'senders'       => ['noreply@volotea.com', 'eticket@volotea.com', 'customer.service@volotea.com'],
        'domains'       => ['volotea.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'vueling' => [
        'friendly_name'         => 'Vueling',
        'senders'               => ['notificaciones@vueling.com', 'info@vueling.com', 'no-reply@vueling.com', 'servicio.cliente@vueling.com', 'checkin@vueling.com'],
        'domains'               => ['vueling.com'],
        'gmail_query_tags'      => [
            'subject:(reserva OR "confirmación de vuelo" OR "e-ticket" OR "tarjeta de embarque")',
            'has:attachment filename:pdf',
            'from:(@vueling.com)'
        ],
        'keywords'              => ['Código de reserva:', 'Booking number:', 'PNR:']
    ],

    'westjet' => [
        'friendly_name'  => 'WestJet',
        'senders'       => ['noreply@westjet.com', 'eticket@westjet.com', 'customer.service@westjet.com'],
        'domains'       => ['westjet.com'],
        'gmail_query_tags' => 'subject:(booking OR ticket OR confirmation) has:attachment filename:pdf',
        'keywords'      => ['Booking ref:', 'PNR:']
    ],

    'wizz_air' => [
        'friendly_name'         => 'Wizz Air (Bajo Coste)',
        'senders'               => ['noreply@wizzair.com', 'bookings@wizzair.com', 'customer.service@wizzair.com', 'boardingpass@wizzair.com'],
        'domains'               => ['wizzair.com'],
        'gmail_query_tags'      => [
            'subject:(booking confirmation OR itinerary OR "boarding pass")',
            'has:attachment filename:pdf',
            'from:(@wizzair.com)'
        ],
        'keywords'              => ['Reservation number:', 'Booking ref:', 'PNR:']
    ],

    // --- CONFIGURACIÓN GLOBAL ---
    'global_settings' => [
        'pdf_parser'    => 'smalot/pdfparser',
        'regex_patterns'=> [
            'booking_code' => '/\b(?:PNR|Booking ref|Código|Folio)[:\s]*([A-Z0-9]{6,8})\b/i',
            'flight_number'=> '/\b(?:Flight|Vuelo|Vol)[\s]*([A-Z0-9]{2,5})\b/i',
            'passenger'   => '/Passenger name:\s*(.+)/i'
        ],
        'timezone'      => 'UTC',
        'backup_emails' => ['backup@yourdomain.com']
    ]
];
