<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class GeoDataController extends BaseController
{
    private const REGION_DEFAULT = '12';
    private static array $provinceRecords = [];
    private static array $cityRecords = [];
    private static array $barangayRecords = [];

    public function provinces(): ResponseInterface
    {
        try {
            $regionCode = $this->request->getGet('region') ?? self::REGION_DEFAULT;
            $records = array_filter($this->getProvinceRecords(), static fn ($record) => $record['regCode'] === $regionCode);
            $data = array_map(fn ($record) => [
                'code' => $record['provCode'],
                'name' => $this->formatName($record['provDesc'] ?? ''),
            ], $records);

            return $this->respondSuccess(array_values($data));
        } catch (\Throwable $e) {
            log_message('error', 'GeoDataController::provinces error: ' . $e->getMessage());
            return $this->respondError('Failed to load provinces', ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cities(): ResponseInterface
    {
        try {
            $provinceCode = $this->request->getGet('province');
            if (empty($provinceCode)) {
                return $this->respondError('Province code is required');
            }

            $records = array_filter($this->getCityRecords(), static fn ($record) => $record['provCode'] === $provinceCode);
            $data = array_map(fn ($record) => [
                'code' => $record['citymunCode'],
                'name' => $this->formatName($record['citymunDesc'] ?? ''),
            ], $records);

            return $this->respondSuccess(array_values($data));
        } catch (\Throwable $e) {
            log_message('error', 'GeoDataController::cities error: ' . $e->getMessage());
            return $this->respondError('Failed to load municipalities', ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function barangays(): ResponseInterface
    {
        try {
            $cityCode = $this->request->getGet('city');
            if (empty($cityCode)) {
                return $this->respondError('City/municipality code is required');
            }

            $records = array_filter($this->getBarangayRecords(), static fn ($record) => $record['citymunCode'] === $cityCode);
            $data = array_map(fn ($record) => [
                'code' => $record['brgyCode'],
                'name' => $this->formatName($record['brgyDesc'] ?? ''),
            ], $records);

            return $this->respondSuccess(array_values($data));
        } catch (\Throwable $e) {
            log_message('error', 'GeoDataController::barangays error: ' . $e->getMessage());
            return $this->respondError('Failed to load barangays', ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getProvinceRecords(): array
    {
        if (!empty(self::$provinceRecords)) {
            return self::$provinceRecords;
        }

        self::$provinceRecords = $this->loadReferenceData('refprovince');
        return self::$provinceRecords;
    }

    private function getCityRecords(): array
    {
        if (!empty(self::$cityRecords)) {
            return self::$cityRecords;
        }

        self::$cityRecords = $this->loadReferenceData('refcitymun');
        return self::$cityRecords;
    }

    private function getBarangayRecords(): array
    {
        if (!empty(self::$barangayRecords)) {
            return self::$barangayRecords;
        }

        self::$barangayRecords = $this->loadReferenceData('refbrgy');
        return self::$barangayRecords;
    }

    private function loadReferenceData(string $filename): array
    {
        $path = FCPATH . 'data/' . $filename . '.json';
        if (!is_file($path)) {
            throw new \RuntimeException("Reference file not found: {$filename}");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Unable to read {$filename}");
        }

        $json = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        return $json['RECORDS'] ?? [];
    }

    private function respondSuccess(array $data): ResponseInterface
    {
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    private function respondError(string $message, int $status = ResponseInterface::HTTP_BAD_REQUEST): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setJSON([
                'status'  => 'error',
                'message' => $message,
            ]);
    }

    private function formatName(string $name): string
    {
        $lower = strtolower($name);
        $formatted = preg_replace_callback('/\b([a-z])/', static fn ($match) => strtoupper($match[1]), $lower);
        return str_replace(['Ii', 'Iii'], ['II', 'III'], $formatted ?? $name);
    }
}
