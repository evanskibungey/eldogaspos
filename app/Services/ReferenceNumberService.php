<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferenceNumberService
{
    /**
     * Generate a unique receipt number for sales with proper locking
     *
     * @return string
     */
    public function generateReceiptNumber(): string
    {
        return DB::transaction(function () {
            $prefix = 'RCP-' . date('Ymd');
            
            // Lock the last receipt for today to prevent race conditions
            $lastSale = DB::table('sales')
                ->where('receipt_number', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            if ($lastSale) {
                // Extract the last number and increment
                $lastNumber = intval(substr($lastSale->receipt_number, -5));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            return $prefix . '-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Generate a unique reference number for cylinder transactions with proper locking
     *
     * @return string
     */
    public function generateCylinderReference(): string
    {
        return DB::transaction(function () {
            $prefix = 'CYL';
            $date = now()->format('Ymd');
            
            // Lock the last cylinder transaction for today to prevent race conditions
            $lastTransaction = DB::table('cylinder_transactions')
                ->where('reference_number', 'like', $prefix . $date . '%')
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            if ($lastTransaction) {
                // Extract the last number and increment
                $lastNumber = intval(substr($lastTransaction->reference_number, -3));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            return $prefix . $date . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Verify if a receipt number is unique
     *
     * @param string $receiptNumber
     * @return bool
     */
    public function isReceiptNumberUnique(string $receiptNumber): bool
    {
        return !DB::table('sales')
            ->where('receipt_number', $receiptNumber)
            ->exists();
    }

    /**
     * Verify if a cylinder reference number is unique
     *
     * @param string $referenceNumber
     * @return bool
     */
    public function isCylinderReferenceUnique(string $referenceNumber): bool
    {
        return !DB::table('cylinder_transactions')
            ->where('reference_number', $referenceNumber)
            ->exists();
    }
}
