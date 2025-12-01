<?php

namespace iabduul7\ThemeParkBooking\Services;

use iabduul7\ThemeParkBooking\Data\VoucherData;
use iabduul7\ThemeParkBooking\Exceptions\AdapterException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class VoucherGenerator
{
    protected string $storageDisk;
    protected string $templatesPath;

    public function __construct(string $storageDisk = 'public', string $templatesPath = 'voucher-templates')
    {
        $this->storageDisk = $storageDisk;
        $this->templatesPath = $templatesPath;
    }

    /**
     * Generate a complete voucher with PDF and download capabilities
     */
    public function generateVoucherWithPdf(VoucherData $voucherData, array $options = []): VoucherData
    {
        // Generate QR code if not provided
        if (empty($voucherData->qrCode) || $options['regenerate_qr'] ?? false) {
            $qrCodeData = $this->generateQRCode($voucherData);
        } else {
            $qrCodeData = $voucherData->qrCode;
        }

        // Generate barcode if not provided
        if (empty($voucherData->barcodeData) || $options['regenerate_barcode'] ?? false) {
            $barcodeData = $this->generateBarcode($voucherData);
        } else {
            $barcodeData = $voucherData->barcodeData;
        }

        // Determine template based on provider
        $template = $this->getTemplate($voucherData, $options);
        
        // Generate PDF
        $pdfPath = $this->generatePDF($voucherData, $template, $options);
        
        // Generate download URL
        $downloadUrl = $this->getDownloadUrl($pdfPath);

        return new VoucherData(
            bookingId: $voucherData->bookingId,
            voucherNumber: $voucherData->voucherNumber,
            qrCode: $qrCodeData,
            barcodeData: $barcodeData,
            customerInfo: $voucherData->customerInfo,
            productInfo: $voucherData->productInfo,
            bookingDetails: $voucherData->bookingDetails,
            pdfPath: $pdfPath,
            downloadUrl: $downloadUrl,
            instructions: $this->getInstructions($voucherData),
            metadata: array_merge($voucherData->metadata, [
                'generated_at' => Carbon::now()->toISOString(),
                'template_used' => $template,
                'options' => $options,
            ])
        );
    }

    /**
     * Generate QR code for voucher
     */
    protected function generateQRCode(VoucherData $voucherData): string
    {
        $qrData = [
            'booking_id' => $voucherData->bookingId,
            'voucher_number' => $voucherData->voucherNumber,
            'customer_name' => $voucherData->getCustomerName(),
            'product_name' => $voucherData->getProductName(),
            'date' => $voucherData->getBookingDate(),
            'time' => $voucherData->getTimeSlot(),
            'quantity' => $voucherData->getQuantity(),
        ];

        // Generate QR code image
        $qrCodePath = $this->storeQRCode($qrData, $voucherData->voucherNumber);
        
        return $qrCodePath;
    }

    /**
     * Generate barcode for voucher
     */
    protected function generateBarcode(VoucherData $voucherData): string
    {
        // Generate barcode data - typically the voucher number or booking ID
        $barcodeData = $voucherData->voucherNumber ?: $voucherData->bookingId;
        
        // You might want to use a barcode library here
        // For now, returning the data itself
        return $barcodeData;
    }

    /**
     * Determine which template to use based on provider and options
     */
    protected function getTemplate(VoucherData $voucherData, array $options = []): string
    {
        // Check if template is specified in options
        if (isset($options['template'])) {
            return $options['template'];
        }

        // Determine template based on provider from metadata
        $provider = $voucherData->metadata['provider'] ?? 'default';
        $parkType = $voucherData->metadata['park_type'] ?? null;

        switch ($provider) {
            case 'redeam':
                if ($parkType === 'disney') {
                    return 'disney.will-call-confirmation';
                } elseif ($parkType === 'united_parks') {
                    return 'united-parks.ez-ticket';
                }
                return 'redeam.default';

            case 'smartorder':
                return 'universal.standard-ticket';

            default:
                return 'default.standard';
        }
    }

    /**
     * Generate PDF voucher
     */
    protected function generatePDF(VoucherData $voucherData, string $template, array $options = []): string
    {
        // Prepare data for the view
        $viewData = [
            'voucher' => $voucherData,
            'customer' => $voucherData->customerInfo,
            'product' => $voucherData->productInfo,
            'booking' => $voucherData->bookingDetails,
            'qr_code' => $this->getQRCodeImagePath($voucherData->qrCode),
            'barcode' => $voucherData->barcodeData,
            'instructions' => $voucherData->instructions,
            'generated_at' => Carbon::now(),
        ];

        // Build template path
        $templatePath = "{$this->templatesPath}.{$template}";

        // Check if view exists
        if (!View::exists($templatePath)) {
            throw new AdapterException("Voucher template not found: {$templatePath}");
        }

        // Generate HTML content
        $html = View::make($templatePath, $viewData)->render();

        // Generate PDF
        $pdf = Pdf::loadHTML($html);
        
        // Configure PDF options
        $pdf->setPaper($options['paper_size'] ?? 'letter', $options['orientation'] ?? 'portrait');
        
        if (isset($options['margins'])) {
            $pdf->setOptions(['margins' => $options['margins']]);
        }

        // Generate filename and path
        $filename = $this->generatePdfFilename($voucherData);
        $directory = "vouchers/{$voucherData->metadata['provider'] ?? 'default'}";
        $fullPath = "{$directory}/{$filename}";

        // Ensure directory exists
        Storage::disk($this->storageDisk)->makeDirectory($directory);

        // Save PDF
        Storage::disk($this->storageDisk)->put($fullPath, $pdf->output());

        return $fullPath;
    }

    /**
     * Store QR code image
     */
    protected function storeQRCode(array $data, string $identifier): string
    {
        $qrCodeContent = json_encode($data);
        
        // Generate QR code image
        $qrCodeImage = QrCode::format('png')
            ->size(200)
            ->margin(2)
            ->generate($qrCodeContent);

        // Store QR code image
        $filename = "qr-{$identifier}-" . time() . ".png";
        $path = "vouchers/qr-codes/{$filename}";
        
        Storage::disk($this->storageDisk)->put($path, $qrCodeImage);

        return Storage::disk($this->storageDisk)->url($path);
    }

    /**
     * Get QR code image path for PDF inclusion
     */
    protected function getQRCodeImagePath(string $qrCodeUrl): string
    {
        // Convert URL to local path if needed
        return $qrCodeUrl;
    }

    /**
     * Generate PDF filename
     */
    protected function generatePdfFilename(VoucherData $voucherData): string
    {
        $customerName = Str::slug($voucherData->getCustomerName());
        $date = Carbon::now()->format('Y-m-d');
        
        return "voucher-{$voucherData->voucherNumber}-{$customerName}-{$date}.pdf";
    }

    /**
     * Get download URL for PDF
     */
    protected function getDownloadUrl(string $pdfPath): string
    {
        return Storage::disk($this->storageDisk)->url($pdfPath);
    }

    /**
     * Get instructions based on provider and booking details
     */
    protected function getInstructions(VoucherData $voucherData): array
    {
        $provider = $voucherData->metadata['provider'] ?? 'default';
        $parkType = $voucherData->metadata['park_type'] ?? null;

        switch ($provider) {
            case 'redeam':
                if ($parkType === 'disney') {
                    return $this->getDisneyInstructions($voucherData);
                } elseif ($parkType === 'united_parks') {
                    return $this->getUnitedParksInstructions($voucherData);
                }
                break;

            case 'smartorder':
                return $this->getUniversalInstructions($voucherData);
        }

        return $this->getDefaultInstructions($voucherData);
    }

    /**
     * Get Disney-specific instructions
     */
    protected function getDisneyInstructions(VoucherData $voucherData): array
    {
        return [
            'Bring this voucher and a valid photo ID to the park entrance.',
            'Exchange this voucher for your park tickets at the Will Call window.',
            'Allow extra time for ticket pickup before park opening.',
            'Keep your tickets safe - they cannot be replaced if lost.',
            'Tickets are valid for the date specified only.',
        ];
    }

    /**
     * Get United Parks instructions
     */
    protected function getUnitedParksInstructions(VoucherData $voucherData): array
    {
        return [
            'Present this EZ-Ticket at the park entrance turnstiles.',
            'The barcode will be scanned for admission.',
            'Keep this voucher with you throughout your visit.',
            'This ticket is valid for one admission on the specified date.',
            'Contact guest services for any issues with ticket scanning.',
        ];
    }

    /**
     * Get Universal Studios instructions
     */
    protected function getUniversalInstructions(VoucherData $voucherData): array
    {
        return [
            'Present this voucher at the park entrance for admission.',
            'Arrive at least 30 minutes before your scheduled time.',
            'Bring a valid photo ID matching the name on the reservation.',
            'Follow all park safety guidelines and restrictions.',
            'Contact customer service for changes or cancellations.',
        ];
    }

    /**
     * Get default instructions
     */
    protected function getDefaultInstructions(VoucherData $voucherData): array
    {
        return [
            'Present this voucher at the designated location.',
            'Arrive on time for your scheduled booking.',
            'Bring valid identification if required.',
            'Follow all venue rules and guidelines.',
            'Contact customer service for assistance.',
        ];
    }

    /**
     * Validate voucher data before generation
     */
    protected function validateVoucherData(VoucherData $voucherData): void
    {
        if (empty($voucherData->bookingId)) {
            throw new AdapterException('Booking ID is required for voucher generation');
        }

        if (empty($voucherData->voucherNumber)) {
            throw new AdapterException('Voucher number is required');
        }

        if (empty($voucherData->customerInfo)) {
            throw new AdapterException('Customer information is required for voucher generation');
        }
    }

    /**
     * Get voucher storage statistics
     */
    public function getStorageStats(): array
    {
        $disk = Storage::disk($this->storageDisk);
        $voucherFiles = $disk->files('vouchers');
        
        $totalSize = 0;
        $fileCount = 0;
        
        foreach ($voucherFiles as $file) {
            if ($disk->exists($file)) {
                $totalSize += $disk->size($file);
                $fileCount++;
            }
        }

        return [
            'total_files' => $fileCount,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'storage_disk' => $this->storageDisk,
        ];
    }

    /**
     * Cleanup old voucher files
     */
    public function cleanupOldVouchers(int $daysOld = 90): array
    {
        $disk = Storage::disk($this->storageDisk);
        $cutoffDate = Carbon::now()->subDays($daysOld);
        
        $voucherFiles = $disk->files('vouchers');
        $deletedFiles = [];
        
        foreach ($voucherFiles as $file) {
            if ($disk->exists($file)) {
                $lastModified = Carbon::createFromTimestamp($disk->lastModified($file));
                
                if ($lastModified->lt($cutoffDate)) {
                    $disk->delete($file);
                    $deletedFiles[] = $file;
                }
            }
        }

        return [
            'deleted_count' => count($deletedFiles),
            'deleted_files' => $deletedFiles,
            'cutoff_date' => $cutoffDate->toDateString(),
        ];
    }
}