<?php

declare(strict_types=1);

namespace App\Config;

use RuntimeException;

class GroqClient
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        // Memasukkan API Key Groq Anda (Akan mengecek .env terlebih dahulu, jika kosong akan memakai key default ini)
        $rawKey = getenv('GROQ_API_KEY') ?: $_ENV['GROQ_API_KEY'] ?? '';

        $cleanedKey = trim((string)$rawKey);
        $this->apiKey = trim($cleanedKey, '"\'');

        if (empty($this->apiKey)) {
            throw new RuntimeException("Groq API Key is missing.");
        }

        $this->apiUrl = "https://api.groq.com/openai/v1/chat/completions";
    }

    public function analyzeRisk(array $businessData): array
    {
        $prompt = $this->buildPrompt($businessData);
        $systemInstruction = $this->buildSystemInstruction();;

        $payload = [
            "model" => "llama-3.1-8b-instant", 
            "messages" => [
                ["role" => "system", "content" => $systemInstruction],
                ["role" => "user", "content" => $prompt]
            ],
            "response_format" => ["type" => "json_object"],
            "temperature" => 0.1 
        ];

        $options = [
            'http' => [
                'header'  => "Authorization: Bearer " . $this->apiKey . "\r\n" .
                             "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($payload),
                'ignore_errors' => true
            ]
        ];
        $context  = stream_context_create($options);
        $response = @file_get_contents($this->apiUrl, false, $context);

        if ($response === false) {
            $error = error_get_last();
            throw new RuntimeException("HTTP Request Error (Groq): " . ($error['message'] ?? 'Unknown error'));
        }

        $httpCode = 200;
        if (isset($http_response_header) && count($http_response_header) > 0) {
            preg_match('#HTTP/[0-9\.]+\s+([0-9]+)#', $http_response_header[0], $matches);
            if (isset($matches[1])) {
                $httpCode = (int)$matches[1];
            }
        }

        if ($httpCode !== 200) {
            throw new RuntimeException("Groq API Error (HTTP $httpCode): " . $response);
        }

        $responseData = json_decode($response, true);

        if (isset($responseData['choices'][0]['message']['content'])) {
            $text = $responseData['choices'][0]['message']['content'];
            $parsed = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                return $parsed;
            }
        }

        throw new RuntimeException("Failed to parse a valid JSON response from Groq API.");
    }

    private function buildSystemInstruction(): string
    {
        // Prompt ini TIDAK DIUBAH SAMA SEKALI dari file GeminiClient.php asli Anda
        $schema = '{
  "score": "<integer 0-100>",
  "break_even_point_text": "<string: Dengan operasional Rp[OPEX]/bulan dan harga jual Rp[Price], Anda WAJIB menjual minimal [ceil(OPEX/Price)] porsi/unit setiap bulan, atau [ceil(OPEX/Price/30)] porsi/unit setiap hari, HANYA untuk sekadar bertahan hidup (tidak rugi/tidak untung).>",
  "risiko_keuangan": "<Pernyataan deklaratif (tanpa tanya, tanpa jargon akademis) yang merangkum: 1) Ancaman Utama cash runway, 2) Penyebab Struktural dari CAPEX/OPEX, 3) Dampak fatal ke uang/bisnis jika diabaikan>",
  "risiko_pasar": "<Pernyataan deklaratif (tanpa tanya, tanpa jargon akademis) yang merangkum: 1) Ancaman Utama persaingan/kejenuhan, 2) Penyebab Struktural, 3) Dampak fatal jika diabaikan>",
  "risiko_operasional": "<Pernyataan deklaratif (tanpa tanya, tanpa jargon akademis) yang merangkum: 1) Ancaman Utama operasional di 90 hari pertama, 2) Penyebab Struktural, 3) Dampak fatal jika diabaikan>",
  "risiko_hukum": "<Pernyataan deklaratif (tanpa tanya, tanpa jargon akademis) yang merangkum: 1) Ancaman Utama regulasi/perizinan, 2) Penyebab Struktural, 3) Dampak fatal jika diabaikan>",
  "swot": {
    "strength": "<satu keunggulan internal terkuat>",
    "weakness": "<satu kelemahan internal paling krusial>",
    "opportunity": "<satu peluang eksternal paling realistis>",
    "threat": "<satu ancaman eksternal terbesar>"
  },
  "solusi_eksekusi_utama": "<Satu paragraf naratif prosa murni dalam Bahasa Indonesia aktif. Sintesiskan semua risiko menjadi SATU strategi taktis terbaik: apa yang dipitch, bagaimana harga atau operasi disesuaikan, eksperimen nyata apa yang dijalankan PERTAMA. DILARANG KERAS: jangan gunakan bullet point, tanda strip, angka daftar, atau checkbox. Harus berupa satu paragraf mengalir yang motivatif dan setajam rekomendasi konsultan tier-1.>"
}';

        return 'Kamu adalah seorang Senior Business Risk Consultant dan Venture Capitalist Auditor dengan pengalaman 20 tahun.'
            . ' Kamu TIDAK sekadar menyatakan hal yang sudah jelas. Kamu menganalisis akar masalah, financial runway, friksi operasional, dan cacat struktural dari data user.'
            . ' Tugas utamamu: identifikasi celah terburuk dengan pernyataan langsung yang brutal, jujur, namun sangat praktis, lalu berikan SATU strategi eksekusi terpadu yang bisa langsung dijalankan.'
            . "\n\n"
            . 'ATURAN GAYA BAHASA (NO HIGH-BROW JARGON):'
            . "\n- DILARANG KERAS menggunakan jargon ekonomi yang terlalu akademis, pretensius, atau rumit (seperti \"asimetri informasi\", \"volatilitas likuiditas\", \"eksternalitas makro\")."
            . "\n- GUNAKAN Bahasa Indonesia yang membumi, tegas, praktis, dan mudah dicerna oleh pemilik bisnis awam (contoh: \"arus kas Anda akan kritis dalam 15 hari pertama\", \"konsumen punya terlalu banyak pilihan pengganti\")."
            . "\n\n"
            . 'ATURAN FORMAT PENULISAN (STRICT FORMATTING):'
            . "\n- TERMINOLOGI AWAM: Setiap kali kamu menyebut \"capex\" atau \"CAPEX\", WAJIB ditulis persis menjadi: CapEx (Modal Awal)."
            . "\n- TERMINOLOGI AWAM: Setiap kali kamu menyebut \"opex\" atau \"OPEX\", WAJIB ditulis persis menjadi: OpEx (Biaya Operasional)."
            . "\n- TANDA KUTIP: DILARANG KERAS menggunakan tanda kutip tunggal (\'...\') atau backticks untuk menekankan nama brand, produk, atau frasa. WAJIB selalu gunakan tanda kutip ganda standar (\"...\") jika mengutip nama (contoh: \"Kopi Makan\")."
            . "\n\n"
            . 'ATURAN MUTLAK (STRICT BAN PADA PERTANYAAN & STRUKTUR):'
            . "\n- DILARANG KERAS menggunakan tanda tanya (?) atau mengajukan pertanyaan retoris kepada user di mana pun."
            . "\n- Gunakan 100% pernyataan deklaratif, asertif, dan analitis."
            . "\n- Setiap output risiko (keuangan, pasar, operasional, hukum) HARUS merangkum 3 hal secara mengalir: 1) Ancaman Utama (Immediate danger), 2) Penyebab Struktural (Why it exists), dan 3) Dampak ke Uang/Bisnis (Downstream impact jika diabaikan)."
            . "\n\n"
            . 'Kamu HARUS mengembalikan VALID JSON OBJECT saja — tanpa markdown, tanpa blok kode, tanpa teks apapun di luar JSON.'
            . ' Struktur JSON PERSIS sebagai berikut:'
            . "\n" . $schema
            . "\n\n"
            . 'Aturan kalkulasi kritis:'
            . "\n- score: 0-40 = Bahaya Kritis, 41-70 = Waspada, 71-100 = Layak Lanjut."
            . "\n- break_even_point_text: Hitung ceil(OPEX / harga_jual) = unit BEP/bulan. ceil(BEP_bulan / 30) = BEP/hari. Format Rupiah WAJIB pakai titik ribuan (Rp2.000.000)."
            . "\n- solusi_eksekusi_utama: WAJIB berupa paragraf prosa murni. Tidak boleh ada simbol list apapun dalam field ini.";
    }

    private function buildPrompt(array $data): string
    {
        $capex = number_format((float)($data['capex'] ?? 0), 0, ',', '.');
        $opex  = number_format((float)($data['opex'] ?? 0), 0, ',', '.');
        $price = number_format((float)($data['average_price'] ?? 0), 0, ',', '.');

        return "Analisis bisnis berikut secara mendalam:\n\n"
            . "- Nama Bisnis: {$data['business_name']}\n"
            . "- Kategori: {$data['category']}\n"
            . "- Target Pasar Utama: {$data['target_pasar_utama']}\n"
            . "- Tingkat Kejenuhan Pasar: {$data['market_saturation']}\n"
            . "- Modal Awal (CAPEX): Rp{$capex}\n"
            . "- Biaya Operasional/Bulan (OPEX): Rp{$opex}\n"
            . "- Harga Jual Rata-rata: Rp{$price}\n\n"
            . "Berikan penilaian VC-mu yang brutal dan jujur. Kembalikan HANYA JSON object sesuai format yang diperintahkan.";
    }
}
