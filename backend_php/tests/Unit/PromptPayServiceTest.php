<?php

namespace Tests\Unit;

use App\Services\PromptPayService;
use PHPUnit\Framework\TestCase;

class PromptPayServiceTest extends TestCase
{
    /**
     * ทดสอบการคำนวณ CRC16-CCITT (False) เทียบกับ Test Vector มาตรฐานสากล
     * ข้อมูลนำเข้า "123456789" ต้องได้ผลลัพธ์ "29B1" ตามมาตรฐาน CRC-16/CCITT-FALSE
     * (Polynomial 0x1021, Initial 0xFFFF, No Reflection, No Final XOR)
     */
    public function test_crc16_standard_vector()
    {
        $input = '123456789';
        $expectedCrc = '29B1';
        $actualCrc = PromptPayService::crc16($input);

        $this->assertEquals($expectedCrc, $actualCrc, 'CRC-16 ของ "123456789" ต้องได้ 29B1');
    }

    /**
     * ทดสอบการสร้าง PromptPay QR Payload สำหรับเบอร์มือถือ
     * อ้างอิงตามมาตรฐาน EMVCo Merchant-Presented Mode และมาตรฐาน Thai QR Payment (BOT):
     * - Tag 00: 01 (Version)
     * - Tag 01: 12 (Dynamic QR with amount)
     * - Tag 29: Merchant Account Information
     *     - Sub-tag 00: A000000677010111 (PromptPay AID)
     *     - Sub-tag 01: 0066812345678 (เบอร์มือถือแปลงเป็น 0066 + 9 หลัก)
     * - Tag 53: 764 (THB)
     * - Tag 54: 100.00 (ยอดเงิน)
     * - Tag 58: TH (ประเทศไทย)
     * - Tag 63: 6304 + CRC16
     */
    public function test_generate_payload_mobile_phone_with_amount()
    {
        $mobile = '0812345678';
        $amount = 100.00;

        $payload = PromptPayService::generatePayload($mobile, $amount);

        // ตรวจสอบส่วนประกอบโครงสร้าง TLV
        $this->assertStringStartsWith('000201010212', $payload);
        $this->assertStringContainsString('29370016A00000067701011101130066812345678', $payload);
        $this->assertStringContainsString('5303764', $payload);
        $this->assertStringContainsString('5406100.00', $payload);
        $this->assertStringContainsString('5802TH', $payload);
        $this->assertStringContainsString('6304', $payload);

        // ตรวจสอบความถูกต้องของ Checksum ที่แนบอยู่ท้าย Payload
        $payloadBody = substr($payload, 0, -4);
        $checksum = substr($payload, -4);
        $this->assertEquals(PromptPayService::crc16($payloadBody), $checksum);
    }

    /**
     * ทดสอบการสร้าง PromptPay QR Payload สำหรับเลขประจำตัวประชาชน/ผู้เสียภาษี 13 หลัก
     * Sub-tag 02 ความยาว 13 หลัก
     */
    public function test_generate_payload_national_id_with_amount()
    {
        $nationalId = '1234567890123';
        $amount = 350.50;

        $payload = PromptPayService::generatePayload($nationalId, $amount);

        $this->assertStringStartsWith('000201010212', $payload);
        // Sub-tag 02 สำหรับเลขประจำตัวประชาชน 13 หลัก
        $this->assertStringContainsString('29370016A00000067701011102131234567890123', $payload);
        $this->assertStringContainsString('5303764', $payload);
        $this->assertStringContainsString('5406350.50', $payload);
        $this->assertStringContainsString('5802TH', $payload);

        $payloadBody = substr($payload, 0, -4);
        $checksum = substr($payload, -4);
        $this->assertEquals(PromptPayService::crc16($payloadBody), $checksum);
    }

    /**
     * ทดสอบการปฏิเสธ PromptPay ID ที่ไม่ถูกต้อง (เช่น เป็น placeholder หรือความยาวไม่ตรง)
     */
    public function test_invalid_promptpay_id_validation()
    {
        $this->assertFalse(PromptPayService::isValidPromptPayId(''));
        $this->assertFalse(PromptPayService::isValidPromptPayId('YOUR_PROMPTPAY_ID_HERE'));
        $this->assertFalse(PromptPayService::isValidPromptPayId('12345')); // ความยาวผิด
        $this->assertTrue(PromptPayService::isValidPromptPayId('0812345678')); // 10 หลัก
        $this->assertTrue(PromptPayService::isValidPromptPayId('1234567890123')); // 13 หลัก
    }
}
