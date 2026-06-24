<?php

declare(strict_types=1);

namespace App\Service\Bitcoin;

final class AddressInfoProvider
{
    public function __construct(
        private readonly MempoolClient $mempoolClient,
    ) {
    }

    public function getBalance(string $address): array
    {
        $data = $this->mempoolClient->getAddressInfo($address);

        $funded = $data['chain_stats']['funded_txo_sum'] ?? 0;
        $spent = $data['chain_stats']['spent_txo_sum'] ?? 0;
        $balanceSatoshis = $funded - $spent;

        return [
            'address' => $address,
            'balance_satoshis' => $balanceSatoshis,
            'balance_btc' => round($balanceSatoshis / 100_000_000, 8),
            'tx_count' => $data['chain_stats']['tx_count'] ?? 0,
        ];
    }

    public function getTransactions(string $address): array
    {
        $transactions = $this->mempoolClient->getAddressTransactions($address);

        return array_map(fn(array $tx) => [
            'txid' => $tx['txid'],
            'confirmed' => $tx['status']['confirmed'] ?? false,
            'block_time' => isset($tx['status']['block_time'])
                ? (new \DateTimeImmutable('@' . $tx['status']['block_time']))->format(\DateTimeInterface::ATOM)
                : null,
            'value_satoshis' => $this->calculateValue($tx, $address),
        ], $transactions);
    }

    private function calculateValue(array $tx, string $address): int
    {
        $received = 0;
        $sent = 0;

        foreach ($tx['vout'] as $output) {
            if (($output['scriptpubkey_address'] ?? '') === $address) {
                $received += $output['value'];
            }
        }

        foreach ($tx['vin'] as $input) {
            if (($input['prevout']['scriptpubkey_address'] ?? '') === $address) {
                $sent += $input['prevout']['value'];
            }
        }

        return $received - $sent;
    }
}