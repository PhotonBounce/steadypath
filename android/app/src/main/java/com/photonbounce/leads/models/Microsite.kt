package com.photonbounce.leads.models

import com.google.gson.annotations.SerializedName

data class Microsite(
    val id: String,
    val name: String,
    val slug: String,
    val niche: String? = null,
    val active: Boolean = true,
    @SerializedName("lead_count") val leadCount: Int = 0,
    @SerializedName("created_at") val createdAt: String? = null
)

data class CreateMicrositeRequest(
    val name: String,
    val slug: String,
    val niche: String? = null
)

data class UpdateMicrositeRequest(
    val name: String? = null,
    val niche: String? = null,
    val active: Boolean? = null
)
