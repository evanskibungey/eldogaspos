<?php

namespace App\Traits;

trait GeneratesProductCodes
{
    /**
     * Generate a unique SKU
     * Format: CAT-YYYY-XXXXX (e.g., ELEC-2024-00001)
     */
    protected function generateSKU($categoryPrefix)
    {
        $year = date('Y');
        $lastProduct = $this->model::orderBy('id', 'desc')->first();
        
        $sequence = $lastProduct ? (intval(substr($lastProduct->sku, -5)) + 1) : 1;
        
        return sprintf(
            '%s-%s-%05d',
            strtoupper($categoryPrefix),
            $year,
            $sequence
        );
    }

    /**
     * Generate a unique serial number
     * Format: PRDYYYYMMDDXXXXX (e.g., PRD202402230001)
     */
    protected function generateSerialNumber()
    {
        $prefix = 'PRD';
        $date = date('Ymd');
        $lastProduct = $this->model::orderBy('id', 'desc')->first();
        
        $sequence = $lastProduct ? (intval(substr($lastProduct->serial_number, -5)) + 1) : 1;
        
        return sprintf(
            '%s%s%05d',
            $prefix,
            $date,
            $sequence
        );
    }
}