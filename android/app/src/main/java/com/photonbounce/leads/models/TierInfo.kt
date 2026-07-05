package com.photonbounce.leads.models

import com.google.gson.annotations.SerializedName

data class TierInfo(
    val tier: String = "free",
    @SerializedName("max_microsites") val maxMicrosites: Int = 1,
    @SerializedName("has_ads") val hasAds: Boolean = true,
    @SerializedName("has_ml_scores") val hasMlScores: Boolean = false,
    @SerializedName("has_advanced_analytics") val hasAdvancedAnalytics: Boolean = false,
    @SerializedName("has_push_notifications") val hasPushNotifications: Boolean = false,
    @SerializedName("subscription_expires_at") val subscriptionExpiresAt: String? = null
)
