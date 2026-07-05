package com.photonbounce.leads.models

import com.google.gson.annotations.SerializedName

data class DashboardStats(
    @SerializedName("total_leads") val totalLeads: Int = 0,
    @SerializedName("won_leads") val wonLeads: Int = 0,
    @SerializedName("avg_ml_score") val avgMlScore: Double? = null,
    @SerializedName("active_niches") val activeNiches: Int = 0,
    @SerializedName("recent_leads") val recentLeads: List<Lead> = emptyList()
)

data class AnalyticsData(
    @SerializedName("lead_trends") val leadTrends: List<TrendPoint> = emptyList(),
    @SerializedName("status_breakdown") val statusBreakdown: List<StatusCount> = emptyList()
)

data class TrendPoint(
    val date: String,
    val count: Int
)

data class StatusCount(
    val status: String,
    val count: Int
)
