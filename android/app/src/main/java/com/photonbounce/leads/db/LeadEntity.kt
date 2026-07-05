package com.photonbounce.leads.db

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "leads")
data class LeadEntity(
    @PrimaryKey val id: String,
    val name: String,
    val email: String? = null,
    val phone: String? = null,
    val company: String? = null,
    val status: String = "new",
    val source: String? = null,
    val micrositeId: String? = null,
    val mlScore: Double? = null,
    val niche: String? = null,
    val notes: String? = null,
    val createdAt: String? = null,
    val updatedAt: String? = null,
    val pendingStatus: String? = null,
    val pendingNotes: String? = null,
    val isSynced: Boolean = true
)
