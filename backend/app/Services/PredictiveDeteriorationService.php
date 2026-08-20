<?php

namespace App\Services;

use App\Models\AiAlert;
use App\Models\ClinicalTimeline;

class PredictiveDeteriorationService
{
    protected HealthTrendAnalyzer $healthTrendAnalyzer;

    public function __construct(
        HealthTrendAnalyzer $healthTrendAnalyzer
    ) {
        $this->healthTrendAnalyzer =
            $healthTrendAnalyzer;
    }

    /*
    |--------------------------------------------------------------------------
    | Predict Resident Deterioration Risk
    |--------------------------------------------------------------------------
    */

    public function predict($residentId)
    {
        $riskScore = 0;

        $warningSigns = [];

        $recommendations = [];

        /*
        |--------------------------------------------------------------------------
        | 1. Health Trend Intelligence
        |--------------------------------------------------------------------------
        */

        $healthTrend =
            $this->healthTrendAnalyzer
                ->analyze($residentId);

        $currentCondition =
            $healthTrend['current_condition']['status']
            ?? 'STABLE';

        $trendStatus =
            $healthTrend['trend']['status']
            ?? 'STABLE';

        $trendConfidence =
            $healthTrend['trend_confidence']
            ?? 0;

        $trendAnalysis =
            $healthTrend['trend']['analysis']
            ?? [];

        $cleanedVitals =
            $healthTrend['vitals']
            ?? [];

        $cleanedDataPoints =
            $healthTrend['data_points']
            ?? count($cleanedVitals);

        $dataQuality =
            $healthTrend['data_quality']
            ?? [];

        $duplicatesRemoved =
            $dataQuality['duplicates_removed']
            ?? 0;

        /*
        |--------------------------------------------------------------------------
        | 2. Current Clinical Condition
        |--------------------------------------------------------------------------
        */

        if ($currentCondition === 'CRITICAL') {

            $riskScore += 25;

            $warningSigns[] =
                'Current clinical condition is critical.';

            $recommendations[] =
                'Immediate clinical assessment recommended.';

        } elseif ($currentCondition === 'HIGH') {

            $riskScore += 15;

            $warningSigns[] =
                'Current clinical condition is high risk.';
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Trend Direction
        |--------------------------------------------------------------------------
        */

        if ($trendStatus === 'WORSENING') {

            $riskScore += 25;

            $warningSigns[] =
                'Resident health trend is worsening.';

            foreach ($trendAnalysis as $analysis) {

                $warningSigns[] =
                    $analysis;
            }

            $recommendations[] =
                'Increase monitoring frequency due to worsening trend.';
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Cleaned Abnormal Vital Evidence
        |--------------------------------------------------------------------------
        */

        $abnormalVitalPoints = 0;

        foreach ($cleanedVitals as $vital) {

            $systolic =
                isset($vital['blood_pressure_systolic'])
                ? (float) $vital['blood_pressure_systolic']
                : null;

            $diastolic =
                isset($vital['blood_pressure_diastolic'])
                ? (float) $vital['blood_pressure_diastolic']
                : null;

            $oxygen =
                isset($vital['oxygen_level'])
                ? (float) $vital['oxygen_level']
                : null;

            $temperature =
                isset($vital['temperature'])
                ? (float) $vital['temperature']
                : null;

            $glucose =
                isset($vital['blood_glucose'])
                ? (float) $vital['blood_glucose']
                : null;

            $isAbnormal = false;

            if (
                $systolic !== null
                &&
                $systolic >= 160
            ) {
                $isAbnormal = true;
            }

            if (
                $diastolic !== null
                &&
                $diastolic >= 100
            ) {
                $isAbnormal = true;
            }

            if (
                $oxygen !== null
                &&
                $oxygen < 92
            ) {
                $isAbnormal = true;
            }

            if (
                $temperature !== null
                &&
                $temperature >= 38
            ) {
                $isAbnormal = true;
            }

            if (
                $glucose !== null
                &&
                $glucose >= 11
            ) {
                $isAbnormal = true;
            }

            if ($isAbnormal) {

                $abnormalVitalPoints++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Vital Evidence Scoring
        |--------------------------------------------------------------------------
        */

        if ($abnormalVitalPoints >= 4) {

            $riskScore += 20;

        } elseif ($abnormalVitalPoints >= 2) {

            $riskScore += 15;

        } elseif ($abnormalVitalPoints === 1) {

            $riskScore += 10;
        }

        if ($abnormalVitalPoints > 0) {

            $warningSigns[] =
                $abnormalVitalPoints .
                ' unique abnormal monitoring point(s) detected.';

            $recommendations[] =
                'Repeat vital signs monitoring regularly.';
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Active Critical AI Alerts
        |--------------------------------------------------------------------------
        */

        $criticalAlerts =
            AiAlert::where(
                'resident_id',
                $residentId
            )
            ->where(
                'severity',
                'CRITICAL'
            )
            ->where(
                'status',
                'OPEN'
            )
            ->count();

        if ($criticalAlerts > 0) {

            $riskScore += 20;

            $warningSigns[] =
                $criticalAlerts .
                ' unresolved critical AI alert(s).';

            $recommendations[] =
                'Review unresolved critical AI alerts.';
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Medication Behaviour Analysis
        |--------------------------------------------------------------------------
        */

        $delayedMedication =
            ClinicalTimeline::where(
                'resident_id',
                $residentId
            )
            ->where(
                'event_type',
                'MEDICATION_DELAYED'
            )
            ->where(
                'created_at',
                '>=',
                now()->subDays(7)
            )
            ->count();

        if ($delayedMedication > 0) {

            $riskScore += 10;

            $warningSigns[] =
                $delayedMedication .
                ' medication delay event(s) detected.';

            $recommendations[] =
                'Review medication adherence.';
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Historical AI Clinical Pattern
        |--------------------------------------------------------------------------
        */

        $clinicalEvents =
            ClinicalTimeline::where(
                'resident_id',
                $residentId
            )
            ->where(
                'event_type',
                'AI_ALERT'
            )
            ->where(
                'created_at',
                '>=',
                now()->subDays(30)
            )
            ->count();

        if ($clinicalEvents >= 3) {

            $riskScore += 10;

            $warningSigns[] =
                'Frequent AI clinical events detected within 30 days.';
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Normalize Risk Score
        |--------------------------------------------------------------------------
        */

        $riskScore =
            min(
                $riskScore,
                100
            );

        /*
        |--------------------------------------------------------------------------
        | 10. Future Deterioration Classification
        |--------------------------------------------------------------------------
        */

        if ($riskScore >= 80) {

            $predictionRisk =
                'CRITICAL';

        } elseif ($riskScore >= 60) {

            $predictionRisk =
                'HIGH';

        } elseif ($riskScore >= 30) {

            $predictionRisk =
                'MEDIUM';

        } else {

            $predictionRisk =
                'LOW';
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Prediction Window
        |--------------------------------------------------------------------------
        */

        if (
            $predictionRisk === 'CRITICAL'
            ||
            $trendStatus === 'WORSENING'
        ) {

            $predictionWindow =
                'Next 24 hours';

        } else {

            $predictionWindow =
                'Next 24-48 hours';
        }

        /*
        |--------------------------------------------------------------------------
        | 12. Prediction Confidence
        |--------------------------------------------------------------------------
        */

        $predictionConfidence =
            $this->calculatePredictionConfidence(
                $healthTrend,
                $criticalAlerts,
                $abnormalVitalPoints
            );

        /*
        |--------------------------------------------------------------------------
        | 13. Evidence Quality
        |--------------------------------------------------------------------------
        */

        $evidenceQuality =
            $this->determineEvidenceQuality(
                $cleanedDataPoints,
                $trendConfidence
            );

        /*
        |--------------------------------------------------------------------------
        | 14. Clinical Safety Guardrail
        |--------------------------------------------------------------------------
        */

        $escalationStatus =
            $this->determineEscalationStatus(
                $currentCondition,
                $predictionRisk,
                $trendStatus,
                $criticalAlerts
            );

        $clinicalActionTiming =
            $this->determineClinicalActionTiming(
                $currentCondition,
                $escalationStatus
            );

        /*
        |--------------------------------------------------------------------------
        | 15. Risk Interpretation
        |--------------------------------------------------------------------------
        */

        $riskInterpretation =
            $this->buildRiskInterpretation(
                $currentCondition,
                $predictionRisk,
                $trendStatus,
                $evidenceQuality
            );

        /*
        |--------------------------------------------------------------------------
        | 16. Predictive Clinical Driver Analysis
        |--------------------------------------------------------------------------
        */

        $clinicalDrivers =
            $this->analyzeClinicalDrivers(
                $cleanedVitals,
                $trendAnalysis,
                $delayedMedication,
                $criticalAlerts
            );

        /*
        |--------------------------------------------------------------------------
        | Remove Duplicate Messages
        |--------------------------------------------------------------------------
        */

        $warningSigns =
            array_values(
                array_unique(
                    $warningSigns
                )
            );

        $recommendations =
            array_values(
                array_unique(
                    $recommendations
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Final Predictive Intelligence Response
        |--------------------------------------------------------------------------
        */

        return [

            'resident_id' =>
                (int) $residentId,

            'clinical_severity' =>
                $currentCondition,

            'deterioration_risk' =>
                $predictionRisk,

            'risk_score' =>
                $riskScore,

            'prediction_window' =>
                $predictionWindow,

            'trend_direction' =>
                $trendStatus,

            'prediction_confidence' =>
                $predictionConfidence,

            'trend_confidence' =>
                $trendConfidence,

            'escalation_status' =>
                $escalationStatus,

            'clinical_action_timing' =>
                $clinicalActionTiming,

            'risk_interpretation' =>
                $riskInterpretation,

            /*
            |--------------------------------------------------------------------------
            | Step 51.4 Clinical Driver Intelligence
            |--------------------------------------------------------------------------
            */

            'clinical_drivers' =>
                $clinicalDrivers,

            'evidence_quality' => [

                'status' =>
                    $evidenceQuality,

                'cleaned_data_points' =>
                    $cleanedDataPoints,

                'abnormal_data_points' =>
                    $abnormalVitalPoints,

                'duplicates_removed' =>
                    $duplicatesRemoved,

                'data_source' =>
                    'HealthTrendAnalyzer cleaned vital dataset'
            ],

            'warning_signs' =>
                $warningSigns,

            'recommended_monitoring' =>
                $recommendations
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Prediction Confidence
    |--------------------------------------------------------------------------
    */

    private function calculatePredictionConfidence(
        array $healthTrend,
        int $criticalAlerts,
        int $abnormalVitalPoints
    ): int {

        $confidence =
            $healthTrend['trend_confidence']
            ?? 40;

        if ($abnormalVitalPoints > 0) {

            $confidence += 10;
        }

        if ($criticalAlerts > 0) {

            $confidence += 10;
        }

        $dataPoints =
            $healthTrend['data_points']
            ?? 0;

        if ($dataPoints >= 5) {

            $confidence += 10;
        }

        return min(
            $confidence,
            100
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Evidence Quality Classification
    |--------------------------------------------------------------------------
    */

    private function determineEvidenceQuality(
        int $dataPoints,
        int $trendConfidence
    ): string {

        if (
            $dataPoints >= 5
            &&
            $trendConfidence >= 70
        ) {

            return 'HIGH';
        }

        if (
            $dataPoints >= 3
            &&
            $trendConfidence >= 50
        ) {

            return 'MODERATE';
        }

        return 'LIMITED';
    }

    /*
    |--------------------------------------------------------------------------
    | Clinical Escalation Guardrail
    |--------------------------------------------------------------------------
    */

    private function determineEscalationStatus(
        string $currentCondition,
        string $predictionRisk,
        string $trendStatus,
        int $criticalAlerts
    ): string {

        if ($currentCondition === 'CRITICAL') {

            return 'URGENT CLINICAL REVIEW';
        }

        if (
            $predictionRisk === 'CRITICAL'
            ||
            $trendStatus === 'WORSENING'
        ) {

            return 'HIGH PRIORITY MONITORING';
        }

        if (
            $predictionRisk === 'HIGH'
            ||
            $criticalAlerts > 0
        ) {

            return 'ENHANCED MONITORING';
        }

        return 'ROUTINE MONITORING';
    }

    /*
    |--------------------------------------------------------------------------
    | Clinical Action Timing
    |--------------------------------------------------------------------------
    */

    private function determineClinicalActionTiming(
        string $currentCondition,
        string $escalationStatus
    ): string {

        if ($currentCondition === 'CRITICAL') {

            return 'IMMEDIATE';
        }

        if (
            $escalationStatus ===
            'HIGH PRIORITY MONITORING'
        ) {

            return 'WITHIN 1 HOUR';
        }

        if (
            $escalationStatus ===
            'ENHANCED MONITORING'
        ) {

            return 'WITHIN 4 HOURS';
        }

        return 'ROUTINE';
    }

    /*
    |--------------------------------------------------------------------------
    | Human-Readable Risk Interpretation
    |--------------------------------------------------------------------------
    */

    private function buildRiskInterpretation(
        string $currentCondition,
        string $predictionRisk,
        string $trendStatus,
        string $evidenceQuality
    ): string {

        if (
            $currentCondition === 'CRITICAL'
            &&
            $predictionRisk === 'CRITICAL'
        ) {

            return
                'Resident is currently clinically critical and has critical risk of further deterioration.';
        }

        if (
            $currentCondition === 'CRITICAL'
            &&
            in_array(
                $predictionRisk,
                [
                    'HIGH',
                    'MEDIUM',
                    'LOW'
                ]
            )
        ) {

            return
                'Resident is currently clinically critical. Future deterioration risk is ' .
                strtolower($predictionRisk) .
                ', but immediate clinical review remains required regardless of predictive risk.';
        }

        if ($trendStatus === 'WORSENING') {

            return
                'Resident health trend is worsening with ' .
                strtolower($predictionRisk) .
                ' predicted deterioration risk.';
        }

        if ($evidenceQuality === 'LIMITED') {

            return
                'Predictive evidence is limited. Continue monitoring and collect additional clinical data.';
        }

        return
            'Resident currently has ' .
            strtolower($predictionRisk) .
            ' predicted deterioration risk with ' .
            strtolower($evidenceQuality) .
            ' supporting evidence.';
    }

    /*
    |--------------------------------------------------------------------------
    | Step 51.4 Predictive Clinical Driver Analysis
    |--------------------------------------------------------------------------
    */

        private function analyzeClinicalDrivers(
        iterable $cleanedVitals,
        iterable $trendAnalysis,
        int $delayedMedication,
        int $criticalAlerts
    ): array {

        $drivers = [

            'cardiovascular' => [
                'score' => 0,
                'level' => 'LOW',
                'evidence' => []
            ],

            'respiratory' => [
                'score' => 0,
                'level' => 'LOW',
                'evidence' => []
            ],

            'metabolic' => [
                'score' => 0,
                'level' => 'LOW',
                'evidence' => []
            ],

            'infection' => [
                'score' => 0,
                'level' => 'LOW',
                'evidence' => []
            ],

            'medication' => [
                'score' => 0,
                'level' => 'LOW',
                'evidence' => []
            ]
        ];

        /*
        |--------------------------------------------------------------------------
        | Analyze Cleaned Vital Evidence
        |--------------------------------------------------------------------------
        */

        foreach ($cleanedVitals as $vital) {

            $systolic =
                isset($vital['blood_pressure_systolic'])
                ? (float) $vital['blood_pressure_systolic']
                : null;

            $diastolic =
                isset($vital['blood_pressure_diastolic'])
                ? (float) $vital['blood_pressure_diastolic']
                : null;

            $oxygen =
                isset($vital['oxygen_level'])
                ? (float) $vital['oxygen_level']
                : null;

            $glucose =
                isset($vital['blood_glucose'])
                ? (float) $vital['blood_glucose']
                : null;

            $temperature =
                isset($vital['temperature'])
                ? (float) $vital['temperature']
                : null;

            /*
            |--------------------------------------------------------------------------
            | Cardiovascular Driver
            |--------------------------------------------------------------------------
            */

            if (
                ($systolic !== null && $systolic >= 160)
                ||
                ($diastolic !== null && $diastolic >= 100)
            ) {

                $drivers['cardiovascular']['score'] += 20;

                $drivers['cardiovascular']['evidence'][] =
                    'Abnormally high blood pressure detected.';
            }

            /*
            |--------------------------------------------------------------------------
            | Respiratory Driver
            |--------------------------------------------------------------------------
            */

            if (
                $oxygen !== null
                &&
                $oxygen < 92
            ) {

                $drivers['respiratory']['score'] += 25;

                $drivers['respiratory']['evidence'][] =
                    'Low oxygen saturation detected.';
            }

            /*
            |--------------------------------------------------------------------------
            | Metabolic Driver
            |--------------------------------------------------------------------------
            */

            if (
                $glucose !== null
                &&
                $glucose >= 11
            ) {

                $drivers['metabolic']['score'] += 20;

                $drivers['metabolic']['evidence'][] =
                    'Elevated blood glucose detected.';
            }

            /*
            |--------------------------------------------------------------------------
            | Infection Driver
            |--------------------------------------------------------------------------
            */

            if (
                $temperature !== null
                &&
                $temperature >= 38
            ) {

                $drivers['infection']['score'] += 20;

                $drivers['infection']['evidence'][] =
                    'Elevated body temperature detected.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Trend-Based Driver Evidence
        |--------------------------------------------------------------------------
        */

        foreach ($trendAnalysis as $analysis) {

            $normalized =
                strtolower($analysis);

            if (
                str_contains(
                    $normalized,
                    'blood pressure'
                )
            ) {

                $drivers['cardiovascular']['score'] += 15;

                $drivers['cardiovascular']['evidence'][] =
                    $analysis;
            }

            if (
                str_contains(
                    $normalized,
                    'oxygen'
                )
            ) {

                $drivers['respiratory']['score'] += 15;

                $drivers['respiratory']['evidence'][] =
                    $analysis;
            }

            if (
                str_contains(
                    $normalized,
                    'glucose'
                )
            ) {

                $drivers['metabolic']['score'] += 15;

                $drivers['metabolic']['evidence'][] =
                    $analysis;
            }

            if (
                str_contains(
                    $normalized,
                    'temperature'
                )
            ) {

                $drivers['infection']['score'] += 15;

                $drivers['infection']['evidence'][] =
                    $analysis;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Medication Driver
        |--------------------------------------------------------------------------
        */

        if ($delayedMedication > 0) {

            $drivers['medication']['score'] +=
                min(
                    $delayedMedication * 20,
                    100
                );

            $drivers['medication']['evidence'][] =
                $delayedMedication .
                ' medication delay event(s) detected.';
        }

        /*
        |--------------------------------------------------------------------------
        | Critical Alert Support
        |--------------------------------------------------------------------------
        */

        if ($criticalAlerts > 0) {

            foreach (
                [
                    'cardiovascular',
                    'respiratory',
                    'metabolic',
                    'infection'
                ]
                as $domain
            ) {

                $drivers[$domain]['score'] += 5;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize + Classify Scores
        |--------------------------------------------------------------------------
        */

        foreach ($drivers as $domain => $driver) {

            $score =
                min(
                    $driver['score'],
                    100
                );

            if ($score >= 75) {

                $level = 'CRITICAL';

            } elseif ($score >= 50) {

                $level = 'HIGH';

            } elseif ($score >= 25) {

                $level = 'MODERATE';

            } else {

                $level = 'LOW';
            }

            $drivers[$domain]['score'] =
                $score;

            $drivers[$domain]['level'] =
                $level;

            $drivers[$domain]['evidence'] =
                array_values(
                    array_unique(
                        $driver['evidence']
                    )
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Step 51.5 Driver Ranking
        |--------------------------------------------------------------------------
        */

        $ranking = [];

        foreach ($drivers as $domain => $driver) {

            $ranking[] = [

                'domain' =>
                    $domain,

                'score' =>
                    $driver['score'],

                'level' =>
                    $driver['level']
            ];
        }

        usort(
            $ranking,
            function ($a, $b) {

                return $b['score']
                    <=>
                    $a['score'];
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Primary Driver
        |--------------------------------------------------------------------------
        */

        $primaryDriver =
            $ranking[0]['domain']
            ?? null;

        $primaryScore =
            $ranking[0]['score']
            ?? 0;

        /*
        |--------------------------------------------------------------------------
        | Dominant Driver Tie Detection
        |--------------------------------------------------------------------------
        */

        $dominantDrivers = [];

        foreach ($ranking as $driver) {

            if (
                $driver['score'] ===
                $primaryScore
                &&
                $driver['score'] > 0
            ) {

                $dominantDrivers[] =
                    $driver['domain'];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Clinically Significant Drivers
        |--------------------------------------------------------------------------
        |
        | Keep only MODERATE and above for summary intelligence.
        |
        */

        $significantDrivers =
            array_values(
                array_filter(
                    $ranking,
                    function ($driver) {

                        return
                            $driver['score'] >= 25;
                    }
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Driver Summary
        |--------------------------------------------------------------------------
        */

        $driverSummary =
            $this->buildDriverSummary(
                $ranking,
                $dominantDrivers
            );

        return [

            'primary_driver' =>
                $primaryDriver,

            'primary_score' =>
                $primaryScore,

            'dominant_drivers' =>
                $dominantDrivers,

            'driver_summary' =>
                $driverSummary,

            'driver_ranking' =>
                $ranking,

            'significant_drivers' =>
                $significantDrivers,

            'domains' =>
                $drivers
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Step 51.5 Predictive Driver Summary
    |--------------------------------------------------------------------------
    */

    private function buildDriverSummary(
        array $ranking,
        array $dominantDrivers
    ): string {

        if (empty($ranking)) {

            return
                'No predictive clinical drivers detected.';
        }

        $significant =
            array_values(
                array_filter(
                    $ranking,
                    function ($driver) {

                        return
                            $driver['score'] >= 25;
                    }
                )
            );

        if (empty($significant)) {

            return
                'No significant clinical driver currently dominates deterioration risk.';
        }

        /*
        |--------------------------------------------------------------------------
        | Multiple Dominant Drivers
        |--------------------------------------------------------------------------
        */

        if (count($dominantDrivers) > 1) {

            $formatted =
                array_map(
                    function ($domain) {

                        return
                            ucfirst($domain);
                    },
                    $dominantDrivers
                );

            return
                'Deterioration risk is primarily driven by ' .
                strtolower(
                    implode(
                        ' and ',
                        $formatted
                    )
                ) .
                ' instability.';
        }

        /*
        |--------------------------------------------------------------------------
        | Single Primary Driver
        |--------------------------------------------------------------------------
        */

        $primary =
            $significant[0];

        $secondary =
            $significant[1]
            ?? null;

        if ($secondary) {

            return
                'Primary deterioration driver is ' .
                $primary['domain'] .
                ' (' .
                strtolower($primary['level']) .
                '), with additional ' .
                $secondary['domain'] .
                ' involvement (' .
                strtolower($secondary['level']) .
                ').';
        }

        return
            'Primary deterioration driver is ' .
            $primary['domain'] .
            ' with ' .
            strtolower($primary['level']) .
            ' predictive severity.';
    }
}