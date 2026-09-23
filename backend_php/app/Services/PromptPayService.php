<?php

namespace App\Services;

class PromptPayService
{
    /**
     * Application Identifier (AID) มาตรฐานสำหรับ PromptPay (EMVCo)
     */
    private const PROMPTPAY_AID = 'A000000677010111';

    /**
     * ตรวจสอบว่า PromptPay ID ถูกต้องตามมาตรฐานหรือไม่
     */
    public static function isValidPromptPayId(?string $id): bool
    {
        if (empty($id)) {
            return false;
        }

        $trimmed = trim($id);
        if (str_starts_with(strtoupper($trimmed), 'YOUR_')) {
            return false;
        }

        $digits = preg_replace('/[^0-9]/', '', $trimmed);
        $len = strlen($digits);

        // รองรับ 10 หลัก (เบอร์มือถือ), 13 หลัก (เลขประจำตัวประชาชน/ผู้เสียภาษี), หรือ 15 หลัก (e-Wallet)
        return in_array($len, [10, 13, 15], true);
    }

    /**
     * สร้าง EMVCo QR Code Payload สำหรับ PromptPay
     *
     * @param string $target เบอร์มือถือ (10 หลัก), เลขบัตรประชาชน/ผู้เสียภาษี (13 หลัก), หรือ e-Wallet (15 หลัก)
     * @param float|null $amount ยอดเงินที่ต้องการระบุ (ถ้ามี)
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function generatePayload(string $target, ?float $amount = null): string
    {
        $targetDigits = preg_replace('/[^0-9]/', '', trim($target));

        if (!self::isValidPromptPayId($targetDigits)) {
            throw new \InvalidArgumentException('PromptPay ID ไม่ถูกต้อง ต้องเป็นเบอร์มือถือ 10 หลัก หรือเลขบัตรประชาชน/ผู้เสียภาษี 13 หลัก');
        }

        $targetLength = strlen($targetDigits);

        // Merchant Account Information (Tag 29)
        $aidField = self::formatField('00', self::PROMPTPAY_AID);

        if ($targetLength === 10) {
            // เบอร์โทรศัพท์: ตัด 0 ตัวหน้าออก และเติมรหัสประเทศ 0066 (Sub-tag 01, ความยาว 13 ตัวอักษร)
            $phone = '0066' . substr($targetDigits, 1);
            $targetField = self::formatField('01', $phone);
        } elseif ($targetLength === 13) {
            // เลขประจำตัวประชาชน / เลขผู้เสียภาษี 13 หลัก (Sub-tag 02, ความยาว 13 ตัวอักษร)
            $targetField = self::formatField('02', $targetDigits);
        } else {
            // e-Wallet ID 15 หลัก (Sub-tag 03, ความยาว 15 ตัวอักษร)
            $targetField = self::formatField('03', $targetDigits);
        }

        $tag29 = self::formatField('29', $aidField . $targetField);

        // ประกอบ Payload ตามมาตรฐาน EMVCo Merchant-Presented Mode:
        // Tag 00: Payload Format Indicator (01)
        $payload = self::formatField('00', '01');

        // Tag 01: Point of Initiation Method: 12 (Dynamic เมื่อมียอดเงิน), 11 (Static เมื่อไม่ระบุยอดเงิน)
        $hasAmount = $amount !== null && $amount > 0;
        $payload .= self::formatField('01', $hasAmount ? '12' : '11');

        // Tag 29: ข้อมูลผู้รับชำระ PromptPay
        $payload .= $tag29;

        // Tag 53: Transaction Currency (764 = THB)
        $payload .= self::formatField('53', '764');

        // Tag 54: Transaction Amount (ทศนิยม 2 ตำแหน่ง)
        if ($hasAmount) {
            $formattedAmount = number_format((float) $amount, 2, '.', '');
            $payload .= self::formatField('54', $formattedAmount);
        }

        // Tag 58: Country Code (TH)
        $payload .= self::formatField('58', 'TH');

        // Tag 63: CRC-16 Checksum
        $payloadToCrc = $payload . '6304';
        $crc = self::crc16($payloadToCrc);

        return $payloadToCrc . $crc;
    }

    /**
     * จัดรูปแบบข้อมูล Tag-Length-Value (TLV)
     */
    public static function formatField(string $tag, string $value): string
    {
        $length = str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT);
        return $tag . $length . $value;
    }

    /**
     * คำนวณ CRC16-CCITT (False) ตามมาตรฐาน EMVCo
     * Polynomial: 0x1021, Initial: 0xFFFF
     */
    public static function crc16(string $data): string
    {
        $crc = 0xFFFF;
        $length = strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
