<?php

namespace App\Service;

use App\Repository\TicketRepository;

class AnalyticsService
{
    public function __construct(
        private TicketRepository $ticketRepository,
        )
    {
    }

    /**
     * Returns dashboard summary stats.
     */
    public function getDashboardStats(): array
    {
        $statusCounts = $this->ticketRepository->countByStatus();
        $total = array_sum($statusCounts);

        return [
            'total' => $total,
            'open' => ($statusCounts['pending'] ?? 0) + ($statusCounts['in_progress'] ?? 0),
            'resolved' => $statusCounts['resolved'] ?? 0,
            'rejected' => $statusCounts['rejected'] ?? 0,
            'pending' => $statusCounts['pending'] ?? 0,
            'in_progress' => $statusCounts['in_progress'] ?? 0,
            'topCategory' => $this->ticketRepository->getTopCategory(),
        ];
    }

    /**
     * Returns tickets-by-category data for bar charts.
     * Format: [['category' => 'Bug Report', 'cnt' => 12], ...]
     */
    public function getTicketsByCategory(): array
    {
        return $this->ticketRepository->countByCategory();
    }

    /**
     * Returns status distribution for pie charts.
     * Format: ['pending' => 5, 'in_progress' => 3, ...]
     */
    public function getStatusDistribution(): array
    {
        return $this->ticketRepository->countByStatus();
    }

    /**
     * Returns daily ticket creation trends for line charts.
     * Format: ['2026-01-01' => 3, '2026-01-02' => 1, ...]
     */
    public function getTicketTrends(int $days = 30): array
    {
        return $this->ticketRepository->getTicketTrends($days);
    }
}