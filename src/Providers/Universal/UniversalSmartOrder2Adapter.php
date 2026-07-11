<?php

namespace Iabduul7\ThemeParkAdapters\Providers\Universal;

use DateTimeInterface;
use Iabduul7\ThemeParkAdapters\Abstracts\AbstractSmartOrderAdapter;
use Iabduul7\ThemeParkAdapters\Contracts\Capabilities\ProvidesTicketArtifacts;
use Iabduul7\ThemeParkAdapters\Contracts\TokenRepositoryInterface;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Collections\CatalogEntryCollection;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\CatalogEntry;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\TicketArtifact;
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Universal Orlando adapter (SmartOrder / "SmartOrder2"). Drop-in compatible with
 * the upstream CodeCreatives\LaravelSmartOrder client: OAuth2 bearer auth with
 * customerId injection (handled by the SmartOrder base), the /smartorder/* event
 * and order endpoints (inherited via SupportsEvents), plus the product catalog and
 * available-months helpers below.
 */
class UniversalSmartOrder2Adapter extends AbstractSmartOrderAdapter implements ProvidesTicketArtifacts
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [], ?TokenRepositoryInterface $tokenRepository = null)
    {
        parent::__construct($config, $tokenRepository);

        if (! $this->hasRequiredConfig(['client_username', 'client_secret', 'customer_id'])) {
            throw ThemeParkApiException::invalidCredentials();
        }
    }

    public function getProviderName(): string
    {
        return 'universal';
    }

    /**
     * Fetch the full SmartOrder product catalog (MyProductCatalog).
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getAllProducts(array $parameters = []): array
    {
        return $this->getRequest('smartorder/MyProductCatalog', $parameters) ?? [];
    }

    /**
     * Opt-in building block over getAllProducts(): fetch the catalog and return a
     * filterable collection of typed {@see CatalogEntry} objects (the nested
     * catalogBySalesProgram[] flattened, each entry tagged with its sales program).
     *
     * $options are passed through to MyProductCatalog as native query params:
     *  - startDateInclusive / endDateInclusive (string|DateTimeInterface) — bound the
     *    pricesByDay calendar; REQUIRED for any pricing to come back.
     *  - retrieveOnly: "future" | "current" — limit to one pricing horizon (default both).
     *  - pricing: "base" | "discounted" — limit to one price bucket (default both).
     *  - collapseDates: bool — fold contiguous equal-price days into ranges.
     *  - salesProgramId: int — the endpoint ignores this server-side, so it is also
     *    applied client-side here to guarantee the filter actually takes effect.
     *
     * customerId is injected by the SmartOrder transport; do not pass it.
     *
     * @param  array<string, mixed>  $options
     */
    public function catalog(array $options = []): CatalogEntryCollection
    {
        $catalog = $this->parseCatalog($this->getAllProducts($this->catalogParameters($options)));

        if (isset($options['salesProgramId'])) {
            $catalog = $catalog->salesProgram((int) $options['salesProgramId']);
        }

        return $catalog;
    }

    /**
     * Normalise friendly option values to the wire format MyProductCatalog expects:
     * dates to Y-m-d, booleans to "true"/"false" (matching the upstream client).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function catalogParameters(array $options): array
    {
        return array_map(static function (mixed $value): mixed {
            if ($value instanceof DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            return $value;
        }, $options);
    }

    /**
     * Flatten catalogBySalesProgram[] into one collection of CatalogEntry objects,
     * denormalising each parent salesProgramId/salesProgramName onto its entries
     * (the entry payload does not carry them natively).
     *
     * @param  array<string, mixed>  $response
     */
    protected function parseCatalog(array $response): CatalogEntryCollection
    {
        $entries = [];

        foreach ($response['catalogBySalesProgram'] ?? [] as $program) {
            $salesProgramId = $program['salesProgramId'] ?? null;
            $salesProgramName = $program['salesProgramName'] ?? null;

            foreach ($program['productCatalogEntries'] ?? [] as $entry) {
                $entry['salesProgramId'] ??= $salesProgramId;
                $entry['salesProgramName'] ??= $salesProgramName;

                $entries[] = (new CatalogEntry($entry))->withAdapter($this);
            }
        }

        return new CatalogEntryCollection($entries);
    }

    /**
     * Next 12 months as selectable options (matches the upstream client shape).
     *
     * @return array<int, array{class: string, text: string, value: string}>
     */
    public function getAvailableMonths(): array
    {
        return collect(range(0, 11))
            ->map(function (int $i): array {
                $month = now()->startOfMonth()->addMonths($i);

                return [
                    'class' => $month->format('Y-m'),
                    'text' => $month->format('F, Y'),
                    'value' => $month->format('Y-m-d'),
                ];
            })
            ->toArray();
    }

    /**
     * SmartOrder returns the redeemable tickets inline in placeOrder's
     * createdTicketResponses[] — one per ticket, each with a visualID (the scannable
     * id), traveler names, plu and calculated validity dates. Epic Universe tickets
     * are scanned as QR upstream; the format hint defaults to CODE39 here since the
     * response carries no product type — the app can upgrade it via the catalog.
     *
     * @param  array<string, mixed>|null  $response
     * @return Collection<int, TicketArtifact>
     */
    public function tickets(?array $response): Collection
    {
        return collect(Arr::get($response, 'createdTicketResponses', []))
            ->map(function ($ticket) use ($response): TicketArtifact {
                $ticket = (array) $ticket;
                $name = trim(Arr::get($ticket, 'firstName', '') . ' ' . Arr::get($ticket, 'lastName', ''));

                return new TicketArtifact([
                    'provider' => 'universal',
                    'identifier' => Arr::get($ticket, 'visualID'),
                    'format' => TicketArtifact::FORMAT_CODE39,
                    'redemption' => TicketArtifact::REDEMPTION_SCAN,
                    'traveler_name' => $name !== '' ? $name : null,
                    'product_name' => Arr::get($ticket, 'productName'),
                    'plu' => Arr::get($ticket, 'plu'),
                    'valid_from' => Arr::get($ticket, 'validityRules.0.calculatedStartDateTime'),
                    'valid_to' => Arr::get($ticket, 'validityRules.0.calculatedExpirationDateTime'),
                    'status' => Arr::get($response, 'success') ? 'CONFIRMED' : null,
                ]);
            })
            ->values();
    }
}
