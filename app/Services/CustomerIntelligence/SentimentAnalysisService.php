<?php

namespace App\Services\CustomerIntelligence;

use App\Models\ETL\FactCustomerSentiment;
use App\Models\Backend\Support;
use App\Models\Backend\SupportChat;
use App\Models\ETL\DimensionSentimentCategories;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SentimentAnalysisService
{
    private const PYTHON_API_URL = 'http://localhost:8001/api/sentiment';
    
    /**
     * Analyze sentiment for a specific support ticket
     */
    public function analyzeTicketSentiment(int $ticketId): array
    {
        $ticket = Support::find($ticketId);
        
        if (!$ticket) {
            throw new \Exception("Support ticket not found: {$ticketId}");
        }

        // Get ticket content for analysis
        $content = $this->extractTicketContent($ticket);
        
        // Perform sentiment analysis
        $sentimentResult = $this->performSentimentAnalysis($content);
        
        // Calculate NPS score
        $npsScore = $this->calculateNpsScore($sentimentResult, $ticket);
        
        // Categorize feedback
        $category = $this->categorizeFeedback($content, $sentimentResult);
        
        // Create sentiment record
        $sentimentRecord = $this->createSentimentRecord($ticket, $sentimentResult, $npsScore, $category);
        
        return [
            'ticket_id' => $ticketId,
            'sentiment_score' => $sentimentResult['sentiment_score'],
            'nps_score' => $npsScore,
            'emotion' => $sentimentResult['primary_emotion'],
            'confidence' => $sentimentResult['confidence'],
            'category' => $category,
            'keywords' => $sentimentResult['keywords'],
            'sentiment_record' => $sentimentRecord
        ];
    }

    /**
     * Batch analyze all pending support tickets
     */
    public function batchAnalyzePendingTickets(): array
    {
        $pendingTickets = Support::where('status', 'pending')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->limit(1000)
            ->get();

        $results = [
            'processed' => 0,
            'errors' => [],
            'sentiment_summary' => []
        ];

        foreach ($pendingTickets as $ticket) {
            try {
                $this->analyzeTicketSentiment($ticket->id);
                $results['processed']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'ticket_id' => $ticket->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Generate sentiment summary
        $results['sentiment_summary'] = $this->generateSentimentSummary();

        return $results;
    }

    /**
     * Get customer sentiment analysis for a specific client
     */
    public function getCustomerSentiment(int $clientKey, int $days = 90): array
    {
        $startDate = Carbon::now()->subDays($days);
        $startDateKey = $startDate->format('Ymd');

        $sentimentData = FactCustomerSentiment::where('client_key', $clientKey)
            ->where('sentiment_date_key', '>=', $startDateKey)
            ->with('client')
            ->get();

        if ($sentimentData->isEmpty()) {
            return $this->createEmptySentimentAnalysis($clientKey);
        }

        return [
            'client_key' => $clientKey,
            'period_days' => $days,
            'total_tickets' => $sentimentData->count(),
            'average_sentiment' => $sentimentData->avg('sentiment_score'),
            'average_nps' => $sentimentData->avg('nps_score'),
            'sentiment_trend' => $this->calculateSentimentTrend($sentimentData),
            'emotion_distribution' => $this->getEmotionDistribution($sentimentData),
            'category_distribution' => $this->getCategoryDistribution($sentimentData),
            'latest_sentiment' => $sentimentData->sortByDesc('sentiment_date_key')->first(),
            'sentiment_changes' => $this->identifySentimentChanges($sentimentData),
            'critical_issues' => $this->identifyCriticalIssues($sentimentData)
        ];
    }

    /**
     * Get overall sentiment analysis for all customers
     */
    public function getOverallSentimentAnalysis(int $days = 90): array
    {
        $startDate = Carbon::now()->subDays($days);
        $startDateKey = $startDate->format('Ymd');

        $sentimentData = FactCustomerSentiment::where('sentiment_date_key', '>=', $startDateKey)
            ->with('client')
            ->get();

        return [
            'period_days' => $days,
            'total_tickets_analyzed' => $sentimentData->count(),
            'overall_sentiment_score' => $sentimentData->avg('sentiment_score'),
            'overall_nps' => $this->calculateOverallNps($sentimentData),
            'sentiment_distribution' => $this->getSentimentDistribution($sentimentData),
            'nps_distribution' => $this->getNpsDistribution($sentimentData),
            'emotion_trends' => $this->getEmotionTrends($sentimentData),
            'category_trends' => $this->getCategoryTrends($sentimentData),
            'customer_satisfaction' => $this->calculateCustomerSatisfaction($sentimentData),
            'critical_feedback' => $this->getCriticalFeedback($sentimentData),
            'improvement_areas' => $this->identifyImprovementAreas($sentimentData)
        ];
    }

    /**
     * Monitor sentiment alerts and spikes
     */
    public function monitorSentimentAlerts(): array
    {
        $alerts = [];
        
        // Check for negative sentiment spikes
        $negativeSpike = $this->detectNegativeSentimentSpike();
        if ($negativeSpike) {
            $alerts[] = [
                'type' => 'negative_sentiment_spike',
                'severity' => 'high',
                'message' => 'Detected spike in negative customer sentiment',
                'data' => $negativeSpike
            ];
        }

        // Check for NPS drops
        $npsDrop = $this->detectNpsDrop();
        if ($npsDrop) {
            $alerts[] = [
                'type' => 'nps_drop',
                'severity' => 'high',
                'message' => 'Customer NPS score has dropped significantly',
                'data' => $npsDrop
            ];
        }

        // Check for critical issues
        $criticalIssues = $this->detectCriticalIssues();
        if ($criticalIssues) {
            $alerts[] = [
                'type' => 'critical_issues',
                'severity' => 'critical',
                'message' => 'Multiple critical customer issues detected',
                'data' => $criticalIssues
            ];
        }

        return $alerts;
    }

    /**
     * Generate automated insights from sentiment data
     */
    public function generateInsights(int $clientKey = null): array
    {
        $sentimentData = $clientKey 
            ? FactCustomerSentiment::where('client_key', $clientKey)->get()
            : FactCustomerSentiment::all();

        return [
            'key_insights' => $this->extractKeyInsights($sentimentData),
            'sentiment_drivers' => $this->identifySentimentDrivers($sentimentData),
            'satisfaction_trends' => $this->analyzeSatisfactionTrends($sentimentData),
            'recommendations' => $this->generateRecommendations($sentimentData),
            'action_items' => $this->identifyActionItems($sentimentData)
        ];
    }

    private function extractTicketContent(Support $ticket): string
    {
        $content = $ticket->subject . ' ' . $ticket->description;
        
        // Include chat messages if available
        if ($ticket->supportChats) {
            $chatContent = $ticket->supportChats->pluck('message')->implode(' ');
            $content .= ' ' . $chatContent;
        }
        
        return trim($content);
    }

    private function performSentimentAnalysis(string $content): array
    {
        try {
            // Try Python API first
            $response = Http::timeout(30)->post(self::PYTHON_API_URL, [
                'text' => $content,
                'language' => 'en'
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning('Python sentiment analysis failed, falling back to PHP analysis', [
                'error' => $e->getMessage()
            ]);
        }

        // Fallback to PHP-based sentiment analysis
        return $this->performPhpSentimentAnalysis($content);
    }

    private function performPhpSentimentAnalysis(string $content): array
    {
        // Simple PHP-based sentiment analysis
        $positiveWords = ['good', 'great', 'excellent', 'amazing', 'wonderful', 'fantastic', 'perfect', 'love', 'satisfied', 'happy'];
        $negativeWords = ['bad', 'terrible', 'awful', 'horrible', 'disappointed', 'frustrated', 'angry', 'hate', 'problem', 'issue', 'error', 'broken', 'slow', 'poor'];
        
        $positiveCount = 0;
        $negativeCount = 0;
        $wordCount = 0;
        
        $words = preg_split('/\s+/', strtolower($content));
        $extractedKeywords = [];
        
        foreach ($words as $word) {
            $word = preg_replace('/[^a-zA-Z]/', '', $word);
            if (strlen($word) < 3) continue;
            
            $wordCount++;
            $extractedKeywords[] = $word;
            
            if (in_array($word, $positiveWords)) $positiveCount++;
            if (in_array($word, $negativeWords)) $negativeCount++;
        }
        
        $totalSentimentWords = $positiveCount + $negativeCount;
        $sentimentScore = $wordCount > 0 ? ($positiveCount - $negativeCount) / max($wordCount * 0.1, 1) : 0;
        
        // Determine primary emotion
        $primaryEmotion = $this->determineEmotion($sentimentScore, $content);
        
        return [
            'sentiment_score' => max(-1, min(1, $sentimentScore)),
            'confidence' => $totalSentimentWords > 0 ? min(0.9, $totalSentimentWords / 10) : 0.3,
            'primary_emotion' => $primaryEmotion,
            'emotion_intensity' => abs($sentimentScore),
            'keywords' => array_slice(array_unique($extractedKeywords), 0, 10),
            'positive_count' => $positiveCount,
            'negative_count' => $negativeCount,
            'model_version' => 'php_fallback_1.0'
        ];
    }

    private function determineEmotion(float $sentimentScore, string $content): string
    {
        if ($sentimentScore > 0.5) return 'joy';
        if ($sentimentScore > 0.1) return 'trust';
        if ($sentimentScore > -0.1) return 'neutral';
        if ($sentimentScore > -0.5) return 'sadness';
        if (preg_match('/\b(angry|mad|furious|rage)\b/i', $content)) return 'anger';
        if (preg_match('/\b(afraid|scared|worried|concerned)\b/i', $content)) return 'fear';
        if (preg_match('/\b(disgusted|disgusting|disgust)\b/i', $content)) return 'disgust';
        if (preg_match('/\b(surprised|shocked|amazed)\b/i', $content)) return 'surprise';
        if (preg_match('/\b(excited|anticipating|expecting)\b/i', $content)) return 'anticipation';
        
        return $sentimentScore > 0 ? 'joy' : 'sadness';
    }

    private function calculateNpsScore(array $sentimentResult, Support $ticket): int
    {
        $sentimentScore = $sentimentResult['sentiment_score'];
        $confidence = $sentimentResult['confidence'];
        
        // Convert sentiment to NPS scale (0-10)
        $npsScore = round((($sentimentScore + 1) / 2) * 10);
        
        // Adjust based on ticket priority and resolution time
        if ($ticket->priority === 'high') {
            $npsScore = max(0, $npsScore - 2);
        }
        
        // Apply confidence weighting
        $weightedScore = $npsScore * $confidence + 5 * (1 - $confidence);
        
        return max(0, min(10, round($weightedScore)));
    }

    private function categorizeFeedback(string $content, array $sentimentResult): string
    {
        $content = strtolower($content);
        
        // Define category keywords
        $categories = [
            'billing_issue' => ['bill', 'billing', 'charge', 'payment', 'invoice', 'refund', 'price'],
            'technical_support' => ['error', 'bug', 'issue', 'problem', 'not working', 'broken', 'technical'],
            'service_quality' => ['slow', 'delay', 'quality', 'service', 'performance'],
            'account_management' => ['account', 'login', 'password', 'profile', 'settings'],
            'feature_request' => ['feature', 'request', 'suggestion', 'improvement', 'would like'],
            'praise' => ['great', 'excellent', 'amazing', 'fantastic', 'love', 'perfect'],
            'complaint' => ['complaint', 'disappointed', 'frustrated', 'terrible', 'awful']
        ];
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($content, $keyword) !== false) {
                    return $category;
                }
            }
        }
        
        return 'general_inquiry';
    }

    private function createSentimentRecord(Support $ticket, array $sentimentResult, int $npsScore, string $category): FactCustomerSentiment
    {
        return FactCustomerSentiment::create([
            'sentiment_key' => $this->generateSentimentKey($ticket->id),
            'client_key' => $ticket->user->client->client_key ?? 0,
            'ticket_key' => $ticket->id,
            'sentiment_date_key' => now()->format('Ymd'),
            'nps_score' => $npsScore,
            'sentiment_score' => $sentimentResult['sentiment_score'],
            'confidence_level' => $sentimentResult['confidence'],
            'feedback_category' => $category,
            'primary_emotion' => $sentimentResult['primary_emotion'],
            'emotion_intensity' => $sentimentResult['emotion_intensity'],
            'language_detected' => 'en',
            'support_channel' => 'ticket_system',
            'ticket_status' => $ticket->status,
            'resolution_time_hours' => $ticket->created_at->diffInHours(now()),
            'customer_satisfaction_rating' => $npsScore,
            'sentiment_trend' => [],
            'feedback_keywords' => $sentimentResult['keywords'],
            'model_version' => $sentimentResult['model_version'],
            'analysis_metadata' => [
                'content_length' => strlen($ticket->description),
                'has_chat_history' => $ticket->supportChats->isNotEmpty(),
                'ticket_priority' => $ticket->priority
            ]
        ]);
    }

    private function generateSentimentKey(int $ticketId): string
    {
        return $ticketId . '_' . now()->format('YmdHis');
    }

    private function createEmptySentimentAnalysis(int $clientKey): array
    {
        return [
            'client_key' => $clientKey,
            'period_days' => 0,
            'total_tickets' => 0,
            'average_sentiment' => 0,
            'average_nps' => 0,
            'sentiment_trend' => [],
            'emotion_distribution' => [],
            'category_distribution' => [],
            'latest_sentiment' => null,
            'message' => 'No sentiment data available for this customer'
        ];
    }

    private function calculateSentimentTrend(Collection $sentimentData): array
    {
        $trend = [];
        $groupedByMonth = $sentimentData->groupBy(function ($item) {
            return Carbon::createFromFormat('Ymd', $item->sentiment_date_key)->format('Y-m');
        });

        foreach ($groupedByMonth as $month => $data) {
            $trend[] = [
                'month' => $month,
                'average_sentiment' => $data->avg('sentiment_score'),
                'average_nps' => $data->avg('nps_score'),
                'ticket_count' => $data->count()
            ];
        }

        return array_values($trend);
    }

    private function getEmotionDistribution(Collection $sentimentData): array
    {
        return $sentimentData->groupBy('primary_emotion')
            ->map(function ($group) {
                return [
                    'emotion' => $group->first()->primary_emotion,
                    'count' => $group->count(),
                    'percentage' => round(($group->count() / $sentimentData->count()) * 100, 1)
                ];
            })->values()->toArray();
    }

    private function getCategoryDistribution(Collection $sentimentData): array
    {
        return $sentimentData->groupBy('feedback_category')
            ->map(function ($group) {
                return [
                    'category' => $group->first()->feedback_category,
                    'count' => $group->count(),
                    'percentage' => round(($group->count() / $sentimentData->count()) * 100, 1),
                    'average_sentiment' => round($group->avg('sentiment_score'), 2)
                ];
            })->values()->toArray();
    }

    private function identifySentimentChanges(Collection $sentimentData): array
    {
        $sorted = $sentimentData->sortBy('sentiment_date_key');
        $changes = [];
        $previousScore = null;

        foreach ($sorted as $record) {
            if ($previousScore !== null) {
                $change = $record->sentiment_score - $previousScore;
                if (abs($change) > 0.3) { // Significant change threshold
                    $changes[] = [
                        'date' => $record->sentiment_date_key,
                        'change' => round($change, 2),
                        'direction' => $change > 0 ? 'improved' : 'declined'
                    ];
                }
            }
            $previousScore = $record->sentiment_score;
        }

        return $changes;
    }

    private function identifyCriticalIssues(Collection $sentimentData): array
    {
        return $sentimentData->where('sentiment_score', '<', -0.5)
            ->where('confidence_level', '>', 0.7)
            ->map(function ($record) {
                return [
                    'ticket_id' => $record->ticket_key,
                    'sentiment_score' => $record->sentiment_score,
                    'category' => $record->feedback_category,
                    'emotion' => $record->primary_emotion,
                    'date' => $record->sentiment_date_key
                ];
            })->values()->toArray();
    }

    private function calculateOverallNps(Collection $sentimentData): int
    {
        $promoters = $sentimentData->where('nps_score', '>=', 9)->count();
        $detractors = $sentimentData->where('nps_score', '<=', 6)->count();
        $total = $sentimentData->count();
        
        if ($total === 0) return 0;
        
        return round((($promoters - $detractors) / $total) * 100);
    }

    private function getSentimentDistribution(Collection $sentimentData): array
    {
        return [
            'very_positive' => $sentimentData->where('sentiment_score', '>=', 0.5)->count(),
            'positive' => $sentimentData->whereBetween('sentiment_score', [0.1, 0.49])->count(),
            'neutral' => $sentimentData->whereBetween('sentiment_score', [-0.1, 0.1])->count(),
            'negative' => $sentimentData->whereBetween('sentiment_score', [-0.5, -0.11])->count(),
            'very_negative' => $sentimentData->where('sentiment_score', '<', -0.5)->count()
        ];
    }

    private function getNpsDistribution(Collection $sentimentData): array
    {
        return [
            'promoters' => $sentimentData->where('nps_score', '>=', 9)->count(),
            'passives' => $sentimentData->whereBetween('nps_score', [7, 8])->count(),
            'detractors' => $sentimentData->where('nps_score', '<=', 6)->count()
        ];
    }

    // Additional helper methods for advanced analysis...
    
    private function detectNegativeSentimentSpike(): ?array
    {
        $currentWeek = FactCustomerSentiment::where('sentiment_date_key', '>=', now()->subWeek()->format('Ymd'))
            ->where('sentiment_score', '<', -0.3)->count();
        
        $previousWeek = FactCustomerSentiment::whereBetween('sentiment_date_key', [
            now()->subWeek(2)->format('Ymd'),
            now()->subWeek()->format('Ymd')
        ])->where('sentiment_score', '<', -0.3)->count();
        
        if ($currentWeek > $previousWeek * 2 && $currentWeek > 5) {
            return [
                'current_week_negative_count' => $currentWeek,
                'previous_week_negative_count' => $previousWeek,
                'increase_factor' => round($currentWeek / max(1, $previousWeek), 1)
            ];
        }
        
        return null;
    }

    private function detectNpsDrop(): ?array
    {
        $currentNps = $this->calculateOverallNps(
            FactCustomerSentiment::where('sentiment_date_key', '>=', now()->subWeek()->format('Ymd'))->get()
        );
        
        $previousNps = $this->calculateOverallNps(
            FactCustomerSentiment::whereBetween('sentiment_date_key', [
                now()->subWeek(2)->format('Ymd'),
                now()->subWeek()->format('Ymd')
            ])->get()
        );
        
        if ($currentNps < $previousNps - 20) {
            return [
                'current_nps' => $currentNps,
                'previous_nps' => $previousNps,
                'drop' => $previousNps - $currentNps
            ];
        }
        
        return null;
    }

    private function detectCriticalIssues(): ?array
    {
        $criticalCount = FactCustomerSentiment::where('sentiment_date_key', '>=', now()->subDay()->format('Ymd'))
            ->where('sentiment_score', '<', -0.7)
            ->count();
        
        if ($criticalCount >= 3) {
            return [
                'critical_issues_today' => $criticalCount,
                'threshold_exceeded' => true
            ];
        }
        
        return null;
    }

    private function extractKeyInsights(Collection $sentimentData): array
    {
        $insights = [];
        
        $avgSentiment = $sentimentData->avg('sentiment_score');
        $avgNps = $sentimentData->avg('nps_score');
        
        if ($avgSentiment > 0.3) {
            $insights[] = 'Overall customer sentiment is positive';
        } elseif ($avgSentiment < -0.3) {
            $insights[] = 'Overall customer sentiment requires attention';
        }
        
        if ($avgNps > 7) {
            $insights[] = 'Strong customer satisfaction and loyalty';
        } elseif ($avgNps < 5) {
            $insights[] = 'Customer satisfaction needs improvement';
        }
        
        $topNegativeCategory = $sentimentData->where('sentiment_score', '<', 0)
            ->groupBy('feedback_category')
            ->sortByDesc(fn($group) => $group->count())
            ->first();
            
        if ($topNegativeCategory) {
            $insights[] = "Most common issue: {$topNegativeCategory->first()->feedback_category}";
        }
        
        return $insights;
    }

    private function identifySentimentDrivers(Collection $sentimentData): array
    {
        return $sentimentData->groupBy('feedback_category')
            ->map(function ($group) {
                return [
                    'category' => $group->first()->feedback_category,
                    'avg_sentiment' => round($group->avg('sentiment_score'), 2),
                    'count' => $group->count(),
                    'impact' => $group->count() * abs($group->avg('sentiment_score'))
                ];
            })
            ->sortByDesc('impact')
            ->take(5)
            ->values()
            ->toArray();
    }

    private function analyzeSatisfactionTrends(Collection $sentimentData): array
    {
        // Implementation for satisfaction trend analysis
        return [];
    }

    private function generateRecommendations(Collection $sentimentData): array
    {
        $recommendations = [];
        $avgSentiment = $sentimentData->avg('sentiment_score');
        $avgNps = $sentimentData->avg('nps_score');
        
        if ($avgSentiment < 0) {
            $recommendations[] = 'Focus on addressing common customer complaints';
        }
        
        if ($avgNps < 5) {
            $recommendations[] = 'Implement customer experience improvements';
        }
        
        $topCategory = $sentimentData->groupBy('feedback_category')
            ->sortByDesc(fn($group) => $group->count())
            ->first();
            
        if ($topCategory) {
            $recommendations[] = "Prioritize improvements in: {$topCategory->first()->feedback_category}";
        }
        
        return $recommendations;
    }

    private function identifyActionItems(Collection $sentimentData): array
    {
        $actionItems = [];
        $criticalIssues = $this->identifyCriticalIssues($sentimentData);
        
        if (count($criticalIssues) > 0) {
            $actionItems[] = 'Address critical customer issues immediately';
        }
        
        $lowNpsCount = $sentimentData->where('nps_score', '<', 4)->count();
        if ($lowNpsCount > 0) {
            $actionItems[] = "Follow up with {$lowNpsCount} highly dissatisfied customers";
        }
        
        return $actionItems;
    }
}
