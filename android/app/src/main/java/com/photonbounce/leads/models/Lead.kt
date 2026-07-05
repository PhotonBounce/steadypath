package com.photonbounce.leads.models

import com.google.gson.annotations.SerializedName

data class Lead(
    val id: String,
    val name: String,
    val email: String? = null,
    val phone: String? = null,
    val company: String? = null,
    val status: String = "new",
    val source: String? = null,
    @SerializedName("microsite_id") val micrositeId: String? = null,
    @SerializedName("ml_score") val mlScore: Double? = null,
    val niche: String? = null,
    val notes: String? = null,
    @SerializedName("created_at") val createdAt: String? = null,
    @SerializedName("updated_at") val updatedAt: String? = null
)

data class UpdateLeadRequest(
    val status: String? = null,
    val notes: String? = null
)
