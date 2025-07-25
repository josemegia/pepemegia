<?php

namespace App\Services;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberType;
use libphonenumber\NumberParseException;

class PhoneNumberService
{
    protected $phoneUtil;

    public function __construct()
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
    }

    /**
     * Valida si el número de teléfono es válido y opcionalmente si es móvil.
     *
     * @param string $number El número de teléfono.
     * @param string $countryCode Código ISO2 del país (ej. 'CO', 'CA').
     * @param bool $mobileOnly Si true, valida solo números móviles.
     * @return bool True si es válido, false si no.
     */
    public function isValid(string $number, string $countryCode, bool $mobileOnly = true): bool
    {
        try {
            $phoneNumber = $this->phoneUtil->parse($number, $countryCode);
            $isValid = $this->phoneUtil->isValidNumber($phoneNumber);
            
            if ($mobileOnly) {
                return $isValid && $this->phoneUtil->getNumberType($phoneNumber) === PhoneNumberType::MOBILE;
            }
            
            return $isValid;
        } catch (NumberParseException $e) {
            return false;
        }
    }

    /**
     * Formatea el número de teléfono en formato nacional.
     *
     * @param string $number El número de teléfono.
     * @param string $countryCode Código ISO2 del país.
     * @return string El número formateado, o el original si no se puede formatear.
     */
    public function formatNational(string $number, string $countryCode): string
    {
        try {
            $phoneNumber = $this->phoneUtil->parse($number, $countryCode);
            if ($this->phoneUtil->isValidNumber($phoneNumber)) {
                return $this->phoneUtil->format($phoneNumber, \libphonenumber\PhoneNumberFormat::NATIONAL);
            }
        } catch (NumberParseException $e) {
            // Manejo de error silencioso, retorna el número original
        }
        
        return $number;
    }

    /**
     * Obtiene el prefijo internacional para un país.
     *
     * @param string $countryCode Código ISO2 del país.
     * @return string El prefijo (ej. '+57' para 'CO').
     */
    public function getCountryPrefix(string $countryCode): string
    {
        return '+' . $this->phoneUtil->getCountryCodeForRegion($countryCode);
    }
    
    public function formatInternational(string $number, string $countryCode): string
    {
        try {
            $phoneNumber = $this->phoneUtil->parse($number, $countryCode);
            if ($this->phoneUtil->isValidNumber($phoneNumber)) {
                return $this->phoneUtil->format($phoneNumber, \libphonenumber\PhoneNumberFormat::INTERNATIONAL);
            }
        } catch (\libphonenumber\NumberParseException $e) {
            //
        }
        return $number;
    }
}