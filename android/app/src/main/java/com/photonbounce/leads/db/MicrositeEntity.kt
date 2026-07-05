package com.photonbounce.leads.db

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "microsites")
data class MicrositeEntity(
    @PrimaryKey val id: String,
    val name: String,
    val slug: String,
    val niche: String? = null,
    val active: Boolean = true,
    val leadCount: Int = 0,
    val createdAt: String? = null
)
